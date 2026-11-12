<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Member;
use App\Models\RecurringOccurrence;
use App\Models\Space;
use App\Models\TransactionRevision;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    public function recordIncome(Space $space, User $actor, array $data): FinancialTransaction
    {
        $requestKey = $data['request_key'] ?? null;
        if ($requestKey) {
            $existing = FinancialTransaction::where('space_id', $space->id)
                ->where('request_key', $requestKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        try {
            return DB::transaction(function () use ($space, $actor, $data, $requestKey) {
                $account = Account::where('space_id', $space->id)
                    ->where('id', $data['account_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $account) {
                    throw ValidationException::withMessages([
                        'account_id' => 'Rekening tidak valid atau bukan milik ruang aktif.',
                    ]);
                }

                if ($account->isArchived()) {
                    throw ValidationException::withMessages([
                        'account_id' => 'Rekening yang telah diarsipkan tidak dapat menerima mutasi transaksi.',
                    ]);
                }

                $category = Category::where('space_id', $space->id)
                    ->where('id', $data['category_id'])
                    ->income()
                    ->active()
                    ->first();

                if (! $category) {
                    throw ValidationException::withMessages([
                        'category_id' => 'Kategori pemasukan tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
                    ]);
                }

                $amount = round((float) $data['amount'], 2);
                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'Nominal transaksi harus lebih dari 0.',
                    ]);
                }

                $transaction = FinancialTransaction::create([
                    'space_id' => $space->id,
                    'type' => 'income',
                    'transaction_date' => $data['transaction_date'],
                    'transaction_time' => ! empty($data['transaction_time']) ? $data['transaction_time'] : now()->format('H:i:s'),
                    'description' => $data['description'],
                    'category_id' => $category->id,
                    'member_id' => $data['member_id'] ?? null,
                    'status' => 'posted',
                    'request_key' => $requestKey,
                    'created_by' => $actor->id,
                    'version' => 1,
                ]);

                $transaction->entries()->create([
                    'account_id' => $account->id,
                    'amount_signed' => $amount,
                ]);

                if (empty($data['skip_activity_log'])) {
                    $this->activityLog->log(
                        module: 'transaction',
                        action: 'created',
                        description: "Mencatat pemasukan '{$transaction->description}' sebesar ".$this->activityLog->formatCurrency($amount)." ke {$account->name}",
                        space: $space,
                        actor: $actor,
                        subject: $transaction,
                        subjectLabel: "#{$transaction->id} {$transaction->description}",
                        properties: [
                            'type' => 'income',
                            'amount' => $amount,
                            'account' => $account->name,
                            'category' => $category->name,
                            'date' => $transaction->transaction_date->toDateString(),
                        ],
                    );
                }

                return $transaction;
            });
        } catch (UniqueConstraintViolationException $e) {
            if ($requestKey) {
                $existing = FinancialTransaction::where('space_id', $space->id)
                    ->where('request_key', $requestKey)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            throw $e;
        }
    }

    public function recordExpense(Space $space, User $actor, array $data): FinancialTransaction
    {
        $requestKey = $data['request_key'] ?? null;
        if ($requestKey) {
            $existing = FinancialTransaction::where('space_id', $space->id)
                ->where('request_key', $requestKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        try {
            return DB::transaction(function () use ($space, $actor, $data, $requestKey) {
                $account = Account::where('space_id', $space->id)
                    ->where('id', $data['account_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $account) {
                    throw ValidationException::withMessages([
                        'account_id' => 'Rekening tidak valid atau bukan milik ruang aktif.',
                    ]);
                }

                if ($account->isArchived()) {
                    throw ValidationException::withMessages([
                        'account_id' => 'Rekening yang telah diarsipkan tidak dapat melakukan mutasi transaksi.',
                    ]);
                }

                $category = Category::where('space_id', $space->id)
                    ->where('id', $data['category_id'])
                    ->expense()
                    ->active()
                    ->first();

                if (! $category) {
                    throw ValidationException::withMessages([
                        'category_id' => 'Kategori pengeluaran tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
                    ]);
                }

                $amount = round((float) $data['amount'], 2);
                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'Nominal transaksi harus lebih dari 0.',
                    ]);
                }

                $transaction = FinancialTransaction::create([
                    'space_id' => $space->id,
                    'type' => 'expense',
                    'transaction_date' => $data['transaction_date'],
                    'transaction_time' => ! empty($data['transaction_time']) ? $data['transaction_time'] : now()->format('H:i:s'),
                    'description' => $data['description'],
                    'category_id' => $category->id,
                    'member_id' => $data['member_id'] ?? null,
                    'status' => 'posted',
                    'request_key' => $requestKey,
                    'created_by' => $actor->id,
                    'version' => 1,
                ]);

                $transaction->entries()->create([
                    'account_id' => $account->id,
                    'amount_signed' => -$amount,
                ]);

                if (empty($data['skip_activity_log'])) {
                    $this->activityLog->log(
                        module: 'transaction',
                        action: 'created',
                        description: "Mencatat pengeluaran '{$transaction->description}' sebesar ".$this->activityLog->formatCurrency($amount)." dari {$account->name}",
                        space: $space,
                        actor: $actor,
                        subject: $transaction,
                        subjectLabel: "#{$transaction->id} {$transaction->description}",
                        properties: [
                            'type' => 'expense',
                            'amount' => $amount,
                            'account' => $account->name,
                            'category' => $category->name,
                            'date' => $transaction->transaction_date->toDateString(),
                        ],
                    );
                }

                return $transaction;
            });
        } catch (UniqueConstraintViolationException $e) {
            if ($requestKey) {
                $existing = FinancialTransaction::where('space_id', $space->id)
                    ->where('request_key', $requestKey)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            throw $e;
        }
    }

    public function recordTransfer(Space $space, User $actor, array $data): FinancialTransaction
    {
        $requestKey = $data['request_key'] ?? null;
        if ($requestKey) {
            $existing = FinancialTransaction::where('space_id', $space->id)
                ->where('request_key', $requestKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $sourceId = (int) $data['source_account_id'];
        $destId = (int) $data['destination_account_id'];

        if ($sourceId === $destId) {
            throw ValidationException::withMessages([
                'destination_account_id' => 'Rekening tujuan transfer harus berbeda dengan rekening asal.',
            ]);
        }

        try {
            return DB::transaction(function () use ($space, $actor, $data, $sourceId, $destId, $requestKey) {
                $sortedIds = [$sourceId, $destId];
                sort($sortedIds);

                $accounts = Account::where('space_id', $space->id)
                    ->whereIn('id', $sortedIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if (! $accounts->has($sourceId) || ! $accounts->has($destId)) {
                    throw ValidationException::withMessages([
                        'source_account_id' => 'Salah satu rekening transfer tidak valid atau bukan milik ruang aktif.',
                    ]);
                }

                $sourceAccount = $accounts->get($sourceId);
                $destAccount = $accounts->get($destId);

                if ($sourceAccount->isArchived() || $destAccount->isArchived()) {
                    throw ValidationException::withMessages([
                        'source_account_id' => 'Rekening yang telah diarsipkan tidak dapat digunakan untuk transfer.',
                    ]);
                }

                $amount = round((float) $data['amount'], 2);
                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'Nominal transfer harus lebih dari 0.',
                    ]);
                }

                $feeAmount = isset($data['fee_amount']) ? round((float) $data['fee_amount'], 2) : 0.0;
                if ($feeAmount < 0) {
                    throw ValidationException::withMessages([
                        'fee_amount' => 'Biaya admin transfer tidak boleh negatif.',
                    ]);
                }

                $feeCategoryId = null;
                if ($feeAmount > 0) {
                    if (! empty($data['fee_category_id'])) {
                        $cat = Category::where('space_id', $space->id)
                            ->where('id', $data['fee_category_id'])
                            ->expense()
                            ->active()
                            ->first();

                        if (! $cat) {
                            throw ValidationException::withMessages([
                                'fee_category_id' => 'Kategori biaya admin tidak valid atau telah diarsipkan.',
                            ]);
                        }

                        $feeCategoryId = $cat->id;
                    } else {
                        $cat = Category::where('space_id', $space->id)
                            ->expense()
                            ->active()
                            ->where(function ($q) {
                                $q->where('name', 'like', '%Admin%')
                                    ->orWhere('name', 'like', '%Layanan%');
                            })
                            ->first();

                        if (! $cat) {
                            $cat = Category::where('space_id', $space->id)
                                ->expense()
                                ->active()
                                ->first();
                        }

                        $feeCategoryId = $cat?->id;
                    }
                }

                $transaction = FinancialTransaction::create([
                    'space_id' => $space->id,
                    'type' => 'transfer',
                    'transaction_date' => $data['transaction_date'],
                    'transaction_time' => ! empty($data['transaction_time']) ? $data['transaction_time'] : now()->format('H:i:s'),
                    'description' => $data['description'],
                    'category_id' => null,
                    'fee_amount' => $feeAmount,
                    'fee_category_id' => $feeCategoryId,
                    'member_id' => $data['member_id'] ?? null,
                    'status' => 'posted',
                    'request_key' => $requestKey,
                    'created_by' => $actor->id,
                    'version' => 1,
                ]);

                $transaction->entries()->create([
                    'account_id' => $sourceAccount->id,
                    'amount_signed' => -($amount + $feeAmount),
                ]);

                $transaction->entries()->create([
                    'account_id' => $destAccount->id,
                    'amount_signed' => $amount,
                ]);

                if (empty($data['skip_activity_log'])) {
                    $feeText = $feeAmount > 0 ? ' (biaya admin '.$this->activityLog->formatCurrency($feeAmount).')' : '';
                    $this->activityLog->log(
                        module: 'transaction',
                        action: 'created',
                        description: "Mencatat transfer '{$transaction->description}' sebesar ".$this->activityLog->formatCurrency($amount)." dari {$sourceAccount->name} ke {$destAccount->name}{$feeText}",
                        space: $space,
                        actor: $actor,
                        subject: $transaction,
                        subjectLabel: "#{$transaction->id} {$transaction->description}",
                        properties: [
                            'type' => 'transfer',
                            'amount' => $amount,
                            'fee_amount' => $feeAmount,
                            'source_account' => $sourceAccount->name,
                            'destination_account' => $destAccount->name,
                            'date' => $transaction->transaction_date->toDateString(),
                        ],
                    );
                }

                return $transaction;
            });
        } catch (UniqueConstraintViolationException $e) {
            if ($requestKey) {
                $existing = FinancialTransaction::where('space_id', $space->id)
                    ->where('request_key', $requestKey)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            throw $e;
        }
    }

    public function correctTransaction(FinancialTransaction $transaction, User $actor, array $data, ?string $reason = null): FinancialTransaction
    {
        $expectedVersion = isset($data['version']) ? (int) $data['version'] : null;

        return DB::transaction(function () use ($transaction, $actor, $data, $reason, $expectedVersion) {
            $lockedTransaction = FinancialTransaction::where('id', $transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTransaction->isVoid()) {
                throw ValidationException::withMessages([
                    'status' => 'Transaksi yang telah dibatalkan (void) tidak dapat dikoreksi atau diubah.',
                ]);
            }

            if ($expectedVersion !== null && $expectedVersion !== (int) $lockedTransaction->version) {
                throw ValidationException::withMessages([
                    'version' => 'Konflik pembaruan bersamaan: data transaksi ini telah diubah oleh pengguna lain. Silakan muat ulang halaman.',
                ]);
            }

            $space = $lockedTransaction->space;
            $type = $data['type'] ?? $lockedTransaction->type;
            $amount = round((float) ($data['amount'] ?? $lockedTransaction->totalAmount()), 2);
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal transaksi harus lebih dari 0.',
                ]);
            }

            $lockedTransaction->load(['entries.account', 'category', 'feeCategory']);
            $beforeSnapshot = $this->buildRevisionSnapshot($lockedTransaction);

            $categoryId = null;
            $feeAmount = 0.0;
            $feeCategoryId = null;

            if ($type === 'income') {
                $account = $this->lockActiveAccount(
                    $space,
                    (int) $data['account_id'],
                    'account_id',
                    'Rekening yang telah diarsipkan tidak dapat menerima mutasi transaksi.'
                );
                $category = $this->resolveActiveCategory($space, (int) $data['category_id'], 'income');
                $categoryId = $category->id;

                $lockedTransaction->entries()->delete();
                $lockedTransaction->entries()->create([
                    'account_id' => $account->id,
                    'amount_signed' => $amount,
                ]);
            } elseif ($type === 'expense') {
                $account = $this->lockActiveAccount(
                    $space,
                    (int) $data['account_id'],
                    'account_id',
                    'Rekening yang telah diarsipkan tidak dapat melakukan mutasi transaksi.'
                );
                $category = $this->resolveActiveCategory($space, (int) $data['category_id'], 'expense');
                $categoryId = $category->id;

                $lockedTransaction->entries()->delete();
                $lockedTransaction->entries()->create([
                    'account_id' => $account->id,
                    'amount_signed' => -$amount,
                ]);
            } elseif ($type === 'transfer') {
                $sourceId = (int) ($data['source_account_id'] ?? 0);
                $destId = (int) ($data['destination_account_id'] ?? 0);

                if ($sourceId === $destId) {
                    throw ValidationException::withMessages([
                        'destination_account_id' => 'Rekening tujuan transfer harus berbeda dengan rekening asal.',
                    ]);
                }

                $accounts = $this->lockActiveTransferAccounts($space, [$sourceId, $destId]);
                $sourceAccount = $accounts->get($sourceId);
                $destAccount = $accounts->get($destId);

                $feeAmount = array_key_exists('fee_amount', $data) ? round((float) $data['fee_amount'], 2) : (float) ($lockedTransaction->fee_amount ?? 0);
                if ($feeAmount < 0) {
                    throw ValidationException::withMessages([
                        'fee_amount' => 'Biaya admin transfer tidak boleh negatif.',
                    ]);
                }

                $targetCatId = array_key_exists('fee_category_id', $data) ? $data['fee_category_id'] : $lockedTransaction->fee_category_id;
                $feeCategoryId = $this->resolveTransferFeeCategoryId($space, $feeAmount, $targetCatId);

                $lockedTransaction->entries()->delete();
                $lockedTransaction->entries()->create([
                    'account_id' => $sourceAccount->id,
                    'amount_signed' => -($amount + $feeAmount),
                ]);
                $lockedTransaction->entries()->create([
                    'account_id' => $destAccount->id,
                    'amount_signed' => $amount,
                ]);
            }

            $lockedTransaction->update([
                'type' => $type,
                'transaction_date' => $data['transaction_date'] ?? $lockedTransaction->transaction_date,
                'transaction_time' => array_key_exists('transaction_time', $data) ? $data['transaction_time'] : $lockedTransaction->transaction_time,
                'description' => $data['description'] ?? $lockedTransaction->description,
                'category_id' => $categoryId,
                'fee_amount' => $type === 'transfer' ? $feeAmount : 0,
                'fee_category_id' => $type === 'transfer' ? $feeCategoryId : null,
                'member_id' => array_key_exists('member_id', $data) ? $data['member_id'] : $lockedTransaction->member_id,
                'updated_by' => $actor->id,
                'version' => $lockedTransaction->version + 1,
            ]);

            $lockedTransaction->load(['entries.account', 'category', 'feeCategory']);
            $afterSnapshot = $this->buildRevisionSnapshot($lockedTransaction);

            TransactionRevision::create([
                'transaction_id' => $lockedTransaction->id,
                'actor_id' => $actor->id,
                'before_data' => $beforeSnapshot,
                'after_data' => $afterSnapshot,
                'reason' => $reason ?? 'Koreksi rincian transaksi',
                'created_at' => now(),
            ]);

            $this->activityLog->log(
                module: 'transaction',
                action: 'updated',
                description: "Mengoreksi transaksi #{$lockedTransaction->id} '{$lockedTransaction->description}' (v{$lockedTransaction->version})",
                space: $space,
                actor: $actor,
                subject: $lockedTransaction,
                subjectLabel: "#{$lockedTransaction->id} {$lockedTransaction->description}",
                properties: array_filter([
                    'reason' => $reason,
                    'before' => $beforeSnapshot,
                    'after' => $afterSnapshot,
                ], fn (mixed $v): bool => $v !== null),
            );

            return $lockedTransaction;
        });
    }

    /**
     * Terapkan koreksi seragam atau pembatalan (void) massal pada transaksi terpilih secara atomik.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, FinancialTransaction>
     */
    public function bulkCorrectTransactions(Space $space, User $actor, array $data): Collection
    {
        $action = (string) ($data['action'] ?? 'update');
        if (! in_array($action, ['update', 'void'], true)) {
            throw ValidationException::withMessages([
                'action' => 'Aksi koreksi massal tidak valid.',
            ]);
        }

        $transactionIds = collect($data['transaction_ids'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values();

        if ($transactionIds->isEmpty()) {
            throw ValidationException::withMessages([
                'transaction_ids' => 'Pilih minimal satu transaksi untuk diproses.',
            ]);
        }

        $reason = isset($data['reason']) && trim((string) $data['reason']) !== '' ? trim((string) $data['reason']) : null;
        $versions = is_array($data['versions'] ?? null) ? $data['versions'] : [];

        return DB::transaction(function () use ($space, $actor, $data, $action, $transactionIds, $reason, $versions): Collection {
            $lockedTransactions = FinancialTransaction::where('space_id', $space->id)
                ->whereIn('id', $transactionIds->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lockedTransactions->count() !== $transactionIds->count()) {
                throw ValidationException::withMessages([
                    'transaction_ids' => 'Sebagian transaksi yang dipilih tidak ditemukan di ruang aktif.',
                ]);
            }

            $lockedTransactions->load(['entries.account', 'category', 'feeCategory', 'installmentPlan']);

            foreach ($lockedTransactions as $lockedTransaction) {
                Gate::forUser($actor)->authorize($action === 'void' ? 'void' : 'update', $lockedTransaction);

                if ($lockedTransaction->isVoid()) {
                    throw ValidationException::withMessages([
                        'status' => 'Sebagian transaksi yang dipilih sudah dibatalkan (void) dan tidak dapat diproses lagi.',
                    ]);
                }

                if (! in_array($lockedTransaction->type, ['income', 'expense', 'transfer'], true)) {
                    throw ValidationException::withMessages([
                        'transaction_ids' => 'Transaksi pembayaran kredit atau penyesuaian saldo tidak dapat diubah melalui koreksi massal.',
                    ]);
                }

                $providedVersion = $versions[$lockedTransaction->id] ?? $versions[(string) $lockedTransaction->id] ?? null;
                if ($providedVersion !== null && (int) $providedVersion !== (int) $lockedTransaction->version) {
                    throw ValidationException::withMessages([
                        'version' => 'Konflik pembaruan bersamaan: salah satu transaksi yang dipilih telah diubah. Silakan muat ulang halaman.',
                    ]);
                }
            }

            if ($action === 'void') {
                $voided = $lockedTransactions->map(
                    fn (FinancialTransaction $tx): FinancialTransaction => $this->applyVoidToLockedTransaction(
                        $tx,
                        $actor,
                        $reason ?? 'Pembatalan massal transaksi (void)'
                    )
                );

                $this->activityLog->log(
                    module: 'transaction',
                    action: 'bulk_voided',
                    description: "Membatalkan (void) {$voided->count()} transaksi secara massal",
                    space: $space,
                    actor: $actor,
                    subjectLabel: "{$voided->count()} transaksi",
                    properties: array_filter([
                        'count' => $voided->count(),
                        'transaction_ids' => $voided->pluck('id')->all(),
                        'reason' => $reason,
                    ], fn (mixed $v): bool => $v !== null),
                );

                return $voided;
            }

            $updated = $this->applyBulkFieldUpdates($space, $actor, $lockedTransactions, $data, $reason);

            $this->activityLog->log(
                module: 'transaction',
                action: 'bulk_updated',
                description: "Mengoreksi {$updated->count()} transaksi secara massal",
                space: $space,
                actor: $actor,
                subjectLabel: "{$updated->count()} transaksi",
                properties: array_filter([
                    'count' => $updated->count(),
                    'transaction_ids' => $updated->pluck('id')->all(),
                    'reason' => $reason,
                ], fn (mixed $v): bool => $v !== null),
            );

            return $updated;
        });
    }

    public function voidTransaction(FinancialTransaction $transaction, User $actor, ?string $reason = null): FinancialTransaction
    {
        return DB::transaction(function () use ($transaction, $actor, $reason) {
            $lockedTransaction = FinancialTransaction::where('id', $transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTransaction->isVoid()) {
                return $lockedTransaction;
            }

            $lockedTransaction->load(['entries.account', 'category', 'feeCategory']);

            $voided = $this->applyVoidToLockedTransaction($lockedTransaction, $actor, $reason);

            $this->activityLog->log(
                module: 'transaction',
                action: 'voided',
                description: "Membatalkan (void) transaksi #{$voided->id} '{$voided->description}' sebesar ".$this->activityLog->formatCurrency($voided->totalAmount()),
                space: $voided->space,
                actor: $actor,
                subject: $voided,
                subjectLabel: "#{$voided->id} {$voided->description}",
                properties: array_filter([
                    'amount' => $voided->totalAmount(),
                    'reason' => $reason,
                ], fn (mixed $v): bool => $v !== null),
            );

            return $voided;
        });
    }

    /**
     * Terapkan pembaruan field terpilih pada kumpulan transaksi yang sudah dikunci dan divalidasi.
     *
     * @param  Collection<int, FinancialTransaction>  $lockedTransactions
     * @param  array<string, mixed>  $data
     * @return Collection<int, FinancialTransaction>
     */
    private function applyBulkFieldUpdates(
        Space $space,
        User $actor,
        Collection $lockedTransactions,
        array $data,
        ?string $reason
    ): Collection {
        $applyCategory = (bool) ($data['apply_category'] ?? false);
        $applyAccount = (bool) ($data['apply_account'] ?? false);
        $applySourceAccount = (bool) ($data['apply_source_account'] ?? false);
        $applyDestinationAccount = (bool) ($data['apply_destination_account'] ?? false);
        $applyFeeAmount = (bool) ($data['apply_fee_amount'] ?? false);
        $applyFeeCategory = (bool) ($data['apply_fee_category'] ?? false);
        $applyMember = (bool) ($data['apply_member'] ?? false);
        $applyDate = (bool) ($data['apply_transaction_date'] ?? false);
        $applyTime = (bool) ($data['apply_transaction_time'] ?? false);
        $applyDescription = (bool) ($data['apply_description'] ?? false);
        $applyAmount = (bool) ($data['apply_amount'] ?? false);

        if (! ($applyCategory || $applyAccount || $applySourceAccount || $applyDestinationAccount || $applyFeeAmount || $applyFeeCategory || $applyMember || $applyDate || $applyTime || $applyDescription || $applyAmount)) {
            throw ValidationException::withMessages([
                'fields' => 'Pilih minimal satu kolom yang ingin dikoreksi secara massal.',
            ]);
        }

        $types = $lockedTransactions->pluck('type')->unique()->values();

        if ($applyCategory) {
            if ($types->count() !== 1 || ! in_array($types->first(), ['income', 'expense'], true)) {
                throw ValidationException::withMessages([
                    'category_id' => 'Koreksi kategori massal hanya dapat diterapkan jika seluruh transaksi yang dipilih bertipe sama (semua Pengeluaran atau semua Pemasukan).',
                ]);
            }

            $this->resolveActiveCategory($space, (int) ($data['category_id'] ?? 0), $types->first());
        }

        if ($applyAccount && $types->contains('transfer')) {
            throw ValidationException::withMessages([
                'account_id' => 'Kolom rekening tunggal tidak berlaku jika terdapat transaksi Transfer di dalam pilihan.',
            ]);
        }

        if (($applySourceAccount || $applyDestinationAccount || $applyFeeAmount || $applyFeeCategory) && ($types->count() !== 1 || $types->first() !== 'transfer')) {
            throw ValidationException::withMessages([
                'source_account_id' => 'Kolom rekening asal, rekening tujuan, atau biaya admin hanya berlaku jika seluruh transaksi yang dipilih berjenis Transfer.',
            ]);
        }

        if ($applyAmount) {
            $newAmount = round((float) ($data['amount'] ?? 0), 2);
            if ($newAmount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal transaksi harus lebih dari 0.',
                ]);
            }
        }

        if ($applyFeeAmount) {
            $newFeeAmount = round((float) ($data['fee_amount'] ?? 0), 2);
            if ($newFeeAmount < 0) {
                throw ValidationException::withMessages([
                    'fee_amount' => 'Biaya admin transfer tidak boleh negatif.',
                ]);
            }
        }

        if ($applyMember && ! empty($data['member_id'])) {
            $memberValid = Member::where('space_id', $space->id)
                ->where('id', (int) $data['member_id'])
                ->active()
                ->exists();

            if (! $memberValid) {
                throw ValidationException::withMessages([
                    'member_id' => 'Member yang dipilih tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
                ]);
            }
        }

        if ($applyAmount || $applyAccount) {
            foreach ($lockedTransactions as $tx) {
                if ($tx->installmentPlan !== null) {
                    throw ValidationException::withMessages([
                        'transaction_ids' => 'Nominal atau rekening transaksi pembelian cicilan tidak dapat diubah secara massal karena terikat jadwal cicilan.',
                    ]);
                }
            }
        }

        $accountIdsToLock = [];
        foreach ($lockedTransactions as $tx) {
            if ($tx->type === 'transfer') {
                if ($applyAmount || $applySourceAccount || $applyDestinationAccount || $applyFeeAmount) {
                    $srcId = $applySourceAccount ? (int) ($data['source_account_id'] ?? 0) : (int) ($tx->sourceAccount()?->id ?? 0);
                    $dstId = $applyDestinationAccount ? (int) ($data['destination_account_id'] ?? 0) : (int) ($tx->destinationAccount()?->id ?? 0);

                    if ($srcId <= 0 || $dstId <= 0) {
                        throw ValidationException::withMessages([
                            'source_account_id' => 'Rekening asal atau tujuan transfer tidak ditemukan.',
                        ]);
                    }

                    if ($srcId === $dstId) {
                        throw ValidationException::withMessages([
                            'destination_account_id' => 'Rekening tujuan transfer harus berbeda dengan rekening asal.',
                        ]);
                    }

                    $accountIdsToLock[] = $srcId;
                    $accountIdsToLock[] = $dstId;
                }
            } elseif ($applyAmount || $applyAccount) {
                $targetAccountId = $applyAccount ? (int) ($data['account_id'] ?? 0) : (int) ($tx->entries->first()?->account_id ?? 0);
                if ($targetAccountId <= 0) {
                    throw ValidationException::withMessages([
                        'account_id' => 'Rekening transaksi tidak ditemukan.',
                    ]);
                }

                $accountIdsToLock[] = $targetAccountId;
            }
        }

        if ($accountIdsToLock !== []) {
            $uniqueAccountIds = collect($accountIdsToLock)->unique()->sort()->values()->all();
            $lockedAccounts = Account::where('space_id', $space->id)
                ->whereIn('id', $uniqueAccountIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($uniqueAccountIds as $accountId) {
                $account = $lockedAccounts->get($accountId);
                if (! $account) {
                    throw ValidationException::withMessages([
                        'account_id' => 'Rekening yang dipilih tidak valid atau bukan milik ruang aktif.',
                    ]);
                }

                if ($account->isArchived()) {
                    throw ValidationException::withMessages([
                        'account_id' => 'Rekening yang telah diarsipkan tidak dapat melakukan atau menerima mutasi transaksi.',
                    ]);
                }
            }
        }

        if ($applyFeeCategory && ! empty($data['fee_category_id'])) {
            $this->resolveActiveCategory($space, (int) $data['fee_category_id'], 'expense', 'fee_category_id');
        }

        foreach ($lockedTransactions as $tx) {
            $beforeSnapshot = $this->buildRevisionSnapshot($tx);
            $amount = $applyAmount ? round((float) $data['amount'], 2) : $tx->totalAmount();

            if ($tx->type === 'transfer') {
                $feeAmount = $applyFeeAmount ? round((float) ($data['fee_amount'] ?? 0), 2) : (float) ($tx->fee_amount ?? 0);
                if ($feeAmount <= 0) {
                    $feeCategoryId = null;
                } elseif ($applyFeeCategory) {
                    $feeCategoryId = $this->resolveTransferFeeCategoryId($space, $feeAmount, $data['fee_category_id'] ?? null);
                } elseif ($applyFeeAmount && ! $tx->fee_category_id) {
                    $feeCategoryId = $this->resolveTransferFeeCategoryId($space, $feeAmount, null);
                } else {
                    $feeCategoryId = $tx->fee_category_id;
                }

                if ($applyAmount || $applySourceAccount || $applyDestinationAccount || $applyFeeAmount) {
                    $srcId = $applySourceAccount ? (int) $data['source_account_id'] : (int) $tx->sourceAccount()->id;
                    $dstId = $applyDestinationAccount ? (int) $data['destination_account_id'] : (int) $tx->destinationAccount()->id;

                    $tx->entries()->delete();
                    $tx->entries()->create([
                        'account_id' => $srcId,
                        'amount_signed' => -($amount + $feeAmount),
                    ]);
                    $tx->entries()->create([
                        'account_id' => $dstId,
                        'amount_signed' => $amount,
                    ]);
                }

                $categoryId = null;
            } else {
                if ($applyAmount || $applyAccount) {
                    $accountId = $applyAccount ? (int) $data['account_id'] : (int) $tx->entries->first()->account_id;
                    $tx->entries()->delete();
                    $tx->entries()->create([
                        'account_id' => $accountId,
                        'amount_signed' => $tx->type === 'income' ? $amount : -$amount,
                    ]);
                }

                $categoryId = $applyCategory ? (int) $data['category_id'] : $tx->category_id;
                $feeAmount = 0;
                $feeCategoryId = null;
            }

            $tx->update([
                'transaction_date' => $applyDate ? $data['transaction_date'] : $tx->transaction_date,
                'transaction_time' => $applyTime ? ($data['transaction_time'] ?? null) : $tx->transaction_time,
                'description' => $applyDescription ? $data['description'] : $tx->description,
                'category_id' => $categoryId,
                'fee_amount' => $tx->type === 'transfer' ? $feeAmount : 0,
                'fee_category_id' => $tx->type === 'transfer' ? $feeCategoryId : null,
                'member_id' => $applyMember ? ($data['member_id'] ?? null) : $tx->member_id,
                'updated_by' => $actor->id,
                'version' => $tx->version + 1,
            ]);

            $tx->load(['entries.account', 'category', 'feeCategory']);
            $afterSnapshot = $this->buildRevisionSnapshot($tx);

            TransactionRevision::create([
                'transaction_id' => $tx->id,
                'actor_id' => $actor->id,
                'before_data' => $beforeSnapshot,
                'after_data' => $afterSnapshot,
                'reason' => $reason ?? 'Koreksi massal transaksi',
                'created_at' => now(),
            ]);
        }

        return $lockedTransactions;
    }

    /**
     * Kunci satu rekening aktif pada space saat ini untuk mutasi transaksi.
     */
    private function lockActiveAccount(Space $space, int $accountId, string $errorField, string $archivedMessage): Account
    {
        $account = Account::where('space_id', $space->id)
            ->where('id', $accountId)
            ->lockForUpdate()
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                $errorField => 'Rekening tidak valid atau bukan milik ruang aktif.',
            ]);
        }

        if ($account->isArchived()) {
            throw ValidationException::withMessages([
                $errorField => $archivedMessage,
            ]);
        }

        return $account;
    }

    /**
     * Kunci pasangan rekening asal dan tujuan transfer dalam urutan ID deterministik.
     *
     * @param  array<int, int>  $accountIds
     * @return Collection<int, Account>
     */
    private function lockActiveTransferAccounts(Space $space, array $accountIds): Collection
    {
        $sortedIds = collect($accountIds)->unique()->sort()->values()->all();

        $accounts = Account::where('space_id', $space->id)
            ->whereIn('id', $sortedIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($sortedIds as $accountId) {
            $account = $accounts->get($accountId);
            if (! $account) {
                throw ValidationException::withMessages([
                    'source_account_id' => 'Salah satu rekening transfer tidak valid atau bukan milik ruang aktif.',
                ]);
            }

            if ($account->isArchived()) {
                throw ValidationException::withMessages([
                    'source_account_id' => 'Rekening yang telah diarsipkan tidak dapat digunakan untuk transfer.',
                ]);
            }
        }

        return $accounts;
    }

    /**
     * Validasi dan ambil kategori aktif sesuai space serta tipe transaksi.
     */
    private function resolveActiveCategory(Space $space, int $categoryId, string $type, string $errorField = 'category_id'): Category
    {
        $category = Category::where('space_id', $space->id)
            ->where('id', $categoryId)
            ->where('type', $type)
            ->active()
            ->first();

        if (! $category) {
            $label = $type === 'income' ? 'pemasukan' : 'pengeluaran';
            if ($errorField === 'fee_category_id') {
                throw ValidationException::withMessages([
                    'fee_category_id' => 'Kategori biaya admin tidak valid atau telah diarsipkan.',
                ]);
            }

            throw ValidationException::withMessages([
                $errorField => "Kategori {$label} tidak valid, bukan milik ruang aktif, atau telah diarsipkan.",
            ]);
        }

        return $category;
    }

    /**
     * Tentukan ID kategori biaya admin transfer (atau fallback otomatis ke Admin/Layanan).
     */
    private function resolveTransferFeeCategoryId(Space $space, float $feeAmount, mixed $requestedCategoryId): ?int
    {
        if ($feeAmount <= 0) {
            return null;
        }

        if (! empty($requestedCategoryId)) {
            return $this->resolveActiveCategory($space, (int) $requestedCategoryId, 'expense', 'fee_category_id')->id;
        }

        $category = Category::where('space_id', $space->id)
            ->expense()
            ->active()
            ->where(function ($query): void {
                $query->where('name', 'like', '%Admin%')
                    ->orWhere('name', 'like', '%Layanan%');
            })
            ->first();

        if (! $category) {
            $category = Category::where('space_id', $space->id)
                ->expense()
                ->active()
                ->first();
        }

        return $category?->id;
    }

    /**
     * Bangun snapshot audit sebelum/sesudah perubahan untuk TransactionRevision.
     *
     * @return array<string, mixed>
     */
    private function buildRevisionSnapshot(FinancialTransaction $transaction): array
    {
        return [
            'type' => $transaction->type,
            'amount' => $transaction->totalAmount(),
            'fee_amount' => (float) ($transaction->fee_amount ?? 0),
            'fee_category_id' => $transaction->fee_category_id,
            'fee_category_name' => $transaction->feeCategory?->name,
            'transaction_date' => $transaction->transaction_date->toDateString(),
            'description' => $transaction->description,
            'category_id' => $transaction->category_id,
            'category_name' => $transaction->category?->name,
            'status' => $transaction->status,
            'version' => $transaction->version,
            'entries' => $transaction->entries->map(fn ($e): array => [
                'account_id' => $e->account_id,
                'account_name' => $e->account?->name,
                'amount_signed' => (int) $e->amount_signed,
            ])->toArray(),
        ];
    }

    /**
     * Batalkan satu transaksi yang sudah dikunci beserta sinkronisasi modul terkait dan audit trail.
     */
    private function applyVoidToLockedTransaction(FinancialTransaction $lockedTransaction, User $actor, ?string $reason): FinancialTransaction
    {
        $beforeSnapshot = $this->buildRevisionSnapshot($lockedTransaction);

        $lockedTransaction->update([
            'status' => 'void',
            'updated_by' => $actor->id,
            'version' => $lockedTransaction->version + 1,
        ]);

        $afterSnapshot = $beforeSnapshot;
        $afterSnapshot['status'] = 'void';
        $afterSnapshot['version'] = $lockedTransaction->version;

        $occurrence = RecurringOccurrence::where('transaction_id', $lockedTransaction->id)->first();
        if ($occurrence) {
            $occurrence->update([
                'status' => 'pending',
                'transaction_id' => null,
                'confirmed_at' => null,
                'confirmed_by' => null,
            ]);
        }

        // Sync dengan InstallmentPlan jika transaksi yang di-void adalah transaksi pembelian kredit
        $installmentPlan = InstallmentPlan::where('financial_transaction_id', $lockedTransaction->id)->first();
        if ($installmentPlan) {
            $installmentPlan->update(['status' => 'cancelled']);
            $installmentPlan->installments()->where('status', 'pending')->update(['status' => 'cancelled']);
        }

        // Sync dengan Installment jika transaksi yang di-void adalah transaksi pembayaran cicilan
        $paidInstallments = Installment::where('payment_transaction_id', $lockedTransaction->id)->get();
        foreach ($paidInstallments as $paidInst) {
            $paidInst->update([
                'status' => 'pending',
                'paid_at' => null,
                'payment_transaction_id' => null,
            ]);
            if ($paidInst->installmentPlan && $paidInst->installmentPlan->status === 'completed') {
                $paidInst->installmentPlan->update(['status' => 'active']);
            }
        }

        TransactionRevision::create([
            'transaction_id' => $lockedTransaction->id,
            'actor_id' => $actor->id,
            'before_data' => $beforeSnapshot,
            'after_data' => $afterSnapshot,
            'reason' => $reason ?? 'Pembatalan transaksi (void)',
            'created_at' => now(),
        ]);

        return $lockedTransaction;
    }
}
