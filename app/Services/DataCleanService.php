<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\CreditPayment;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Member;
use App\Models\RecurringOccurrence;
use App\Models\RecurringSchedule;
use App\Models\ShortPlan;
use App\Models\ShortPlanItem;
use App\Models\Space;
use App\Models\TransactionEntry;
use App\Models\TransactionRevision;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DataCleanService
{
    public function cleanSpace(Space $space, array $options): array
    {
        $isAll = ! empty($options['all']);
        $cleanTransactions = $isAll || ! empty($options['transactions']);
        $cleanBudgets = $isAll || ! empty($options['budgets']);
        $cleanRecurring = $isAll || ! empty($options['recurring']);
        $cleanMembers = $isAll || ! empty($options['members']);
        $cleanAccounts = $isAll || ! empty($options['accounts']);
        $cleanCredit = $isAll || ! empty($options['credit']);
        $resetAccountBalances = ! empty($options['reset_account_balances']);
        $resetCategories = $isAll || ! empty($options['reset_categories']);

        if (! $cleanTransactions && ! $cleanBudgets && ! $cleanRecurring && ! $cleanMembers && ! $cleanAccounts && ! $cleanCredit && ! $resetAccountBalances && ! $resetCategories) {
            throw ValidationException::withMessages([
                'modules' => 'Pilih minimal satu modul yang ingin dibersihkan.',
            ]);
        }

        if ($cleanAccounts && ! $cleanTransactions) {
            $hasTx = FinancialTransaction::where('space_id', $space->id)->exists();
            if ($hasTx) {
                throw ValidationException::withMessages([
                    'accounts' => 'Rekening tidak dapat dihapus jika transaksi masih ada. Harap centang juga opsi Transaksi.',
                ]);
            }
        }

        if ($resetCategories && ! $cleanTransactions) {
            $hasTx = FinancialTransaction::where('space_id', $space->id)->exists();
            if ($hasTx) {
                throw ValidationException::withMessages([
                    'categories' => 'Kategori tidak dapat direset jika transaksi masih ada. Harap centang juga opsi Transaksi.',
                ]);
            }
        }

        $summary = [];

        DB::transaction(function () use (
            $space,
            $isAll,
            $cleanTransactions,
            $cleanBudgets,
            $cleanRecurring,
            $cleanMembers,
            $cleanAccounts,
            $cleanCredit,
            $resetAccountBalances,
            $resetCategories,
            &$summary
        ) {
            if ($cleanTransactions) {
                $txIds = FinancialTransaction::where('space_id', $space->id)->pluck('id');

                TransactionRevision::whereIn('transaction_id', $txIds)->delete();
                TransactionEntry::whereIn('transaction_id', $txIds)->delete();

                RecurringOccurrence::where('space_id', $space->id)
                    ->whereNotNull('transaction_id')
                    ->update([
                        'transaction_id' => null,
                        'status' => 'pending',
                        'confirmed_at' => null,
                        'confirmed_by' => null,
                    ]);

                ShortPlan::where('space_id', $space->id)
                    ->whereNotNull('financial_transaction_id')
                    ->update(['financial_transaction_id' => null]);

                $deletedTxCount = FinancialTransaction::where('space_id', $space->id)->delete();
                $summary['transactions'] = $deletedTxCount;
            }

            if ($cleanRecurring) {
                RecurringOccurrence::where('space_id', $space->id)->delete();
                $deletedRecurring = RecurringSchedule::where('space_id', $space->id)->delete();
                $summary['recurring'] = $deletedRecurring;
            }

            if ($cleanBudgets) {
                $deletedBudgets = Budget::where('space_id', $space->id)->delete();
                $summary['budgets'] = $deletedBudgets;
            }

            if ($cleanMembers) {
                FinancialTransaction::where('space_id', $space->id)->update(['member_id' => null]);
                RecurringSchedule::where('space_id', $space->id)->update(['member_id' => null]);
                ShortPlan::where('space_id', $space->id)->update(['member_id' => null]);
                $deletedMembers = Member::where('space_id', $space->id)->delete();
                $summary['members'] = $deletedMembers;
            }

            if ($cleanCredit) {
                CreditPayment::where('space_id', $space->id)->delete();
                Installment::where('space_id', $space->id)->delete();
                InstallmentPlan::where('space_id', $space->id)->delete();
                $creditAccountLedgerIds = CreditAccount::where('space_id', $space->id)->pluck('account_id');
                CreditAccount::where('space_id', $space->id)->delete();
                Account::whereIn('id', $creditAccountLedgerIds)->delete();
                $summary['credit'] = true;
            }

            if ($cleanAccounts) {
                RecurringSchedule::where('space_id', $space->id)->delete();
                ShortPlan::where('space_id', $space->id)->update(['account_id' => null]);
                DB::table('account_opening_balance_logs')
                    ->whereIn('account_id', Account::where('space_id', $space->id)->pluck('id'))
                    ->delete();
                $deletedAccounts = Account::where('space_id', $space->id)->delete();
                $summary['accounts'] = $deletedAccounts;
            } elseif ($resetAccountBalances) {
                Account::where('space_id', $space->id)->update(['opening_balance' => 0]);
                $summary['reset_balances'] = true;
            }

            if ($resetCategories) {
                ShortPlan::where('space_id', $space->id)->update(['category_id' => null]);
                Category::where('space_id', $space->id)->delete();
                $space->createDefaultCategories();
                $summary['categories_reset'] = true;
            }

            if ($isAll) {
                ShortPlanItem::where('space_id', $space->id)->delete();
                $summary['short_plans'] = ShortPlan::where('space_id', $space->id)->delete();
            }
        });

        return $summary;
    }
}
