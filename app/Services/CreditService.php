<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\CreditPayment;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Space;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditService
{
    public function createCreditAccount(Space $space, array $data): CreditAccount
    {
        return DB::transaction(function () use ($space, $data) {
            $ledgerAccount = Account::create([
                'space_id' => $space->id,
                'name' => $data['name'] . ' (Kewajiban)',
                'type' => $data['type'] ?? 'credit_card',
                'opening_balance' => 0,
                'opened_on' => now()->toDateString(),
                'is_default' => false,
            ]);

            return CreditAccount::create([
                'space_id' => $space->id,
                'account_id' => $ledgerAccount->id,
                'name' => $data['name'],
                'type' => $data['type'] ?? 'credit_card',
                'credit_limit' => (int) ($data['credit_limit'] ?? 0),
                'billing_cycle_day' => ! empty($data['billing_cycle_day']) ? (int) $data['billing_cycle_day'] : null,
                'due_day' => ! empty($data['due_day']) ? (int) $data['due_day'] : null,
            ]);
        });
    }

    public function updateCreditAccount(CreditAccount $creditAccount, array $data): CreditAccount
    {
        return DB::transaction(function () use ($creditAccount, $data) {
            $creditAccount->update([
                'name' => $data['name'],
                'type' => $data['type'] ?? $creditAccount->type,
                'credit_limit' => (int) ($data['credit_limit'] ?? 0),
                'billing_cycle_day' => ! empty($data['billing_cycle_day']) ? (int) $data['billing_cycle_day'] : null,
                'due_day' => ! empty($data['due_day']) ? (int) $data['due_day'] : null,
            ]);

            if ($creditAccount->ledgerAccount) {
                $creditAccount->ledgerAccount->update([
                    'name' => $data['name'] . ' (Kewajiban)',
                    'type' => $data['type'] ?? $creditAccount->type,
                ]);
            }

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

        $totalAmount = (int) $data['total_amount'];
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
                    'total_amount' => 'Nominal pembelian melebihi sisa limit kredit yang tersedia (' . $lockedCreditAccount->formatted_available_limit . ').',
                ]);
            }

            $transaction = FinancialTransaction::create([
                'space_id' => $space->id,
                'type' => 'expense',
                'transaction_date' => $data['start_date'],
                'transaction_time' => ! empty($data['transaction_time']) ? $data['transaction_time'] : now()->format('H:i:s'),
                'description' => 'Pembelian Kredit: ' . $data['name'] . ' (' . $lockedCreditAccount->name . ')',
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

            $plan = InstallmentPlan::create([
                'space_id' => $space->id,
                'credit_account_id' => $lockedCreditAccount->id,
                'financial_transaction_id' => $transaction->id,
                'category_id' => $category->id,
                'member_id' => $data['member_id'] ?? null,
                'name' => $data['name'],
                'total_amount' => $totalAmount,
                'tenor' => $tenor,
                'interest_rate' => $data['interest_rate'] ?? 0,
                'start_date' => $data['start_date'],
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
            ]);

            $basePrincipal = intdiv($totalAmount, $tenor);
            $remainder = $totalAmount % $tenor;

            $startDate = Carbon::parse($data['start_date']);
            $firstDueDate = ! empty($data['first_due_date'])
                ? Carbon::parse($data['first_due_date'])
                : ($lockedCreditAccount->due_day
                    ? $startDate->copy()->addMonth()->setDay(min($lockedCreditAccount->due_day, $startDate->copy()->addMonth()->daysInMonth))
                    : $startDate->copy()->addMonth());

            for ($i = 1; $i <= $tenor; $i++) {
                $principal = $basePrincipal + ($i === 1 ? $remainder : 0);
                $dueDate = $firstDueDate->copy()->addMonths($i - 1);

                Installment::create([
                    'space_id' => $space->id,
                    'installment_plan_id' => $plan->id,
                    'installment_number' => $i,
                    'due_date' => $dueDate->toDateString(),
                    'principal_amount' => $principal,
                    'interest_amount' => 0,
                    'fee_amount' => 0,
                    'total_amount' => $principal,
                    'status' => 'pending',
                ]);
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

        $paymentAmount = isset($data['amount']) ? (int) $data['amount'] : (int) $installment->principal_amount;

        if ($paymentAmount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran harus lebih dari 0.',
            ]);
        }

        if ($paymentAmount > $installment->principal_amount) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran tidak boleh melebihi sisa pokok cicilan (' . $installment->formatted_principal . ').',
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

            $accountIds = [$sourceAccount->id, $creditAccount->account_id];
            sort($accountIds);
            Account::whereIn('id', $accountIds)->lockForUpdate()->get();

            $transaction = FinancialTransaction::create([
                'space_id' => $space->id,
                'type' => 'credit_payment',
                'transaction_date' => $data['payment_date'],
                'transaction_time' => ! empty($data['transaction_time']) ? $data['transaction_time'] : now()->format('H:i:s'),
                'description' => 'Pembayaran Cicilan #' . $lockedInstallment->installment_number . ' - ' . $lockedInstallment->installmentPlan->name,
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

            $lockedInstallment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_transaction_id' => $transaction->id,
            ]);

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
                'notes' => $data['notes'] ?? null,
            ]);

            $lockedInstallment->installmentPlan->checkCompletion();

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
        });
    }
}
