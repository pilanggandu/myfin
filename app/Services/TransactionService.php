<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\RecurringOccurrence;
use App\Models\Space;
use App\Models\TransactionRevision;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
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
            $beforeSnapshot = [
                'type' => $lockedTransaction->type,
                'amount' => $lockedTransaction->totalAmount(),
                'fee_amount' => (float) ($lockedTransaction->fee_amount ?? 0),
                'fee_category_id' => $lockedTransaction->fee_category_id,
                'fee_category_name' => $lockedTransaction->feeCategory?->name,
                'transaction_date' => $lockedTransaction->transaction_date->toDateString(),
                'description' => $lockedTransaction->description,
                'category_id' => $lockedTransaction->category_id,
                'category_name' => $lockedTransaction->category?->name,
                'status' => $lockedTransaction->status,
                'version' => $lockedTransaction->version,
                'entries' => $lockedTransaction->entries->map(fn ($e) => [
                    'account_id' => $e->account_id,
                    'account_name' => $e->account?->name,
                    'amount_signed' => (int) $e->amount_signed,
                ])->toArray(),
            ];

            $categoryId = null;
            if ($type === 'income') {
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

                $categoryId = $category->id;

                $lockedTransaction->entries()->delete();
                $lockedTransaction->entries()->create([
                    'account_id' => $account->id,
                    'amount_signed' => $amount,
                ]);
            } elseif ($type === 'expense') {
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

                $feeAmount = array_key_exists('fee_amount', $data) ? round((float) $data['fee_amount'], 2) : (float) ($lockedTransaction->fee_amount ?? 0);
                if ($feeAmount < 0) {
                    throw ValidationException::withMessages([
                        'fee_amount' => 'Biaya admin transfer tidak boleh negatif.',
                    ]);
                }

                $feeCategoryId = null;
                if ($feeAmount > 0) {
                    $targetCatId = array_key_exists('fee_category_id', $data) ? $data['fee_category_id'] : $lockedTransaction->fee_category_id;
                    if (! empty($targetCatId)) {
                        $cat = Category::where('space_id', $space->id)
                            ->where('id', $targetCatId)
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
                'fee_amount' => $type === 'transfer' ? ($feeAmount ?? 0) : 0,
                'fee_category_id' => $type === 'transfer' ? ($feeCategoryId ?? null) : null,
                'member_id' => array_key_exists('member_id', $data) ? $data['member_id'] : $lockedTransaction->member_id,
                'updated_by' => $actor->id,
                'version' => $lockedTransaction->version + 1,
            ]);

            $lockedTransaction->load(['entries.account', 'category', 'feeCategory']);
            $afterSnapshot = [
                'type' => $lockedTransaction->type,
                'amount' => $lockedTransaction->totalAmount(),
                'fee_amount' => (float) ($lockedTransaction->fee_amount ?? 0),
                'fee_category_id' => $lockedTransaction->fee_category_id,
                'fee_category_name' => $lockedTransaction->feeCategory?->name,
                'transaction_date' => $lockedTransaction->transaction_date->toDateString(),
                'description' => $lockedTransaction->description,
                'category_id' => $lockedTransaction->category_id,
                'category_name' => $lockedTransaction->category?->name,
                'status' => $lockedTransaction->status,
                'version' => $lockedTransaction->version,
                'entries' => $lockedTransaction->entries->map(fn ($e) => [
                    'account_id' => $e->account_id,
                    'account_name' => $e->account?->name,
                    'amount_signed' => (int) $e->amount_signed,
                ])->toArray(),
            ];

            TransactionRevision::create([
                'transaction_id' => $lockedTransaction->id,
                'actor_id' => $actor->id,
                'before_data' => $beforeSnapshot,
                'after_data' => $afterSnapshot,
                'reason' => $reason ?? 'Koreksi rincian transaksi',
                'created_at' => now(),
            ]);

            return $lockedTransaction;
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

            $beforeSnapshot = [
                'type' => $lockedTransaction->type,
                'amount' => $lockedTransaction->totalAmount(),
                'fee_amount' => (float) ($lockedTransaction->fee_amount ?? 0),
                'fee_category_id' => $lockedTransaction->fee_category_id,
                'fee_category_name' => $lockedTransaction->feeCategory?->name,
                'transaction_date' => $lockedTransaction->transaction_date->toDateString(),
                'description' => $lockedTransaction->description,
                'category_id' => $lockedTransaction->category_id,
                'category_name' => $lockedTransaction->category?->name,
                'status' => $lockedTransaction->status,
                'version' => $lockedTransaction->version,
                'entries' => $lockedTransaction->entries->map(fn ($e) => [
                    'account_id' => $e->account_id,
                    'account_name' => $e->account?->name,
                    'amount_signed' => (int) $e->amount_signed,
                ])->toArray(),
            ];

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
        });
    }
}
