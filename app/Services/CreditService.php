<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\CreditDebitNote;
use App\Models\CreditPayment;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Space;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CreditService
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    public function createCreditAccount(Space $space, array $data): CreditAccount
    {
        return DB::transaction(function () use ($space, $data) {
            $ledgerAccount = Account::create([
                'space_id' => $space->id,
                'name' => $data['name'].' (Kewajiban)',
                'type' => $data['type'] ?? 'credit_card',
                'opening_balance' => 0,
                'opened_on' => now()->toDateString(),
                'is_default' => false,
            ]);

            $creditAccount = CreditAccount::create([
                'space_id' => $space->id,
                'account_id' => $ledgerAccount->id,
                'name' => $data['name'],
                'type' => $data['type'] ?? 'credit_card',
                'credit_limit' => (int) ($data['credit_limit'] ?? 0),
                'billing_cycle_day' => ! empty($data['billing_cycle_day']) ? (int) $data['billing_cycle_day'] : null,
                'due_day' => ! empty($data['due_day']) ? (int) $data['due_day'] : null,
            ]);

            $this->activityLog->log(
                module: 'credit',
                action: 'created',
                description: "Menambahkan akun kredit \"{$creditAccount->name}\" (limit ".$this->activityLog->formatCurrency($creditAccount->credit_limit).')',
                space: $space,
                subject: $creditAccount,
                subjectLabel: $creditAccount->name,
                properties: [
                    'type' => $creditAccount->type,
                    'credit_limit' => $creditAccount->credit_limit,
                ],
            );

            return $creditAccount;
        });
    }

    public function updateCreditAccount(CreditAccount $creditAccount, array $data): CreditAccount
    {
        return DB::transaction(function () use ($creditAccount, $data) {
            $before = [
                'name' => $creditAccount->name,
                'type' => $creditAccount->type,
                'credit_limit' => $creditAccount->credit_limit,
                'billing_cycle_day' => $creditAccount->billing_cycle_day,
                'due_day' => $creditAccount->due_day,
            ];

            $creditAccount->update([
                'name' => $data['name'],
                'type' => $data['type'] ?? $creditAccount->type,
                'credit_limit' => (int) ($data['credit_limit'] ?? 0),
                'billing_cycle_day' => ! empty($data['billing_cycle_day']) ? (int) $data['billing_cycle_day'] : null,
                'due_day' => ! empty($data['due_day']) ? (int) $data['due_day'] : null,
            ]);

            if ($creditAccount->ledgerAccount) {
                $creditAccount->ledgerAccount->update([
                    'name' => $data['name'].' (Kewajiban)',
                    'type' => $data['type'] ?? $creditAccount->type,
                ]);
            }

            $diff = $this->activityLog->buildDiff($before, [
                'name' => $creditAccount->name,
                'type' => $creditAccount->type,
                'credit_limit' => $creditAccount->credit_limit,
                'billing_cycle_day' => $creditAccount->billing_cycle_day,
                'due_day' => $creditAccount->due_day,
            ], [
                'name' => 'Nama Akun',
                'type' => 'Tipe',
                'credit_limit' => 'Limit Kredit',
                'billing_cycle_day' => 'Tanggal Cetak Tagihan',
                'due_day' => 'Tanggal Jatuh Tempo',
            ]);

            $this->activityLog->log(
                module: 'credit',
                action: 'updated',
                description: "Memperbarui akun kredit \"{$creditAccount->name}\"",
                space: $creditAccount->space,
                subject: $creditAccount,
                subjectLabel: $creditAccount->name,
                properties: $diff,
            );

            return $creditAccount;
        });
    }

    public function recordCreditPurchase(Space $space, User $actor, array $data): InstallmentPlan
    {
        $creditAccount = CreditAccount::where('space_id', $space->id)
            ->where('id', $data['credit_account_id'])
            ->first();

        if (! $creditAccount || $creditAccount->isArchived()) {
            throw ValidationException::withMessages([
                'credit_account_id' => 'Akun kredit tidak valid atau telah diarsipkan.',
            ]);
        }

        $category = Category::where('space_id', $space->id)
            ->where('id', $data['category_id'])
            ->expense()
            ->active()
            ->first();

        if (! $category) {
            throw ValidationException::withMessages([
                'category_id' => 'Kategori pengeluaran tidak valid atau telah diarsipkan.',
            ]);
        }

        $totalAmount = round((float) $data['total_amount'], 2);
        if ($totalAmount <= 0) {
            throw ValidationException::withMessages([
                'total_amount' => 'Nominal pembelian harus lebih dari 0.',
            ]);
        }

        $tenor = max(1, (int) ($data['tenor'] ?? 1));

        return DB::transaction(function () use ($space, $actor, $creditAccount, $category, $data, $totalAmount, $tenor) {
            $lockedCreditAccount = CreditAccount::where('id', $creditAccount->id)->lockForUpdate()->first();
            $available = $lockedCreditAccount->availableLimit();

            if ($totalAmount > $available) {
                throw ValidationException::withMessages([
                    'total_amount' => 'Nominal pembelian melebihi sisa limit kredit yang tersedia ('.$lockedCreditAccount->formatted_available_limit.').',
                ]);
            }

            $transaction = FinancialTransaction::create([
                'space_id' => $space->id,
                'type' => 'expense',
                'transaction_date' => $data['start_date'],
                'transaction_time' => ! empty($data['transaction_time']) ? $data['transaction_time'] : now()->format('H:i:s'),
                'description' => 'Pembelian Kredit: '.$data['name'].' ('.$lockedCreditAccount->name.')',
                'category_id' => $category->id,
                'member_id' => $data['member_id'] ?? null,
                'status' => 'posted',
                'created_by' => $actor->id,
                'version' => 1,
            ]);

            $transaction->entries()->create([
                'account_id' => $lockedCreditAccount->account_id,
                'amount_signed' => -$totalAmount,
            ]);

            $interestRate = (float) ($data['interest_rate'] ?? 0);
            $adminFee = ! empty($data['admin_fee']) ? round((float) $data['admin_fee'], 2) : 0;

            $planData = [
                'space_id' => $space->id,
                'credit_account_id' => $lockedCreditAccount->id,
                'financial_transaction_id' => $transaction->id,
                'category_id' => $category->id,
                'member_id' => $data['member_id'] ?? null,
                'name' => $data['name'],
                'total_amount' => $totalAmount,
                'tenor' => $tenor,
                'interest_rate' => $interestRate,
                'start_date' => $data['start_date'],
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
            ];

            if (Schema::hasColumn('installment_plans', 'admin_fee')) {
                $planData['admin_fee'] = $adminFee;
            }

            $plan = InstallmentPlan::create($planData);

            $monthlyInterest = round($totalAmount * ($interestRate / 100), 2);
            $isIntegerDivision = fmod($totalAmount, 1.0) == 0.0 && fmod($adminFee, 1.0) == 0.0;

            if ($isIntegerDivision) {
                $basePrincipal = intdiv((int) $totalAmount, $tenor);
                $remainderPrincipal = (int) $totalAmount % $tenor;
                $baseFee = intdiv((int) $adminFee, $tenor);
                $remainderFee = (int) $adminFee % $tenor;
            } else {
                $basePrincipal = round($totalAmount / $tenor, 2);
                $remainderPrincipal = round($totalAmount - ($basePrincipal * $tenor), 2);
                $baseFee = round($adminFee / $tenor, 2);
                $remainderFee = round($adminFee - ($baseFee * $tenor), 2);
            }

            $startDate = Carbon::parse($data['start_date']);
            $firstDueDate = ! empty($data['first_due_date'])
                ? Carbon::parse($data['first_due_date'])
                : $lockedCreditAccount->calculateFirstDueDate($startDate);

            $targetDueDay = ! empty($lockedCreditAccount->due_day) ? $lockedCreditAccount->due_day : $firstDueDate->day;

            $markPastPaid = ! empty($data['mark_past_paid']);
            $pastPaidPrincipal = 0;
            $pastPaidInstallments = [];

            for ($i = 1; $i <= $tenor; $i++) {
                $principal = round($basePrincipal + ($i === 1 ? $remainderPrincipal : 0), 2);
                $fee = round($baseFee + ($i === 1 ? $remainderFee : 0), 2);
                if ($i === 1) {
                    $dueDate = $firstDueDate->copy();
                } else {
                    $dueDate = $firstDueDate->copy()->addMonthsNoOverflow($i - 1);
                    $dueDate->setDay(min($targetDueDay, $dueDate->daysInMonth));
                }
                $totalInst = round($principal + $monthlyInterest + $fee, 2);

                $isPast = $markPastPaid && $dueDate->copy()->startOfDay()->isPast() && ! $dueDate->isToday();
                $status = $isPast ? 'paid' : 'pending';
                $paidAt = $isPast ? now() : null;

                $inst = Installment::create([
                    'space_id' => $space->id,
                    'installment_plan_id' => $plan->id,
                    'installment_number' => $i,
                    'due_date' => $dueDate->toDateString(),
                    'principal_amount' => $principal,
                    'interest_amount' => $monthlyInterest,
                    'fee_amount' => $fee,
                    'total_amount' => $totalInst,
                    'status' => $status,
                    'paid_at' => $paidAt,
                ]);

                if ($isPast) {
                    $pastPaidPrincipal += $principal;
                    $pastPaidInstallments[] = $inst;
                }
            }

            if ($pastPaidPrincipal > 0 && $lockedCreditAccount->account_id) {
                $adjTx = FinancialTransaction::create([
                    'space_id' => $space->id,
                    'type' => 'adjustment',
                    'transaction_date' => now()->toDateString(),
                    'transaction_time' => now()->format('H:i:s'),
                    'description' => 'Pelunasan Pra-Pencatatan ARUS ('.count($pastPaidInstallments).' cicilan lampau) - '.$plan->name,
                    'category_id' => null,
                    'member_id' => $plan->member_id,
                    'status' => 'posted',
                    'created_by' => $actor->id,
                    'version' => 1,
                ]);

                $adjTx->entries()->create([
                    'account_id' => $lockedCreditAccount->account_id,
                    'amount_signed' => $pastPaidPrincipal,
                ]);

                foreach ($pastPaidInstallments as $pastInst) {
                    $pastInst->update(['payment_transaction_id' => $adjTx->id]);
                }

                $plan->checkCompletion();
            }

            if (empty($data['skip_activity_log'])) {
                $this->activityLog->log(
                    module: 'credit',
                    action: 'created',
                    description: "Mencatat pembelian kredit \"{$plan->name}\" sebesar ".$this->activityLog->formatCurrency($totalAmount)." ({$tenor}x cicilan via {$lockedCreditAccount->name})",
                    space: $space,
                    actor: $actor,
                    subject: $plan,
                    subjectLabel: $plan->name,
                    properties: [
                        'credit_account' => $lockedCreditAccount->name,
                        'total_amount' => $totalAmount,
                        'tenor' => $tenor,
                    ],
                );
            }

            return $plan;
        });
    }

    public function payInstallment(Space $space, User $actor, Installment $installment, array $data): CreditPayment
    {
        if ($installment->space_id !== $space->id) {
            throw ValidationException::withMessages([
                'installment' => 'Cicilan bukan milik ruang aktif.',
            ]);
        }

        if ($installment->isPaid()) {
            throw ValidationException::withMessages([
                'installment' => 'Cicilan ini sudah lunas atau pernah dibayar.',
            ]);
        }

        $sourceAccount = Account::where('space_id', $space->id)
            ->where('id', $data['source_account_id'])
            ->first();

        if (! $sourceAccount || $sourceAccount->isArchived()) {
            throw ValidationException::withMessages([
                'source_account_id' => 'Rekening sumber dana tidak valid atau telah diarsipkan.',
            ]);
        }

        if (! in_array($sourceAccount->type, ['bank', 'cash', 'e-wallet'])) {
            throw ValidationException::withMessages([
                'source_account_id' => 'Rekening sumber dana harus berupa rekening kas, bank, atau e-wallet.',
            ]);
        }

        $paymentAmount = isset($data['amount']) ? round((float) $data['amount'], 2) : round((float) $installment->principal_amount, 2);

        if ($paymentAmount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran harus lebih dari 0.',
            ]);
        }

        if ($paymentAmount > $installment->principal_amount) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran tidak boleh melebihi sisa pokok cicilan ini (Rp '.number_format($installment->principal_amount, 0, ',', '.').'). Gunakan Nota Debit jika ada penambahan pokok atau biaya penyesuaian.',
            ]);
        }

        return DB::transaction(function () use ($space, $actor, $installment, $sourceAccount, $paymentAmount, $data) {
            $lockedInstallment = Installment::where('id', $installment->id)->lockForUpdate()->first();
            if ($lockedInstallment->isPaid()) {
                throw ValidationException::withMessages([
                    'installment' => 'Cicilan ini sudah lunas atau pernah dibayar.',
                ]);
            }

            $creditAccount = $lockedInstallment->installmentPlan->creditAccount;
            $diff = $paymentAmount - (int) $lockedInstallment->principal_amount;

            $accountIds = [$sourceAccount->id, $creditAccount->account_id];
            sort($accountIds);
            Account::whereIn('id', $accountIds)->lockForUpdate()->get();

            $transaction = FinancialTransaction::create([
                'space_id' => $space->id,
                'type' => 'credit_payment',
                'transaction_date' => $data['payment_date'],
                'transaction_time' => ! empty($data['transaction_time']) ? $data['transaction_time'] : now()->format('H:i:s'),
                'description' => 'Pembayaran Cicilan #'.$lockedInstallment->installment_number.' - '.$lockedInstallment->installmentPlan->name,
                'category_id' => null,
                'member_id' => $lockedInstallment->installmentPlan->member_id,
                'status' => 'posted',
                'created_by' => $actor->id,
                'version' => 1,
            ]);

            $transaction->entries()->create([
                'account_id' => $sourceAccount->id,
                'amount_signed' => -$paymentAmount,
            ]);

            $transaction->entries()->create([
                'account_id' => $creditAccount->account_id,
                'amount_signed' => $paymentAmount,
            ]);

            if ($diff !== 0 && Schema::hasTable('credit_debit_notes')) {
                $noteTx = FinancialTransaction::create([
                    'space_id' => $space->id,
                    'type' => 'adjustment',
                    'transaction_date' => $data['payment_date'],
                    'transaction_time' => ! empty($data['transaction_time']) ? $data['transaction_time'] : now()->format('H:i:s'),
                    'description' => ($diff < 0 ? 'Nota Kredit (Selisih Pembulatan): ' : 'Nota Debit (Selisih Biaya/Pembulatan): ').'Cicilan #'.$lockedInstallment->installment_number.' - '.$lockedInstallment->installmentPlan->name,
                    'category_id' => null,
                    'member_id' => $lockedInstallment->installmentPlan->member_id,
                    'status' => 'posted',
                    'created_by' => $actor->id,
                    'version' => 1,
                ]);

                if ($creditAccount->account_id) {
                    $noteTx->entries()->create([
                        'account_id' => $creditAccount->account_id,
                        'amount_signed' => $diff < 0 ? abs($diff) : -$diff,
                    ]);
                }

                CreditDebitNote::create([
                    'space_id' => $space->id,
                    'credit_account_id' => $creditAccount->id,
                    'installment_plan_id' => $lockedInstallment->installment_plan_id,
                    'installment_id' => $lockedInstallment->id,
                    'financial_transaction_id' => $noteTx->id,
                    'type' => $diff < 0 ? 'credit_note' : 'debit_note',
                    'amount' => abs($diff),
                    'note_date' => $data['payment_date'],
                    'description' => ($diff < 0 ? 'Nota Kredit (Selisih Pembulatan) Pembayaran Cicilan #' : 'Nota Debit (Selisih Biaya/Pembulatan) Pembayaran Cicilan #').$lockedInstallment->installment_number,
                ]);
            }

            $lockedInstallment->update([
                'principal_amount' => $paymentAmount,
                'total_amount' => $paymentAmount + (int) $lockedInstallment->interest_amount + (int) $lockedInstallment->fee_amount,
                'status' => 'paid',
                'paid_at' => now(),
                'payment_transaction_id' => $transaction->id,
            ]);

            if ($diff !== 0) {
                $lockedInstallment->installmentPlan->update([
                    'total_amount' => (int) $lockedInstallment->installmentPlan->installments()->sum('principal_amount'),
                ]);
            }

            $payment = CreditPayment::create([
                'space_id' => $space->id,
                'credit_account_id' => $creditAccount->id,
                'installment_id' => $lockedInstallment->id,
                'source_account_id' => $sourceAccount->id,
                'transaction_id' => $transaction->id,
                'principal_amount' => $paymentAmount,
                'interest_amount' => (int) $lockedInstallment->interest_amount,
                'fee_amount' => (int) $lockedInstallment->fee_amount,
                'total_amount' => $paymentAmount + (int) $lockedInstallment->interest_amount + (int) $lockedInstallment->fee_amount,
                'payment_date' => $data['payment_date'],
                'notes' => $data['notes'] ?? ($diff !== 0 ? 'Pembayaran dengan penyesuaian selisih '.($diff < 0 ? '- Rp '.number_format(abs($diff), 0, ',', '.') : '+ Rp '.number_format($diff, 0, ',', '.')) : null),
            ]);

            $lockedInstallment->installmentPlan->checkCompletion();

            $this->activityLog->log(
                module: 'credit',
                action: 'paid',
                description: "Membayar cicilan ke-{$lockedInstallment->installment_number} \"{$lockedInstallment->installmentPlan->name}\" sebesar ".$this->activityLog->formatCurrency($payment->total_amount),
                space: $space,
                actor: $actor,
                subject: $lockedInstallment->installmentPlan,
                subjectLabel: $lockedInstallment->installmentPlan->name,
                properties: [
                    'installment_number' => $lockedInstallment->installment_number,
                    'total_amount' => $payment->total_amount,
                    'source_account' => $sourceAccount->name,
                    'payment_date' => $data['payment_date'],
                ],
            );

            return $payment;
        });
    }

    public function voidCreditPurchase(Space $space, User $actor, InstallmentPlan $plan): void
    {
        if ($plan->space_id !== $space->id) {
            throw ValidationException::withMessages([
                'plan' => 'Rencana cicilan bukan milik ruang aktif.',
            ]);
        }

        if ($plan->installments()->where('status', 'paid')->exists()) {
            throw ValidationException::withMessages([
                'plan' => 'Rencana cicilan yang sudah memiliki pembayaran tidak dapat dibatalkan secara langsung.',
            ]);
        }

        DB::transaction(function () use ($actor, $plan) {
            $plan->update(['status' => 'cancelled']);
            $plan->installments()->update(['status' => 'cancelled']);

            if ($plan->purchaseTransaction && $plan->purchaseTransaction->isPosted()) {
                $plan->purchaseTransaction->update([
                    'status' => 'void',
                    'updated_by' => $actor->id,
                ]);
            }

            $this->activityLog->log(
                module: 'credit',
                action: 'voided',
                description: "Membatalkan (void) rencana cicilan \"{$plan->name}\" sebesar ".$this->activityLog->formatCurrency($plan->total_amount),
                space: $plan->space,
                actor: $actor,
                subject: $plan,
                subjectLabel: $plan->name,
                properties: [
                    'total_amount' => $plan->total_amount,
                ],
            );
        });
    }

    public function payoffPlan(Space $space, User $actor, InstallmentPlan $plan, array $data): array
    {
        if ($plan->space_id !== $space->id) {
            throw ValidationException::withMessages([
                'plan' => 'Rencana cicilan bukan milik ruang aktif.',
            ]);
        }

        if (! $plan->isActive()) {
            throw ValidationException::withMessages([
                'plan' => 'Rencana cicilan sudah selesai atau tidak aktif.',
            ]);
        }

        $sourceAccount = Account::where('space_id', $space->id)
            ->where('id', $data['source_account_id'])
            ->first();

        if (! $sourceAccount || $sourceAccount->isArchived()) {
            throw ValidationException::withMessages([
                'source_account_id' => 'Rekening sumber dana tidak valid atau telah diarsipkan.',
            ]);
        }

        if (! in_array($sourceAccount->type, ['bank', 'cash', 'e-wallet'])) {
            throw ValidationException::withMessages([
                'source_account_id' => 'Rekening sumber dana harus berupa rekening kas, bank, atau e-wallet.',
            ]);
        }

        return DB::transaction(function () use ($space, $actor, $plan, $sourceAccount, $data) {
            $lockedPlan = InstallmentPlan::where('id', $plan->id)->lockForUpdate()->first();
            $pendingInstallments = $lockedPlan->installments()->where('status', 'pending')->lockForUpdate()->get();

            if ($pendingInstallments->isEmpty()) {
                throw ValidationException::withMessages([
                    'plan' => 'Tidak ada sisa cicilan pending yang perlu dilunasi.',
                ]);
            }

            $totalRemainingPrincipal = (int) $pendingInstallments->sum('principal_amount');
            $creditAccount = $lockedPlan->creditAccount;

            $accountIds = [$sourceAccount->id, $creditAccount->account_id];
            sort($accountIds);
            Account::whereIn('id', $accountIds)->lockForUpdate()->get();

            $transaction = FinancialTransaction::create([
                'space_id' => $space->id,
                'type' => 'credit_payment',
                'transaction_date' => $data['payment_date'],
                'transaction_time' => ! empty($data['transaction_time']) ? $data['transaction_time'] : now()->format('H:i:s'),
                'description' => 'Pelunasan Dipercepat: '.$lockedPlan->name.' ('.$pendingInstallments->count().' Cicilan)',
                'category_id' => null,
                'member_id' => $lockedPlan->member_id,
                'status' => 'posted',
                'created_by' => $actor->id,
                'version' => 1,
            ]);

            $transaction->entries()->create([
                'account_id' => $sourceAccount->id,
                'amount_signed' => -$totalRemainingPrincipal,
            ]);

            $transaction->entries()->create([
                'account_id' => $creditAccount->account_id,
                'amount_signed' => $totalRemainingPrincipal,
            ]);

            $settledCount = 0;
            foreach ($pendingInstallments as $inst) {
                $inst->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_transaction_id' => $transaction->id,
                ]);

                CreditPayment::create([
                    'space_id' => $space->id,
                    'credit_account_id' => $creditAccount->id,
                    'installment_id' => $inst->id,
                    'source_account_id' => $sourceAccount->id,
                    'transaction_id' => $transaction->id,
                    'principal_amount' => $inst->principal_amount,
                    'interest_amount' => (int) $inst->interest_amount,
                    'fee_amount' => (int) $inst->fee_amount,
                    'total_amount' => $inst->principal_amount + (int) $inst->interest_amount + (int) $inst->fee_amount,
                    'payment_date' => $data['payment_date'],
                    'notes' => $data['notes'] ?? 'Pelunasan Dipercepat Sekaligus',
                ]);

                $settledCount++;
            }

            $lockedPlan->update(['status' => 'completed']);

            $this->activityLog->log(
                module: 'credit',
                action: 'payoff',
                description: "Melunasi penuh rencana cicilan \"{$lockedPlan->name}\" ({$settledCount} cicilan) sebesar ".$this->activityLog->formatCurrency($totalRemainingPrincipal),
                space: $space,
                actor: $actor,
                subject: $lockedPlan,
                subjectLabel: $lockedPlan->name,
                properties: [
                    'settled_count' => $settledCount,
                    'total_paid' => $totalRemainingPrincipal,
                    'source_account' => $sourceAccount->name,
                ],
            );

            return [
                'transaction' => $transaction,
                'settled_count' => $settledCount,
                'total_paid' => $totalRemainingPrincipal,
            ];
        });
    }

    public function markInstallmentPaidWithoutCash(Space $space, User $actor, Installment $installment, ?string $notes = null): Installment
    {
        if ($installment->space_id !== $space->id) {
            throw ValidationException::withMessages([
                'installment' => 'Cicilan bukan milik ruang aktif.',
            ]);
        }

        if ($installment->isPaid()) {
            throw ValidationException::withMessages([
                'installment' => 'Cicilan ini sudah lunas.',
            ]);
        }

        return DB::transaction(function () use ($space, $actor, $installment, $notes) {
            $lockedInstallment = Installment::where('id', $installment->id)->lockForUpdate()->first();
            if ($lockedInstallment->isPaid()) {
                throw ValidationException::withMessages([
                    'installment' => 'Cicilan ini sudah lunas.',
                ]);
            }

            $creditAccount = $lockedInstallment->installmentPlan->creditAccount;

            $lockedInstallment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            if ($creditAccount && $creditAccount->account_id) {
                $transaction = FinancialTransaction::create([
                    'space_id' => $space->id,
                    'type' => 'adjustment',
                    'transaction_date' => $lockedInstallment->due_date->toDateString(),
                    'transaction_time' => now()->format('H:i:s'),
                    'description' => 'Pelunasan Pra-Pencatatan ARUS: Cicilan #'.$lockedInstallment->installment_number.' - '.$lockedInstallment->installmentPlan->name.($notes ? ' ('.$notes.')' : ''),
                    'category_id' => null,
                    'member_id' => $lockedInstallment->installmentPlan->member_id,
                    'status' => 'posted',
                    'created_by' => $actor->id,
                    'version' => 1,
                ]);

                $transaction->entries()->create([
                    'account_id' => $creditAccount->account_id,
                    'amount_signed' => $lockedInstallment->principal_amount,
                ]);

                $lockedInstallment->update([
                    'payment_transaction_id' => $transaction->id,
                ]);
            }

            $lockedInstallment->installmentPlan->checkCompletion();

            $this->activityLog->log(
                module: 'credit',
                action: 'mark_paid',
                description: "Menandai lunas tanpa kas untuk cicilan ke-{$lockedInstallment->installment_number} \"{$lockedInstallment->installmentPlan->name}\"",
                space: $space,
                actor: $actor,
                subject: $lockedInstallment->installmentPlan,
                subjectLabel: $lockedInstallment->installmentPlan->name,
                properties: [
                    'installment_number' => $lockedInstallment->installment_number,
                    'principal_amount' => $lockedInstallment->principal_amount,
                    'notes' => $notes,
                ],
            );

            return $lockedInstallment;
        });
    }

    public function markOverdueInstallmentsPaidWithoutCash(Space $space, User $actor, InstallmentPlan $plan, ?string $notes = null): int
    {
        if ($plan->space_id !== $space->id) {
            throw ValidationException::withMessages([
                'plan' => 'Rencana cicilan bukan milik ruang aktif.',
            ]);
        }

        return DB::transaction(function () use ($space, $actor, $plan, $notes) {
            $overdueInstallments = $plan->installments()
                ->where('status', 'pending')
                ->where('due_date', '<', now()->startOfDay())
                ->lockForUpdate()
                ->get();

            if ($overdueInstallments->isEmpty()) {
                return 0;
            }

            $creditAccount = $plan->creditAccount;
            $totalPrincipal = 0;

            foreach ($overdueInstallments as $installment) {
                $installment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
                $totalPrincipal += $installment->principal_amount;
            }

            if ($totalPrincipal > 0 && $creditAccount && $creditAccount->account_id) {
                $transaction = FinancialTransaction::create([
                    'space_id' => $space->id,
                    'type' => 'adjustment',
                    'transaction_date' => now()->toDateString(),
                    'transaction_time' => now()->format('H:i:s'),
                    'description' => 'Pelunasan Pra-Pencatatan ARUS ('.$overdueInstallments->count().' cicilan lampau) - '.$plan->name.($notes ? ' ('.$notes.')' : ''),
                    'category_id' => null,
                    'member_id' => $plan->member_id,
                    'status' => 'posted',
                    'created_by' => $actor->id,
                    'version' => 1,
                ]);

                $transaction->entries()->create([
                    'account_id' => $creditAccount->account_id,
                    'amount_signed' => $totalPrincipal,
                ]);

                foreach ($overdueInstallments as $installment) {
                    $installment->update(['payment_transaction_id' => $transaction->id]);
                }
            }

            $plan->checkCompletion();

            $this->activityLog->log(
                module: 'credit',
                action: 'mark_paid',
                description: "Menandai lunas {$overdueInstallments->count()} cicilan lampau untuk \"{$plan->name}\"",
                space: $space,
                actor: $actor,
                subject: $plan,
                subjectLabel: $plan->name,
                properties: [
                    'count' => $overdueInstallments->count(),
                    'total_principal' => $totalPrincipal,
                    'notes' => $notes,
                ],
            );

            return $overdueInstallments->count();
        });
    }

    public function resetInstallmentToPending(Space $space, User $actor, Installment $installment): Installment
    {
        if ($installment->space_id !== $space->id) {
            throw ValidationException::withMessages([
                'installment' => 'Cicilan bukan milik ruang aktif.',
            ]);
        }

        if (! $installment->isPaid()) {
            throw ValidationException::withMessages([
                'installment' => 'Cicilan belum lunas.',
            ]);
        }

        if ($installment->payments()->exists()) {
            throw ValidationException::withMessages([
                'installment' => 'Cicilan yang dibayar dengan kas/bank harus dibatalkan melalui transaksi pembayarannya.',
            ]);
        }

        return DB::transaction(function () use ($actor, $installment) {
            $locked = Installment::where('id', $installment->id)->lockForUpdate()->first();

            if ($locked->paymentTransaction && $locked->paymentTransaction->type === 'adjustment') {
                $locked->paymentTransaction->update([
                    'status' => 'void',
                    'updated_by' => $actor->id,
                ]);
            }

            $locked->update([
                'status' => 'pending',
                'paid_at' => null,
                'payment_transaction_id' => null,
            ]);

            $plan = $locked->installmentPlan;
            if ($plan->status === 'completed') {
                $plan->update(['status' => 'active']);
            }

            $this->activityLog->log(
                module: 'credit',
                action: 'reset_pending',
                description: "Mengembalikan status cicilan ke-{$locked->installment_number} \"{$plan->name}\" menjadi menunggu",
                space: $plan->space,
                actor: $actor,
                subject: $plan,
                subjectLabel: $plan->name,
                properties: [
                    'installment_number' => $locked->installment_number,
                ],
            );

            return $locked;
        });
    }

    public function recordCreditDebitNote(Space $space, User $actor, array $data): CreditDebitNote
    {
        $creditAccount = CreditAccount::where('space_id', $space->id)
            ->where('id', $data['credit_account_id'])
            ->first();

        if (! $creditAccount) {
            throw ValidationException::withMessages([
                'credit_account_id' => 'Akun kredit tidak ditemukan.',
            ]);
        }

        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal nota harus lebih dari 0.',
            ]);
        }

        $type = $data['type'] ?? 'credit_note';
        if (! in_array($type, ['credit_note', 'debit_note'])) {
            throw ValidationException::withMessages([
                'type' => 'Tipe nota harus berupa credit_note atau debit_note.',
            ]);
        }

        return DB::transaction(function () use ($space, $actor, $creditAccount, $type, $amount, $data) {
            $installment = null;
            $installmentPlan = null;

            if (! empty($data['installment_id'])) {
                $installment = Installment::where('space_id', $space->id)
                    ->where('id', $data['installment_id'])
                    ->lockForUpdate()
                    ->first();

                if ($installment) {
                    $installmentPlan = $installment->installmentPlan;
                }
            } elseif (! empty($data['installment_plan_id'])) {
                $installmentPlan = InstallmentPlan::where('space_id', $space->id)
                    ->where('id', $data['installment_plan_id'])
                    ->first();
            }

            $sourceAccount = null;
            if (! empty($data['source_account_id'])) {
                $sourceAccount = Account::where('space_id', $space->id)
                    ->where('id', $data['source_account_id'])
                    ->first();
            }

            $description = $data['description'] ?? ($type === 'credit_note' ? 'Nota Kredit' : 'Nota Debit');
            $noteDate = $data['note_date'] ?? now()->toDateString();

            $transaction = FinancialTransaction::create([
                'space_id' => $space->id,
                'type' => $sourceAccount ? 'expense' : 'adjustment',
                'transaction_date' => $noteDate,
                'transaction_time' => now()->format('H:i:s'),
                'description' => ($type === 'credit_note' ? 'Nota Kredit: ' : 'Nota Debit: ').$description,
                'category_id' => $data['category_id'] ?? null,
                'member_id' => $installmentPlan?->member_id,
                'status' => 'posted',
                'created_by' => $actor->id,
                'version' => 1,
            ]);

            if ($type === 'credit_note') {
                if ($sourceAccount) {
                    $transaction->entries()->create([
                        'account_id' => $sourceAccount->id,
                        'amount_signed' => $amount,
                    ]);
                } elseif ($creditAccount->account_id) {
                    $transaction->entries()->create([
                        'account_id' => $creditAccount->account_id,
                        'amount_signed' => $amount,
                    ]);
                }

                if ($installment && ! $installment->isPaid()) {
                    $newPrincipal = max(0, $installment->principal_amount - $amount);
                    $installment->update([
                        'principal_amount' => $newPrincipal,
                        'total_amount' => $newPrincipal + (int) $installment->interest_amount + (int) $installment->fee_amount,
                    ]);
                    $installmentPlan?->update([
                        'total_amount' => (int) $installmentPlan->installments()->sum('principal_amount'),
                    ]);
                }
            } else {
                if ($sourceAccount) {
                    $transaction->entries()->create([
                        'account_id' => $sourceAccount->id,
                        'amount_signed' => -$amount,
                    ]);
                } elseif ($creditAccount->account_id) {
                    $transaction->entries()->create([
                        'account_id' => $creditAccount->account_id,
                        'amount_signed' => -$amount,
                    ]);
                }

                if ($installment && ! $installment->isPaid()) {
                    $newPrincipal = $installment->principal_amount + $amount;
                    $installment->update([
                        'principal_amount' => $newPrincipal,
                        'total_amount' => $newPrincipal + (int) $installment->interest_amount + (int) $installment->fee_amount,
                    ]);
                    $installmentPlan?->update([
                        'total_amount' => (int) $installmentPlan->installments()->sum('principal_amount'),
                    ]);
                }
            }

            $note = new CreditDebitNote([
                'space_id' => $space->id,
                'credit_account_id' => $creditAccount->id,
                'installment_plan_id' => $installmentPlan?->id,
                'installment_id' => $installment?->id,
                'financial_transaction_id' => $transaction->id,
                'type' => $type,
                'amount' => $amount,
                'note_date' => $noteDate,
                'description' => $description,
            ]);

            if (Schema::hasTable('credit_debit_notes')) {
                $note->save();
            }

            $this->activityLog->log(
                module: 'credit',
                action: 'note_added',
                description: 'Mencatat '.($type === 'credit_note' ? 'nota kredit' : 'nota debit')." pada akun \"{$creditAccount->name}\" sebesar ".$this->activityLog->formatCurrency($amount),
                space: $space,
                actor: $actor,
                subject: $creditAccount,
                subjectLabel: $creditAccount->name,
                properties: [
                    'note_type' => $type,
                    'amount' => $amount,
                    'note_date' => $noteDate,
                    'description' => $description,
                ],
            );

            return $note;
        });
    }

    public function payCreditAccountBill(Space $space, User $actor, CreditAccount $creditAccount, array $data): CreditPayment
    {
        if ($creditAccount->space_id !== $space->id) {
            throw ValidationException::withMessages([
                'credit_account' => 'Akun kredit bukan milik ruang aktif.',
            ]);
        }

        if ($creditAccount->isArchived()) {
            throw ValidationException::withMessages([
                'credit_account' => 'Akun kredit telah diarsipkan.',
            ]);
        }

        $currentLiability = $creditAccount->currentLiability();
        if ($currentLiability <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Akun kredit tidak memiliki kewajiban hutang yang perlu dibayar.',
            ]);
        }

        $paymentAmount = round((float) $data['amount'], 2);
        if ($paymentAmount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran harus lebih dari 0.',
            ]);
        }

        if ($paymentAmount > $currentLiability) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran melebihi total kewajiban ('.$creditAccount->formatted_liability.').',
            ]);
        }

        $sourceAccount = Account::where('space_id', $space->id)
            ->where('id', $data['source_account_id'])
            ->first();

        if (! $sourceAccount || $sourceAccount->isArchived()) {
            throw ValidationException::withMessages([
                'source_account_id' => 'Rekening sumber dana tidak valid atau telah diarsipkan.',
            ]);
        }

        if (! in_array($sourceAccount->type, ['bank', 'cash', 'e-wallet'])) {
            throw ValidationException::withMessages([
                'source_account_id' => 'Rekening sumber dana harus berupa rekening kas, bank, atau e-wallet.',
            ]);
        }

        return DB::transaction(function () use ($space, $actor, $creditAccount, $sourceAccount, $paymentAmount, $data) {
            $accountIds = [$sourceAccount->id, $creditAccount->account_id];
            sort($accountIds);
            Account::whereIn('id', $accountIds)->lockForUpdate()->get();

            $transaction = FinancialTransaction::create([
                'space_id' => $space->id,
                'type' => 'credit_payment',
                'transaction_date' => $data['payment_date'],
                'transaction_time' => ! empty($data['transaction_time']) ? $data['transaction_time'] : now()->format('H:i:s'),
                'description' => 'Pembayaran Tagihan Kartu/Kredit: '.$creditAccount->name,
                'category_id' => null,
                'member_id' => null,
                'status' => 'posted',
                'created_by' => $actor->id,
                'version' => 1,
            ]);

            $transaction->entries()->create([
                'account_id' => $sourceAccount->id,
                'amount_signed' => -$paymentAmount,
            ]);

            $transaction->entries()->create([
                'account_id' => $creditAccount->account_id,
                'amount_signed' => $paymentAmount,
            ]);

            $targetInstallments = collect();
            if (! empty($data['installment_ids']) && is_array($data['installment_ids'])) {
                $targetInstallments = Installment::whereHas('installmentPlan', function ($q) use ($creditAccount) {
                    $q->where('credit_account_id', $creditAccount->id)->where('status', 'active');
                })->whereIn('id', $data['installment_ids'])
                    ->where('status', 'pending')
                    ->orderBy('due_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();
            }

            if ($targetInstallments->isNotEmpty()) {
                $totalTargetPrincipal = (int) $targetInstallments->sum('principal_amount');
                $diff = $paymentAmount - $totalTargetPrincipal;

                foreach ($targetInstallments as $inst) {
                    $inst->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'payment_transaction_id' => $transaction->id,
                    ]);
                    $inst->installmentPlan->checkCompletion();
                }

                if ($diff !== 0 && Schema::hasTable('credit_debit_notes')) {
                    $noteTx = FinancialTransaction::create([
                        'space_id' => $space->id,
                        'type' => 'adjustment',
                        'transaction_date' => $data['payment_date'],
                        'transaction_time' => ! empty($data['transaction_time']) ? $data['transaction_time'] : now()->format('H:i:s'),
                        'description' => ($diff < 0 ? 'Nota Kredit (Selisih Pembayaran Tagihan): ' : 'Nota Debit (Biaya/Penyesuaian Tagihan): ').$creditAccount->name,
                        'category_id' => null,
                        'member_id' => null,
                        'status' => 'posted',
                        'created_by' => $actor->id,
                        'version' => 1,
                    ]);

                    if ($creditAccount->account_id) {
                        $noteTx->entries()->create([
                            'account_id' => $creditAccount->account_id,
                            'amount_signed' => $diff < 0 ? abs($diff) : -$diff,
                        ]);
                    }

                    CreditDebitNote::create([
                        'space_id' => $space->id,
                        'credit_account_id' => $creditAccount->id,
                        'installment_plan_id' => null,
                        'installment_id' => null,
                        'financial_transaction_id' => $noteTx->id,
                        'type' => $diff < 0 ? 'credit_note' : 'debit_note',
                        'amount' => abs($diff),
                        'note_date' => $data['payment_date'],
                        'description' => ($diff < 0 ? 'Nota Kredit (Selisih Pembayaran Tagihan) ' : 'Nota Debit (Biaya/Penyesuaian Tagihan) ').$creditAccount->name,
                    ]);
                }
            } else {
                $remainingToAllocate = $paymentAmount;
                $pendingInstallments = Installment::whereHas('installmentPlan', function ($q) use ($creditAccount) {
                    $q->where('credit_account_id', $creditAccount->id)->where('status', 'active');
                })->where('status', 'pending')->orderBy('due_date', 'asc')->orderBy('id', 'asc')->lockForUpdate()->get();

                foreach ($pendingInstallments as $inst) {
                    if ($remainingToAllocate <= 0) {
                        break;
                    }
                    if ($remainingToAllocate >= $inst->principal_amount) {
                        $inst->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                            'payment_transaction_id' => $transaction->id,
                        ]);
                        $remainingToAllocate -= $inst->principal_amount;
                        $inst->installmentPlan->checkCompletion();
                    }
                }
            }

            $payment = CreditPayment::create([
                'space_id' => $space->id,
                'credit_account_id' => $creditAccount->id,
                'installment_id' => null,
                'source_account_id' => $sourceAccount->id,
                'transaction_id' => $transaction->id,
                'principal_amount' => $paymentAmount,
                'interest_amount' => 0,
                'fee_amount' => 0,
                'total_amount' => $paymentAmount,
                'payment_date' => $data['payment_date'],
                'notes' => $data['notes'] ?? 'Pembayaran Tagihan Akun Kredit',
            ]);

            if (empty($data['skip_activity_log'])) {
                $this->activityLog->log(
                    module: 'credit',
                    action: 'paid',
                    description: "Membayar tagihan akun kredit \"{$creditAccount->name}\" sebesar ".$this->activityLog->formatCurrency($paymentAmount),
                    space: $space,
                    actor: $actor,
                    subject: $creditAccount,
                    subjectLabel: $creditAccount->name,
                    properties: [
                        'amount' => $paymentAmount,
                        'source_account' => $sourceAccount->name,
                        'payment_date' => $data['payment_date'],
                    ],
                );
            }

            return $payment;
        });
    }
}
