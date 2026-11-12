<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\RecurringOccurrence;
use App\Models\RecurringSchedule;
use App\Models\Space;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecurringService
{
    public function __construct(
        protected TransactionService $transactionService,
        protected CreditService $creditService,
        protected ActivityLogService $activityLog
    ) {}

    public function createSchedule(Space $space, User $actor, array $data): RecurringSchedule
    {
        $type = $data['type'] ?? 'expense';
        $startDate = Carbon::parse($data['start_date']);
        $anchorDay = (int) $startDate->day;

        $sourceAccount = null;
        $creditAccount = null;

        if (! empty($data['source_account_id'])) {
            $sourceAccount = Account::where('space_id', $space->id)
                ->where('id', $data['source_account_id'])
                ->with('creditAccount')
                ->first();

            if ($type === 'transfer') {
                if (! $sourceAccount || $sourceAccount->isArchived() || $sourceAccount->isLiability()) {
                    throw ValidationException::withMessages([
                        'source_account_id' => 'Rekening sumber transfer harus berupa akun kas, bank, e-wallet, atau investasi aktif.',
                    ]);
                }
            } elseif ($type === 'credit_payment') {
                if (! $sourceAccount || $sourceAccount->isArchived() || ! in_array($sourceAccount->type, ['bank', 'cash', 'e-wallet'], true)) {
                    throw ValidationException::withMessages([
                        'source_account_id' => 'Rekening pembayar tagihan kredit harus berupa akun kas, bank, atau e-wallet aktif.',
                    ]);
                }
            } else {
                if (! $sourceAccount || $sourceAccount->isArchived()) {
                    throw ValidationException::withMessages([
                        'source_account_id' => 'Rekening sumber tidak valid atau telah diarsipkan.',
                    ]);
                }

                if ($sourceAccount->isCreditSpendAccount()) {
                    $creditAccount = $sourceAccount->creditAccount;
                    if (! $creditAccount || $creditAccount->isArchived()) {
                        throw ValidationException::withMessages([
                            'source_account_id' => 'Akun Kartu Kredit atau PayLater yang dipilih tidak valid atau telah diarsipkan.',
                        ]);
                    }
                } elseif ($sourceAccount->isLiability()) {
                    throw ValidationException::withMessages([
                        'source_account_id' => 'Rekening sumber harus berupa kas, bank, e-wallet, investasi, atau kartu kredit/paylater aktif.',
                    ]);
                }
            }
        } elseif ($type === 'transfer') {
            throw ValidationException::withMessages([
                'source_account_id' => 'Rekening sumber transfer wajib dipilih.',
            ]);
        }

        $destAccount = null;
        $category = null;

        if ($type === 'transfer') {
            $destAccountId = (int) ($data['destination_account_id'] ?? 0);
            if ($destAccountId === (int) $sourceAccount?->id) {
                throw ValidationException::withMessages([
                    'destination_account_id' => 'Rekening tujuan transfer harus berbeda dengan rekening asal.',
                ]);
            }

            $destAccount = Account::where('space_id', $space->id)
                ->where('id', $destAccountId)
                ->first();

            if (! $destAccount || $destAccount->isArchived() || $destAccount->isLiability()) {
                throw ValidationException::withMessages([
                    'destination_account_id' => 'Rekening tujuan transfer harus berupa akun kas, bank, atau e-wallet aktif.',
                ]);
            }
        } elseif ($type === 'credit_payment') {
            $creditAccountId = (int) ($data['credit_account_id'] ?? 0);
            $creditAccount = CreditAccount::where('space_id', $space->id)
                ->where('id', $creditAccountId)
                ->active()
                ->first();

            if (! $creditAccount) {
                throw ValidationException::withMessages([
                    'credit_account_id' => 'Akun kartu kredit atau paylater tujuan tidak valid atau telah diarsipkan.',
                ]);
            }
        } else {
            $categoryId = (int) ($data['category_id'] ?? 0);
            $category = Category::where('space_id', $space->id)
                ->where('id', $categoryId)
                ->expense()
                ->active()
                ->first();

            if (! $category) {
                throw ValidationException::withMessages([
                    'category_id' => 'Kategori pengeluaran tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
                ]);
            }
        }

        $memberId = ! empty($data['member_id']) ? (int) $data['member_id'] : null;
        if ($memberId) {
            $member = Member::where('space_id', $space->id)
                ->where('id', $memberId)
                ->active()
                ->first();

            if (! $member) {
                throw ValidationException::withMessages([
                    'member_id' => 'Member yang dipilih tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
                ]);
            }
        }

        $isVariable = ! empty($data['is_variable_amount']);
        $amount = $isVariable ? null : (int) ($data['amount'] ?? 0);

        if (! $isVariable && $amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal tagihan harus lebih dari 0 jika tidak diset fleksibel.',
            ]);
        }

        return DB::transaction(function () use ($space, $actor, $data, $type, $startDate, $anchorDay, $sourceAccount, $destAccount, $creditAccount, $category, $memberId, $isVariable, $amount) {
            $schedule = RecurringSchedule::create([
                'space_id' => $space->id,
                'type' => $type,
                'name' => $data['name'],
                'member_id' => $memberId,
                'category_id' => $category?->id,
                'source_account_id' => $sourceAccount?->id,
                'destination_account_id' => $destAccount?->id,
                'credit_account_id' => $creditAccount?->id,
                'frequency' => $data['frequency'],
                'anchor_day' => $anchorDay,
                'start_date' => $startDate->toDateString(),
                'end_date' => ! empty($data['end_date']) ? Carbon::parse($data['end_date'])->toDateString() : null,
                'next_due_date' => $startDate->toDateString(),
                'amount' => $amount,
                'is_variable_amount' => $isVariable,
                'description' => $data['description'] ?? null,
                'status' => 'active',
                'created_by' => $actor->id,
            ]);

            RecurringOccurrence::firstOrCreate([
                'recurring_schedule_id' => $schedule->id,
                'due_date' => $schedule->next_due_date->toDateString(),
            ], [
                'space_id' => $space->id,
                'status' => 'pending',
                'amount' => $amount,
            ]);

            $this->activityLog->log(
                module: 'recurring',
                action: 'created',
                description: "Membuat jadwal berulang \"{$schedule->name}\""
                    .($schedule->amount ? ' ('.$this->activityLog->formatCurrency($schedule->amount).')' : ' (nominal fleksibel)'),
                space: $space,
                actor: $actor,
                subject: $schedule,
                subjectLabel: $schedule->name,
                properties: [
                    'type' => $schedule->type,
                    'frequency' => $schedule->frequency,
                    'amount' => $schedule->amount,
                    'is_variable_amount' => (bool) $schedule->is_variable_amount,
                    'next_due_date' => $schedule->next_due_date->toDateString(),
                ],
            );

            return $schedule;
        });
    }

    public function updateSchedule(RecurringSchedule $schedule, User $actor, array $data): RecurringSchedule
    {
        $space = $schedule->space;
        $type = $schedule->type;
        $before = [
            'name' => $schedule->name,
            'amount' => $schedule->amount,
            'frequency' => $schedule->frequency,
            'next_due_date' => $schedule->next_due_date?->toDateString(),
            'status' => $schedule->status,
        ];

        $sourceAccount = null;
        $creditAccount = null;

        if (! empty($data['source_account_id'])) {
            $sourceAccount = Account::where('space_id', $space->id)
                ->where('id', $data['source_account_id'])
                ->with('creditAccount')
                ->first();

            $isSameExistingSource = (int) $schedule->source_account_id === (int) ($sourceAccount?->id);

            if ($type === 'transfer') {
                if (! $sourceAccount || (($sourceAccount->isArchived() || $sourceAccount->isLiability()) && ! $isSameExistingSource)) {
                    throw ValidationException::withMessages([
                        'source_account_id' => 'Rekening sumber transfer harus berupa akun kas, bank, e-wallet, atau investasi aktif.',
                    ]);
                }
            } elseif ($type === 'credit_payment') {
                if (! $sourceAccount || (($sourceAccount->isArchived() || ! in_array($sourceAccount->type, ['bank', 'cash', 'e-wallet'], true)) && ! $isSameExistingSource)) {
                    throw ValidationException::withMessages([
                        'source_account_id' => 'Rekening pembayar tagihan kredit harus berupa akun kas, bank, atau e-wallet aktif.',
                    ]);
                }
            } else {
                if (! $sourceAccount || ($sourceAccount->isArchived() && ! $isSameExistingSource)) {
                    throw ValidationException::withMessages([
                        'source_account_id' => 'Rekening sumber tidak valid atau telah diarsipkan.',
                    ]);
                }

                if ($sourceAccount->isCreditSpendAccount()) {
                    $creditAccount = $sourceAccount->creditAccount;
                    if ((! $creditAccount || $creditAccount->isArchived()) && ! $isSameExistingSource) {
                        throw ValidationException::withMessages([
                            'source_account_id' => 'Akun Kartu Kredit atau PayLater yang dipilih tidak valid atau telah diarsipkan.',
                        ]);
                    }
                    if ($creditAccount && $creditAccount->isArchived() && ! $isSameExistingSource) {
                        $creditAccount = null;
                    }
                } elseif ($sourceAccount->isLiability() && ! $isSameExistingSource) {
                    throw ValidationException::withMessages([
                        'source_account_id' => 'Rekening sumber harus berupa kas, bank, e-wallet, investasi, atau kartu kredit/paylater aktif.',
                    ]);
                }
            }
        } elseif ($type === 'transfer') {
            throw ValidationException::withMessages([
                'source_account_id' => 'Rekening sumber transfer wajib dipilih.',
            ]);
        }

        $destAccount = null;
        $category = null;

        if ($type === 'transfer') {
            $destAccountId = (int) ($data['destination_account_id'] ?? 0);
            if ($destAccountId === (int) $sourceAccount?->id) {
                throw ValidationException::withMessages([
                    'destination_account_id' => 'Rekening tujuan transfer harus berbeda dengan rekening asal.',
                ]);
            }

            $destAccount = Account::where('space_id', $space->id)
                ->where('id', $destAccountId)
                ->first();

            $isSameExistingDest = (int) $schedule->destination_account_id === (int) ($destAccount?->id);
            if (! $destAccount || (($destAccount->isArchived() || $destAccount->isLiability()) && ! $isSameExistingDest)) {
                throw ValidationException::withMessages([
                    'destination_account_id' => 'Rekening tujuan transfer harus berupa akun kas, bank, atau e-wallet aktif.',
                ]);
            }
        } elseif ($type === 'credit_payment') {
            $creditAccountId = (int) ($data['credit_account_id'] ?? 0);
            $isSameExistingCredit = (int) $schedule->credit_account_id === $creditAccountId;

            $creditAccount = CreditAccount::where('space_id', $space->id)
                ->where('id', $creditAccountId)
                ->where(function ($q) use ($isSameExistingCredit) {
                    $q->whereNull('archived_at');
                    if ($isSameExistingCredit) {
                        $q->orWhereNotNull('archived_at');
                    }
                })
                ->first();

            if (! $creditAccount) {
                throw ValidationException::withMessages([
                    'credit_account_id' => 'Akun kartu kredit atau paylater tujuan tidak valid atau telah diarsipkan.',
                ]);
            }
        } else {
            $categoryId = (int) ($data['category_id'] ?? 0);
            $category = Category::where('space_id', $space->id)
                ->where('id', $categoryId)
                ->expense()
                ->where(function ($q) use ($schedule) {
                    $q->whereNull('archived_at');
                    if ($schedule->category_id) {
                        $q->orWhere('id', $schedule->category_id);
                    }
                })
                ->first();

            if (! $category) {
                throw ValidationException::withMessages([
                    'category_id' => 'Kategori pengeluaran tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
                ]);
            }
        }

        $memberId = ! empty($data['member_id']) ? (int) $data['member_id'] : null;
        if ($memberId) {
            $member = Member::where('space_id', $space->id)
                ->where('id', $memberId)
                ->where(function ($q) use ($schedule) {
                    $q->whereNull('archived_at');
                    if ($schedule->member_id) {
                        $q->orWhere('id', $schedule->member_id);
                    }
                })
                ->first();

            if (! $member) {
                throw ValidationException::withMessages([
                    'member_id' => 'Member yang dipilih tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
                ]);
            }
        }

        $isVariable = ! empty($data['is_variable_amount']);
        $amount = $isVariable ? null : (int) ($data['amount'] ?? 0);

        if (! $isVariable && $amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal tagihan harus lebih dari 0 jika tidak diset fleksibel.',
            ]);
        }

        $oldStartDate = $schedule->start_date ? Carbon::parse($schedule->start_date)->toDateString() : null;
        $oldFrequency = $schedule->frequency;
        $oldNextDue = $schedule->next_due_date ? Carbon::parse($schedule->next_due_date)->toDateString() : null;

        $startDate = Carbon::parse($data['start_date']);
        $anchorDay = (int) $startDate->day;
        $newFrequency = $data['frequency'];
        $endDateStr = ! empty($data['end_date']) ? Carbon::parse($data['end_date'])->toDateString() : null;

        $newNextDueDate = $oldNextDue ?: $startDate->toDateString();

        $explicitNextDue = ! empty($data['next_due_date']) ? Carbon::parse($data['next_due_date'])->toDateString() : null;
        if ($explicitNextDue && $explicitNextDue !== $oldNextDue) {
            if (Carbon::parse($explicitNextDue)->lt($startDate)) {
                throw ValidationException::withMessages([
                    'next_due_date' => 'Tanggal jatuh tempo berikutnya tidak boleh sebelum tanggal mulai.',
                ]);
            }
            $newNextDueDate = $explicitNextDue;
        } elseif ($oldStartDate !== $startDate->toDateString() || $oldFrequency !== $newFrequency) {
            $hasHistory = $schedule->occurrences()
                ->whereIn('status', ['confirmed', 'skipped'])
                ->exists();

            if (! $hasHistory || ! $oldNextDue || Carbon::parse($oldNextDue)->lt($startDate)) {
                $newNextDueDate = $startDate->toDateString();
            } elseif ($newFrequency === 'monthly') {
                $targetMonth = Carbon::parse($oldNextDue)->startOfMonth();
                $day = min($anchorDay, (int) $targetMonth->daysInMonth);
                $candidate = $targetMonth->day($day);
                if ($candidate->lt($startDate)) {
                    $candidate = $startDate->copy();
                }
                $newNextDueDate = $candidate->toDateString();
            } elseif ($newFrequency === 'yearly') {
                $targetYear = Carbon::parse($oldNextDue)->year;
                $candidate = Carbon::createFromDate($targetYear, $startDate->month, 1);
                $candidate->day(min($anchorDay, (int) $candidate->daysInMonth));
                if ($candidate->lt($startDate)) {
                    $candidate = $startDate->copy();
                }
                $newNextDueDate = $candidate->toDateString();
            } else {
                $newNextDueDate = $startDate->toDateString();
            }
        }

        $newStatus = $data['status'] ?? $schedule->status;
        $archivedAt = in_array($newStatus, ['archived', 'cancelled'], true)
            ? ($schedule->archived_at ?? now())
            : null;

        return DB::transaction(function () use ($schedule, $space, $actor, $before, $data, $memberId, $category, $sourceAccount, $destAccount, $creditAccount, $newFrequency, $anchorDay, $startDate, $endDateStr, $newNextDueDate, $amount, $isVariable, $newStatus, $archivedAt, $oldNextDue, $oldStartDate, $oldFrequency) {
            $schedule->update([
                'name' => $data['name'],
                'member_id' => $memberId,
                'category_id' => $category?->id,
                'source_account_id' => $sourceAccount?->id,
                'destination_account_id' => $destAccount?->id,
                'credit_account_id' => $creditAccount?->id,
                'frequency' => $newFrequency,
                'anchor_day' => $anchorDay,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDateStr,
                'next_due_date' => $newNextDueDate,
                'amount' => $amount,
                'is_variable_amount' => $isVariable,
                'description' => $data['description'] ?? null,
                'status' => $newStatus,
                'archived_at' => $archivedAt,
            ]);

            $datesChanged = $oldNextDue !== $newNextDueDate
                || $oldStartDate !== $startDate->toDateString()
                || $oldFrequency !== $newFrequency;

            if ($datesChanged) {
                $schedule->occurrences()
                    ->where('status', 'pending')
                    ->whereNull('transaction_id')
                    ->delete();
            } else {
                $schedule->occurrences()
                    ->where('status', 'pending')
                    ->whereNull('transaction_id')
                    ->update(['amount' => $amount]);
            }

            if ($newStatus === 'active' && (! $endDateStr || $newNextDueDate <= $endDateStr)) {
                RecurringOccurrence::firstOrCreate([
                    'recurring_schedule_id' => $schedule->id,
                    'due_date' => $newNextDueDate,
                ], [
                    'space_id' => $schedule->space_id,
                    'status' => 'pending',
                    'amount' => $amount,
                ]);
            }

            $diff = $this->activityLog->buildDiff($before, [
                'name' => $schedule->name,
                'amount' => $schedule->amount,
                'frequency' => $schedule->frequency,
                'next_due_date' => $schedule->next_due_date?->toDateString(),
                'status' => $schedule->status,
            ], [
                'name' => 'Nama Jadwal',
                'amount' => 'Nominal',
                'frequency' => 'Frekuensi',
                'next_due_date' => 'Jatuh Tempo Berikutnya',
                'status' => 'Status',
            ]);

            $this->activityLog->log(
                module: 'recurring',
                action: 'updated',
                description: "Memperbarui jadwal berulang \"{$schedule->name}\"",
                space: $space,
                actor: $actor,
                subject: $schedule,
                subjectLabel: $schedule->name,
                properties: $diff,
            );

            return $schedule;
        });
    }

    public function confirmOccurrence(RecurringSchedule $schedule, string $dueDate, User $actor, array $overrideData = []): FinancialTransaction
    {
        return DB::transaction(function () use ($schedule, $dueDate, $actor, $overrideData) {
            $lockedSchedule = RecurringSchedule::where('id', $schedule->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedSchedule->isActive()) {
                throw ValidationException::withMessages([
                    'schedule' => 'Jadwal berulang tidak dalam status aktif dan tidak dapat dicatat.',
                ]);
            }

            $space = $lockedSchedule->space;

            $dueDateStr = Carbon::parse($dueDate)->format('Y-m-d');

            $occurrence = RecurringOccurrence::where('recurring_schedule_id', $lockedSchedule->id)
                ->where(function ($q) use ($dueDateStr) {
                    $q->where('due_date', $dueDateStr)
                        ->orWhere('due_date', 'like', "{$dueDateStr}%");
                })
                ->lockForUpdate()
                ->first();

            if ($occurrence && $occurrence->status === 'confirmed') {
                throw ValidationException::withMessages([
                    'due_date' => 'Jadwal berulang periode ini sudah dicatat sebelumnya.',
                ]);
            }

            $amount = (int) ($overrideData['amount'] ?? $lockedSchedule->amount);
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal transaksi harus lebih dari 0.',
                ]);
            }

            $sourceAccountId = ! empty($overrideData['source_account_id'])
                ? (int) $overrideData['source_account_id']
                : (int) ($lockedSchedule->source_account_id ?? 0);

            if (! $sourceAccountId) {
                throw ValidationException::withMessages([
                    'source_account_id' => 'Silakan pilih rekening pembayaran untuk transaksi ini.',
                ]);
            }

            $sourceAccount = Account::where('space_id', $space->id)
                ->where('id', $sourceAccountId)
                ->with('creditAccount')
                ->first();

            if (! $sourceAccount || $sourceAccount->isArchived()) {
                throw ValidationException::withMessages([
                    'source_account_id' => 'Rekening sumber tidak valid atau telah diarsipkan.',
                ]);
            }

            $txDate = $overrideData['transaction_date'] ?? $dueDate;
            $description = ! empty($overrideData['description'])
                ? $overrideData['description']
                : $lockedSchedule->name;

            if ($lockedSchedule->isExpense()) {
                $categoryId = ! empty($overrideData['category_id'])
                    ? (int) $overrideData['category_id']
                    : (int) $lockedSchedule->category_id;

                if ($sourceAccount->isCreditSpendAccount()) {
                    $creditAccount = $sourceAccount->creditAccount;
                    if (! $creditAccount || $creditAccount->isArchived()) {
                        throw ValidationException::withMessages([
                            'source_account_id' => 'Akun Kartu Kredit atau PayLater yang dipilih tidak valid atau telah diarsipkan.',
                        ]);
                    }

                    try {
                        $plan = $this->creditService->recordCreditPurchase($space, $actor, [
                            'credit_account_id' => $creditAccount->id,
                            'category_id' => $categoryId,
                            'member_id' => $lockedSchedule->member_id,
                            'name' => $description,
                            'total_amount' => $amount,
                            'tenor' => 1,
                            'start_date' => $txDate,
                            'transaction_time' => $overrideData['transaction_time'] ?? now()->format('H:i:s'),
                            'notes' => $overrideData['notes'] ?? "Dicatat dari Jadwal Berulang: {$lockedSchedule->name}",
                            'skip_activity_log' => true,
                        ]);
                    } catch (ValidationException $e) {
                        $errors = $e->errors();
                        if (isset($errors['total_amount']) && ! isset($errors['amount'])) {
                            $errors['amount'] = $errors['total_amount'];
                            unset($errors['total_amount']);
                            throw ValidationException::withMessages($errors);
                        }
                        throw $e;
                    }

                    $transaction = $plan->purchaseTransaction;
                } elseif ($sourceAccount->isLoan()) {
                    throw ValidationException::withMessages([
                        'source_account_id' => 'Akun pinjaman tidak dapat digunakan sebagai rekening sumber pengeluaran rutin.',
                    ]);
                } else {
                    $transaction = $this->transactionService->recordExpense($space, $actor, [
                        'account_id' => $sourceAccount->id,
                        'category_id' => $categoryId,
                        'amount' => $amount,
                        'transaction_date' => $txDate,
                        'transaction_time' => $overrideData['transaction_time'] ?? now()->format('H:i:s'),
                        'description' => $description,
                        'member_id' => $lockedSchedule->member_id,
                        'skip_activity_log' => true,
                    ]);
                }
            } elseif ($lockedSchedule->isCreditPayment()) {
                if (! in_array($sourceAccount->type, ['bank', 'cash', 'e-wallet'], true)) {
                    throw ValidationException::withMessages([
                        'source_account_id' => 'Rekening pembayar tagihan kredit harus berupa akun kas, bank, atau e-wallet aktif.',
                    ]);
                }

                $creditAccount = $lockedSchedule->creditAccount;
                if (! $creditAccount || $creditAccount->space_id !== $space->id || $creditAccount->isArchived()) {
                    throw ValidationException::withMessages([
                        'credit_account_id' => 'Akun kartu kredit atau paylater tujuan tidak valid atau telah diarsipkan.',
                    ]);
                }

                $payment = $this->creditService->payCreditAccountBill($space, $actor, $creditAccount, [
                    'source_account_id' => $sourceAccount->id,
                    'amount' => $amount,
                    'payment_date' => $txDate,
                    'transaction_time' => $overrideData['transaction_time'] ?? now()->format('H:i:s'),
                    'notes' => $overrideData['notes'] ?? "Pembayaran terjadwal dari Jadwal Berulang: {$lockedSchedule->name}",
                    'skip_activity_log' => true,
                ]);

                $transaction = $payment->transaction;
            } else {
                if ($sourceAccount->isLiability()) {
                    throw ValidationException::withMessages([
                        'source_account_id' => 'Rekening sumber transfer harus berupa akun kas, bank, e-wallet, atau investasi aktif.',
                    ]);
                }

                $destAccountId = ! empty($overrideData['destination_account_id'])
                    ? (int) $overrideData['destination_account_id']
                    : (int) $lockedSchedule->destination_account_id;

                if ($destAccountId === (int) $sourceAccount->id) {
                    throw ValidationException::withMessages([
                        'destination_account_id' => 'Rekening tujuan transfer harus berbeda dengan rekening asal.',
                    ]);
                }

                $destAccount = Account::where('space_id', $space->id)
                    ->where('id', $destAccountId)
                    ->first();

                if (! $destAccount || $destAccount->isArchived() || $destAccount->isLiability()) {
                    throw ValidationException::withMessages([
                        'destination_account_id' => 'Rekening tujuan transfer harus berupa akun kas, bank, atau e-wallet aktif.',
                    ]);
                }

                $transaction = $this->transactionService->recordTransfer($space, $actor, [
                    'source_account_id' => $sourceAccount->id,
                    'destination_account_id' => $destAccount->id,
                    'amount' => $amount,
                    'transaction_date' => $txDate,
                    'transaction_time' => $overrideData['transaction_time'] ?? now()->format('H:i:s'),
                    'description' => $description,
                    'member_id' => $lockedSchedule->member_id,
                    'skip_activity_log' => true,
                ]);
            }

            if ($occurrence) {
                $occurrence->update([
                    'transaction_id' => $transaction->id,
                    'status' => 'confirmed',
                    'amount' => $amount,
                    'confirmed_at' => now(),
                    'confirmed_by' => $actor->id,
                    'notes' => $overrideData['notes'] ?? null,
                ]);
            } else {
                $occurrence = RecurringOccurrence::create([
                    'recurring_schedule_id' => $lockedSchedule->id,
                    'space_id' => $space->id,
                    'due_date' => $dueDateStr,
                    'transaction_id' => $transaction->id,
                    'status' => 'confirmed',
                    'amount' => $amount,
                    'confirmed_at' => now(),
                    'confirmed_by' => $actor->id,
                    'notes' => $overrideData['notes'] ?? null,
                ]);
            }

            $currentDueDate = Carbon::parse($dueDate)->startOfDay();
            $scheduleNextDue = $lockedSchedule->next_due_date
                ? Carbon::parse($lockedSchedule->next_due_date)->startOfDay()
                : $currentDueDate;

            if ($currentDueDate->gte($scheduleNextDue)) {
                $nextDueDate = $lockedSchedule->calculateNextDueDate($currentDueDate);

                if ($lockedSchedule->end_date && $nextDueDate->gt(Carbon::parse($lockedSchedule->end_date))) {
                    $lockedSchedule->update([
                        'status' => 'completed',
                    ]);
                } else {
                    $lockedSchedule->update([
                        'next_due_date' => $nextDueDate->toDateString(),
                    ]);

                    RecurringOccurrence::firstOrCreate([
                        'recurring_schedule_id' => $lockedSchedule->id,
                        'due_date' => $nextDueDate->toDateString(),
                    ], [
                        'space_id' => $space->id,
                        'status' => 'pending',
                        'amount' => $lockedSchedule->amount,
                    ]);
                }
            }

            $this->activityLog->log(
                module: 'recurring',
                action: 'confirmed',
                description: "Mengonfirmasi jadwal berulang \"{$lockedSchedule->name}\" ({$dueDateStr}) sebesar ".$this->activityLog->formatCurrency($amount),
                space: $space,
                actor: $actor,
                subject: $lockedSchedule,
                subjectLabel: $lockedSchedule->name,
                properties: [
                    'due_date' => $dueDateStr,
                    'transaction_date' => $txDate,
                    'amount' => $amount,
                    'transaction_id' => $transaction->id,
                ],
            );

            return $transaction->load('entries');
        });
    }

    public function skipOccurrence(RecurringSchedule $schedule, string $dueDate, User $actor, ?string $notes = null): RecurringOccurrence
    {
        return DB::transaction(function () use ($schedule, $dueDate, $actor, $notes) {
            $lockedSchedule = RecurringSchedule::where('id', $schedule->id)
                ->lockForUpdate()
                ->firstOrFail();

            $space = $lockedSchedule->space;

            $dueDateStr = Carbon::parse($dueDate)->format('Y-m-d');

            $occurrence = RecurringOccurrence::where('recurring_schedule_id', $lockedSchedule->id)
                ->where(function ($q) use ($dueDateStr) {
                    $q->where('due_date', $dueDateStr)
                        ->orWhere('due_date', 'like', "{$dueDateStr}%");
                })
                ->lockForUpdate()
                ->first();

            if ($occurrence && $occurrence->status === 'confirmed') {
                throw ValidationException::withMessages([
                    'due_date' => 'Periode yang telah dicatat tidak dapat dilewati.',
                ]);
            }

            if ($occurrence) {
                $occurrence->update([
                    'status' => 'skipped',
                    'confirmed_at' => now(),
                    'confirmed_by' => $actor->id,
                    'notes' => $notes,
                ]);
            } else {
                $occurrence = RecurringOccurrence::create([
                    'recurring_schedule_id' => $lockedSchedule->id,
                    'space_id' => $space->id,
                    'due_date' => $dueDateStr,
                    'status' => 'skipped',
                    'confirmed_at' => now(),
                    'confirmed_by' => $actor->id,
                    'notes' => $notes,
                ]);
            }

            $currentDueDate = Carbon::parse($dueDate)->startOfDay();
            $scheduleNextDue = $lockedSchedule->next_due_date
                ? Carbon::parse($lockedSchedule->next_due_date)->startOfDay()
                : $currentDueDate;

            if ($currentDueDate->gte($scheduleNextDue)) {
                $nextDueDate = $lockedSchedule->calculateNextDueDate($currentDueDate);

                if ($lockedSchedule->end_date && $nextDueDate->gt(Carbon::parse($lockedSchedule->end_date))) {
                    $lockedSchedule->update([
                        'status' => 'completed',
                    ]);
                } else {
                    $lockedSchedule->update([
                        'next_due_date' => $nextDueDate->toDateString(),
                    ]);

                    RecurringOccurrence::firstOrCreate([
                        'recurring_schedule_id' => $lockedSchedule->id,
                        'due_date' => $nextDueDate->toDateString(),
                    ], [
                        'space_id' => $space->id,
                        'status' => 'pending',
                        'amount' => $lockedSchedule->amount,
                    ]);
                }
            }

            $this->activityLog->log(
                module: 'recurring',
                action: 'skipped',
                description: "Melewati jadwal berulang \"{$lockedSchedule->name}\" untuk jatuh tempo {$dueDateStr}",
                space: $space,
                actor: $actor,
                subject: $lockedSchedule,
                subjectLabel: $lockedSchedule->name,
                properties: [
                    'due_date' => $dueDateStr,
                    'notes' => $notes,
                ],
            );

            return $occurrence;
        });
    }

    public function archiveSchedule(RecurringSchedule $schedule, User $actor): void
    {
        $schedule->archive();

        $this->activityLog->log(
            module: 'recurring',
            action: 'archived',
            description: "Mengarsipkan jadwal berulang \"{$schedule->name}\"",
            space: $schedule->space,
            actor: $actor,
            subject: $schedule,
            subjectLabel: $schedule->name,
        );
    }

    public function unarchiveSchedule(RecurringSchedule $schedule, User $actor): void
    {
        $schedule->unarchive();

        $this->activityLog->log(
            module: 'recurring',
            action: 'unarchived',
            description: "Mengaktifkan kembali jadwal berulang \"{$schedule->name}\"",
            space: $schedule->space,
            actor: $actor,
            subject: $schedule,
            subjectLabel: $schedule->name,
        );
    }

    public function getDashboardSummary(Space $space): array
    {
        $tz = $space->timezone ?? 'Asia/Jakarta';
        $today = Carbon::now($tz)->startOfDay();
        $in7Days = $today->copy()->addDays(7)->endOfDay();

        $activeSchedules = RecurringSchedule::where('space_id', $space->id)
            ->active()
            ->with(['member', 'category', 'sourceAccount', 'destinationAccount', 'creditAccount'])
            ->orderBy('next_due_date', 'asc')
            ->get();

        $overdueCount = 0;
        $dueTodayCount = 0;
        $upcomingCount = 0;
        $needingAttention = [];

        foreach ($activeSchedules as $sch) {
            $dueDate = Carbon::parse($sch->next_due_date, $tz)->startOfDay();
            $itemData = [
                'id' => $sch->id,
                'name' => $sch->name,
                'due_date' => $sch->next_due_date instanceof Carbon ? $sch->next_due_date->toDateString() : (string) $sch->next_due_date,
                'amount' => (int) ($sch->amount ?? 0),
                'schedule' => $sch,
            ];

            if ($dueDate->lt($today)) {
                $overdueCount++;
                $itemData['status'] = 'overdue';
                $needingAttention[] = $itemData;
            } elseif ($dueDate->eq($today)) {
                $dueTodayCount++;
                $itemData['status'] = 'due_today';
                $needingAttention[] = $itemData;
            } elseif ($dueDate->lte($in7Days)) {
                $upcomingCount++;
                $itemData['status'] = 'upcoming';
                $needingAttention[] = $itemData;
            }
        }

        return [
            'total_due_count' => $overdueCount + $dueTodayCount,
            'overdue_count' => $overdueCount,
            'due_today_count' => $dueTodayCount,
            'upcoming_count' => $upcomingCount,
            'items' => array_slice($needingAttention, 0, 5),
        ];
    }
}
