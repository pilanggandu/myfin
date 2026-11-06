<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
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
        protected TransactionService $transactionService
    ) {}

    public function createSchedule(Space $space, User $actor, array $data): RecurringSchedule
    {
        $type = $data['type'] ?? 'expense';
        $startDate = Carbon::parse($data['start_date']);
        $anchorDay = (int) $startDate->day;

        $sourceAccount = null;
        if (! empty($data['source_account_id'])) {
            $sourceAccount = Account::where('space_id', $space->id)
                ->where('id', $data['source_account_id'])
                ->first();

            if (! $sourceAccount || $sourceAccount->isArchived()) {
                throw ValidationException::withMessages([
                    'source_account_id' => 'Rekening sumber tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
                ]);
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

            if (! $destAccount || $destAccount->isArchived()) {
                throw ValidationException::withMessages([
                    'destination_account_id' => 'Rekening tujuan transfer tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
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

        return DB::transaction(function () use ($space, $actor, $data, $type, $startDate, $anchorDay, $sourceAccount, $destAccount, $category, $memberId, $isVariable, $amount) {
            $schedule = RecurringSchedule::create([
                'space_id' => $space->id,
                'type' => $type,
                'name' => $data['name'],
                'member_id' => $memberId,
                'category_id' => $category?->id,
                'source_account_id' => $sourceAccount?->id,
                'destination_account_id' => $destAccount?->id,
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

            return $schedule;
        });
    }

    public function updateSchedule(RecurringSchedule $schedule, User $actor, array $data): RecurringSchedule
    {
        $space = $schedule->space;
        $type = $schedule->type;

        $sourceAccount = null;
        if (! empty($data['source_account_id'])) {
            $sourceAccount = Account::where('space_id', $space->id)
                ->where('id', $data['source_account_id'])
                ->first();

            if (! $sourceAccount || $sourceAccount->isArchived()) {
                throw ValidationException::withMessages([
                    'source_account_id' => 'Rekening sumber tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
                ]);
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

            if (! $destAccount || $destAccount->isArchived()) {
                throw ValidationException::withMessages([
                    'destination_account_id' => 'Rekening tujuan transfer tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
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

        $startDate = Carbon::parse($data['start_date']);
        $anchorDay = (int) $startDate->day;

        $schedule->update([
            'name' => $data['name'],
            'member_id' => $memberId,
            'category_id' => $category?->id,
            'source_account_id' => $sourceAccount?->id,
            'destination_account_id' => $destAccount?->id,
            'frequency' => $data['frequency'],
            'anchor_day' => $anchorDay,
            'start_date' => $startDate->toDateString(),
            'end_date' => ! empty($data['end_date']) ? Carbon::parse($data['end_date'])->toDateString() : null,
            'amount' => $amount,
            'is_variable_amount' => $isVariable,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? $schedule->status,
        ]);

        return $schedule;
    }

    public function confirmOccurrence(RecurringSchedule $schedule, string $dueDate, User $actor, array $overrideData = []): FinancialTransaction
    {
        return DB::transaction(function () use ($schedule, $dueDate, $actor, $overrideData) {
            $lockedSchedule = RecurringSchedule::where('id', $schedule->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSchedule->isArchived()) {
                throw ValidationException::withMessages([
                    'schedule' => 'Jadwal berulang telah diarsipkan dan tidak dapat dicatat.',
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

                $transaction = $this->transactionService->recordExpense($space, $actor, [
                    'account_id' => $sourceAccount->id,
                    'category_id' => $categoryId,
                    'amount' => $amount,
                    'transaction_date' => $txDate,
                    'transaction_time' => $overrideData['transaction_time'] ?? now()->format('H:i:s'),
                    'description' => $description,
                    'member_id' => $lockedSchedule->member_id,
                ]);
            } else {
                $destAccountId = ! empty($overrideData['destination_account_id'])
                    ? (int) $overrideData['destination_account_id']
                    : (int) $lockedSchedule->destination_account_id;

                if ($destAccountId === (int) $sourceAccount->id) {
                    throw ValidationException::withMessages([
                        'destination_account_id' => 'Rekening tujuan transfer harus berbeda dengan rekening asal.',
                    ]);
                }

                $transaction = $this->transactionService->recordTransfer($space, $actor, [
                    'source_account_id' => $sourceAccount->id,
                    'destination_account_id' => $destAccountId,
                    'amount' => $amount,
                    'transaction_date' => $txDate,
                    'transaction_time' => $overrideData['transaction_time'] ?? now()->format('H:i:s'),
                    'description' => $description,
                    'member_id' => $lockedSchedule->member_id,
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

            $currentDueDate = Carbon::parse($dueDate);
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

            return $transaction;
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

            $currentDueDate = Carbon::parse($dueDate);
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

            return $occurrence;
        });
    }

    public function archiveSchedule(RecurringSchedule $schedule, User $actor): void
    {
        $schedule->archive();
    }

    public function unarchiveSchedule(RecurringSchedule $schedule, User $actor): void
    {
        $schedule->unarchive();
    }

    public function getDashboardSummary(Space $space): array
    {
        $tz = $space->timezone ?? 'Asia/Jakarta';
        $today = Carbon::now($tz)->startOfDay();
        $in7Days = $today->copy()->addDays(7)->endOfDay();

        $activeSchedules = RecurringSchedule::where('space_id', $space->id)
            ->active()
            ->with(['member', 'category', 'sourceAccount', 'destinationAccount'])
            ->orderBy('next_due_date', 'asc')
            ->get();

        $overdueCount = 0;
        $dueTodayCount = 0;
        $upcomingCount = 0;
        $needingAttention = [];

        foreach ($activeSchedules as $sch) {
            $dueDate = Carbon::parse($sch->next_due_date, $tz)->startOfDay();
            if ($dueDate->lt($today)) {
                $overdueCount++;
                $needingAttention[] = $sch;
            } elseif ($dueDate->eq($today)) {
                $dueTodayCount++;
                $needingAttention[] = $sch;
            } elseif ($dueDate->lte($in7Days)) {
                $upcomingCount++;
                $needingAttention[] = $sch;
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
