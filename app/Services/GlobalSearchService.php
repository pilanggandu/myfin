<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\FinancialTransaction;
use App\Models\Goal;
use App\Models\InstallmentPlan;
use App\Models\Member;
use App\Models\RecurringSchedule;
use App\Models\Reminder;
use App\Models\ShortPlan;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class GlobalSearchService
{
    /**
     * Execute a space-scoped advanced search across transactions and ARUS modules.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function search(Space $space, User $user, array $filters): array
    {
        $queryText = trim((string) ($filters['q'] ?? ''));
        $scope = (string) ($filters['scope'] ?? 'all');
        $today = $space->now()->toDateString();

        $normalizedFilters = [
            'q' => $queryText,
            'scope' => $scope,
            'type' => $filters['type'] ?? null,
            'status' => $filters['status'] ?? 'posted',
            'account_id' => isset($filters['account_id']) ? (int) $filters['account_id'] : null,
            'category_id' => isset($filters['category_id']) ? (int) $filters['category_id'] : null,
            'member_id' => isset($filters['member_id']) ? (int) $filters['member_id'] : null,
            'start_date' => $filters['start_date'] ?? null,
            'end_date' => $filters['end_date'] ?? null,
            'min_amount' => isset($filters['min_amount']) && $filters['min_amount'] !== '' ? (float) $filters['min_amount'] : null,
            'max_amount' => isset($filters['max_amount']) && $filters['max_amount'] !== '' ? (float) $filters['max_amount'] : null,
            'include_future' => array_key_exists('include_future', $filters) ? (bool) $filters['include_future'] : true,
            'explicit_include_future' => ! empty($filters['include_future']),
            'sort' => (string) ($filters['sort'] ?? 'date_desc'),
        ];

        $hasTransactionFilters = $this->hasAdvancedTransactionFilters($normalizedFilters);
        $hasAnyInput = $queryText !== '' || $hasTransactionFilters;

        $groups = [
            'transactions' => [],
            'accounts' => [],
            'goals' => [],
            'short_plans' => [],
            'recurring' => [],
            'credit' => [],
            'categories' => [],
            'budgets' => [],
            'members' => [],
            'reminders' => [],
            'pages' => [],
        ];

        $transactionsTotalCount = 0;

        if ($hasAnyInput) {
            if ($scope === 'all' || $scope === 'transactions') {
                $txLimit = $scope === 'transactions' ? 25 : 10;
                $txResult = $this->searchTransactions($space, $normalizedFilters, $today, $txLimit);
                $groups['transactions'] = $txResult['items'];
                $transactionsTotalCount = $txResult['total'];
            }

            if ($queryText !== '' && ! $hasTransactionFilters) {
                if ($scope === 'all' || $scope === 'accounts') {
                    $groups['accounts'] = $this->searchAccounts($space, $queryText, $scope === 'accounts' ? 15 : 6);
                }

                if ($scope === 'all' || $scope === 'plans') {
                    $planLimit = $scope === 'plans' ? 12 : 5;
                    $groups['goals'] = $this->searchGoals($space, $queryText, $planLimit);
                    $groups['short_plans'] = $this->searchShortPlans($space, $queryText, $planLimit);
                    $groups['recurring'] = $this->searchRecurring($space, $queryText, $planLimit);
                }

                if ($scope === 'all' || $scope === 'other') {
                    $otherLimit = $scope === 'other' ? 10 : 5;
                    $groups['credit'] = $this->searchCredit($space, $queryText, $otherLimit);
                    $groups['categories'] = $this->searchCategories($space, $queryText, $otherLimit);
                    $groups['budgets'] = $this->searchBudgets($space, $queryText, $otherLimit);
                    $groups['members'] = $this->searchMembers($space, $queryText, $otherLimit);
                    $groups['reminders'] = $this->searchReminders($space, $queryText, $otherLimit);
                }

                if ($scope === 'all' || $scope === 'pages') {
                    $groups['pages'] = $this->searchPages($space, $queryText, $scope === 'pages' ? 15 : 6);
                }
            } elseif ($queryText !== '' && $scope === 'pages') {
                $groups['pages'] = $this->searchPages($space, $queryText, 15);
            }
        }

        $counts = [
            'transactions' => $transactionsTotalCount,
            'accounts' => count($groups['accounts']),
            'plans' => count($groups['goals']) + count($groups['short_plans']) + count($groups['recurring']),
            'other' => count($groups['credit']) + count($groups['categories']) + count($groups['budgets']) + count($groups['members']) + count($groups['reminders']),
            'pages' => count($groups['pages']),
        ];

        $totalReturned = array_sum(array_map('count', $groups));

        return [
            'query' => $queryText,
            'filters' => $normalizedFilters,
            'has_transaction_filters' => $hasTransactionFilters,
            'total_returned' => $totalReturned,
            'counts' => $counts,
            'groups' => $groups,
            'transactions_index_url' => $this->buildTransactionsIndexUrl($normalizedFilters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function hasAdvancedTransactionFilters(array $filters): bool
    {
        return ! empty($filters['type'])
            || ($filters['status'] !== 'posted')
            || ! empty($filters['account_id'])
            || ! empty($filters['category_id'])
            || ! empty($filters['member_id'])
            || ! empty($filters['start_date'])
            || ! empty($filters['end_date'])
            || $filters['min_amount'] !== null
            || $filters['max_amount'] !== null
            || ($filters['include_future'] === false)
            || ($filters['sort'] !== 'date_desc');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total: int, items: list<array<string, mixed>>}
     */
    protected function searchTransactions(Space $space, array $filters, string $today, int $limit): array
    {
        $query = $space->financialTransactions()
            ->with(['category', 'feeCategory', 'entries.account', 'member']);

        if (! empty($filters['type']) && in_array($filters['type'], ['income', 'expense', 'transfer', 'credit_payment'], true)) {
            $query->where('type', $filters['type']);
        }

        $status = $filters['status'] ?? 'posted';
        if ($status !== 'all') {
            $query->where('status', in_array($status, ['posted', 'void'], true) ? $status : 'posted');
        }

        if (! empty($filters['account_id'])) {
            $accountId = (int) $filters['account_id'];
            $query->whereHas('entries', fn (Builder $q) => $q->where('account_id', $accountId));
        }

        if (! empty($filters['category_id'])) {
            $categoryId = (int) $filters['category_id'];
            $query->where(function (Builder $q) use ($categoryId): void {
                $q->where('category_id', $categoryId)
                    ->orWhere('fee_category_id', $categoryId);
            });
        }

        if (! empty($filters['member_id'])) {
            $query->where('member_id', (int) $filters['member_id']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['end_date']);
        } elseif (! $filters['include_future']) {
            $query->whereDate('transaction_date', '<=', $today);
        }

        if ($filters['min_amount'] !== null) {
            $min = (float) $filters['min_amount'];
            $query->whereHas('entries', function (Builder $q) use ($min): void {
                $q->where('amount_signed', '>=', $min)
                    ->orWhere('amount_signed', '<=', -$min);
            });
        }

        if ($filters['max_amount'] !== null) {
            $max = (float) $filters['max_amount'];
            $query->whereHas('entries', function (Builder $q) use ($max): void {
                $q->whereBetween('amount_signed', [-$max, $max]);
            });
        }

        if ($filters['q'] !== '') {
            $query->search($filters['q']);
        }

        $total = (clone $query)->count();

        match ($filters['sort']) {
            'date_asc' => $query->oldestFirst(),
            'amount_desc' => $query->orderByRaw(
                '(SELECT COALESCE(MAX(ABS(amount_signed)), 0) FROM transaction_entries WHERE transaction_entries.transaction_id = financial_transactions.id) DESC'
            )->latestFirst(),
            'amount_asc' => $query->orderByRaw(
                '(SELECT COALESCE(MAX(ABS(amount_signed)), 0) FROM transaction_entries WHERE transaction_entries.transaction_id = financial_transactions.id) ASC'
            )->latestFirst(),
            default => $query->latestFirst(),
        };

        $items = $query->limit($limit)->get()->map(function (FinancialTransaction $tx) use ($today): array {
            $source = $tx->sourceAccount();
            $destination = $tx->destinationAccount();

            $accountSummary = match ($tx->type) {
                'transfer', 'credit_payment' => trim(($source?->name ?? 'Rekening').' -> '.($destination?->name ?? 'Tujuan')),
                'expense' => $source?->name ?? 'Rekening',
                default => $destination?->name ?? 'Rekening',
            };

            $typeLabel = match ($tx->type) {
                'income' => 'Pemasukan',
                'expense' => 'Pengeluaran',
                'transfer' => 'Transfer',
                'credit_payment' => 'Bayar Kredit',
                default => ucfirst((string) $tx->type),
            };

            $amount = $tx->totalAmount();
            $prefix = match ($tx->type) {
                'income' => '+',
                'expense' => '-',
                default => '',
            };

            return [
                'id' => $tx->id,
                'description' => $tx->description,
                'type' => $tx->type,
                'type_label' => $typeLabel,
                'status' => $tx->status,
                'transaction_date' => $tx->transaction_date?->toDateString(),
                'formatted_date' => $tx->transaction_date?->translatedFormat('d M Y'),
                'formatted_time' => $tx->formattedTime(),
                'amount' => $amount,
                'formatted_amount' => $prefix.'Rp '.number_format($amount, 0, ',', '.'),
                'fee_amount' => (float) ($tx->fee_amount ?? 0),
                'category_name' => $tx->category?->name ?? $tx->feeCategory?->name,
                'account_summary' => $accountSummary,
                'member_name' => $tx->member?->name,
                'is_upcoming' => $tx->transaction_date && $tx->transaction_date->toDateString() > $today,
                'url' => route('transactions.show', $tx),
            ];
        })->values()->all();

        return [
            'total' => $total,
            'items' => $items,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function searchAccounts(Space $space, string $term, int $limit): array
    {
        $like = "%{$term}%";
        $matchingTypes = collect(Account::TYPE_LABELS)
            ->filter(fn (string $label, string $key): bool => stripos($label, $term) !== false || stripos($key, $term) !== false)
            ->keys()
            ->all();

        return $space->accounts()
            ->withSum(['entries as posted_entries_sum' => function (Builder $q): void {
                $q->whereHas('transaction', fn (Builder $tq) => $tq->where('status', 'posted'));
            }], 'amount_signed')
            ->where(function (Builder $q) use ($like, $matchingTypes): void {
                $q->where('name', 'like', $like);
                if ($matchingTypes !== []) {
                    $q->orWhereIn('type', $matchingTypes);
                }
            })
            ->orderByRaw('CASE WHEN archived_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (Account $account): array {
                $balance = round((float) $account->opening_balance + (float) ($account->posted_entries_sum ?? 0), 2);
                $decimals = fmod($balance, 1) == 0.0 ? 0 : 2;

                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type,
                    'type_label' => Account::labelForType($account->type),
                    'balance' => $balance,
                    'formatted_balance' => 'Rp '.number_format($balance, $decimals, ',', '.'),
                    'is_archived' => $account->isArchived(),
                    'is_default' => $account->isDefault(),
                    'url' => route('accounts.show', $account),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function searchGoals(Space $space, string $term, int $limit): array
    {
        $like = "%{$term}%";

        return $space->goals()
            ->where(function (Builder $q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('notes', 'like', $like);
            })
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (Goal $goal): array {
                $statusLabel = match ($goal->status) {
                    'active' => 'Aktif',
                    'achieved' => 'Tercapai',
                    'paused' => 'Dijeda',
                    'cancelled' => 'Dibatalkan',
                    default => ucfirst((string) $goal->status),
                };

                return [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'status' => $goal->status,
                    'status_label' => $statusLabel,
                    'percentage' => $goal->percentage(),
                    'formatted_current' => 'Rp '.number_format((float) $goal->current_amount, 0, ',', '.'),
                    'formatted_target' => 'Rp '.number_format((float) $goal->target_amount, 0, ',', '.'),
                    'target_date' => $goal->target_date?->translatedFormat('d M Y'),
                    'url' => route('goals.show', $goal),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function searchShortPlans(Space $space, string $term, int $limit): array
    {
        $like = "%{$term}%";

        return $space->shortPlans()
            ->where(function (Builder $q) use ($like): void {
                $q->where('title', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhereHas('items', fn (Builder $iq) => $iq->where('title', 'like', $like));
            })
            ->orderByRaw("CASE WHEN status = 'planned' THEN 0 ELSE 1 END")
            ->orderBy('target_date')
            ->limit($limit)
            ->get()
            ->map(function (ShortPlan $plan): array {
                $statusLabel = match ($plan->status) {
                    'planned' => 'Direncanakan',
                    'done' => 'Selesai',
                    'cancelled' => 'Dibatalkan',
                    default => ucfirst((string) $plan->status),
                };

                $amount = (float) ($plan->actual_amount ?: $plan->planned_amount);

                return [
                    'id' => $plan->id,
                    'title' => $plan->title,
                    'status' => $plan->status,
                    'status_label' => $statusLabel,
                    'priority' => $plan->priority,
                    'formatted_amount' => 'Rp '.number_format($amount, 0, ',', '.'),
                    'target_date' => $plan->target_date?->translatedFormat('d M Y'),
                    'url' => route('short-plans.show', $plan),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function searchRecurring(Space $space, string $term, int $limit): array
    {
        $like = "%{$term}%";

        return $space->recurringSchedules()
            ->where(function (Builder $q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('next_due_date')
            ->limit($limit)
            ->get()
            ->map(function (RecurringSchedule $schedule): array {
                $freqLabel = match ($schedule->frequency) {
                    'daily' => 'Harian',
                    'weekly' => 'Mingguan',
                    'monthly' => 'Bulanan',
                    'yearly' => 'Tahunan',
                    default => ucfirst((string) $schedule->frequency),
                };

                return [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'type' => $schedule->type,
                    'frequency_label' => $freqLabel,
                    'status' => $schedule->status,
                    'formatted_amount' => 'Rp '.number_format((float) $schedule->amount, 0, ',', '.'),
                    'next_due_date' => $schedule->next_due_date?->translatedFormat('d M Y'),
                    'url' => route('recurring.show', $schedule),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function searchCredit(Space $space, string $term, int $limit): array
    {
        $like = "%{$term}%";

        $creditAccounts = $space->creditAccounts()
            ->where('name', 'like', $like)
            ->orderByRaw('CASE WHEN archived_at IS NULL THEN 0 ELSE 1 END')
            ->limit($limit)
            ->get()
            ->map(fn (CreditAccount $account): array => [
                'id' => $account->id,
                'kind' => 'credit_account',
                'kind_label' => $account->type === 'paylater' ? 'PayLater' : 'Kartu Kredit',
                'name' => $account->name,
                'meta' => 'Limit Rp '.number_format((float) $account->credit_limit, 0, ',', '.').' | Jatuh tempo tgl '.$account->due_day,
                'url' => route('credit.accounts.statement', $account),
            ]);

        $installmentPlans = $space->installmentPlans()
            ->with('creditAccount')
            ->where(function (Builder $q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('notes', 'like', $like);
            })
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->limit($limit)
            ->get()
            ->map(fn (InstallmentPlan $plan): array => [
                'id' => $plan->id,
                'kind' => 'installment_plan',
                'kind_label' => 'Cicilan '.$plan->tenor.'x',
                'name' => $plan->name,
                'meta' => ($plan->creditAccount?->name ?? 'Kredit').' | Rp '.number_format((float) $plan->total_amount, 0, ',', '.'),
                'url' => route('credit.plans.show', $plan),
            ]);

        return $creditAccounts->concat($installmentPlans)->take($limit)->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function searchCategories(Space $space, string $term, int $limit): array
    {
        $like = "%{$term}%";

        return $space->categories()
            ->where('name', 'like', $like)
            ->orderByRaw('CASE WHEN archived_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
                'type_label' => $category->isIncome() ? 'Pemasukan' : 'Pengeluaran',
                'is_archived' => $category->isArchived(),
                'url' => route('transactions.index', ['category_id' => $category->id]),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function searchBudgets(Space $space, string $term, int $limit): array
    {
        $like = "%{$term}%";

        return $space->budgets()
            ->with('category')
            ->whereHas('category', fn (Builder $q) => $q->where('name', 'like', $like))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit($limit)
            ->get()
            ->map(fn (Budget $budget): array => [
                'id' => $budget->id,
                'category_name' => $budget->category?->name ?? 'Kategori',
                'period' => Carbon::createFromDate($budget->year, $budget->month, 1)->translatedFormat('F Y'),
                'formatted_limit' => 'Rp '.number_format((float) $budget->limit_amount, 0, ',', '.'),
                'url' => route('budgets.index', ['year' => $budget->year, 'month' => $budget->month]),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function searchMembers(Space $space, string $term, int $limit): array
    {
        $like = "%{$term}%";

        return $space->customMembers()
            ->where(function (Builder $q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('notes', 'like', $like);
            })
            ->orderByRaw('CASE WHEN archived_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Member $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'is_archived' => $member->isArchived(),
                'url' => route('transactions.index', ['member_id' => $member->id]),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function searchReminders(Space $space, string $term, int $limit): array
    {
        $like = "%{$term}%";

        return Reminder::query()
            ->where('space_id', $space->id)
            ->where(function (Builder $q) use ($like): void {
                $q->where('title', 'like', $like)
                    ->orWhere('notes', 'like', $like);
            })
            ->orderByDesc('is_active')
            ->orderBy('due_date')
            ->limit($limit)
            ->get()
            ->map(fn (Reminder $reminder): array => [
                'id' => $reminder->id,
                'title' => $reminder->title,
                'type_label' => $reminder->typeLabel(),
                'due_date' => $reminder->due_date?->translatedFormat('d M Y'),
                'is_active' => (bool) $reminder->is_active,
                'url' => route('reminders.index'),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function searchPages(Space $space, string $term, int $limit): array
    {
        $pages = [
            ['title' => 'Dashboard', 'kind_label' => 'Menu', 'subtitle' => 'Ringkasan arus kas, saldo, dan aktivitas terbaru', 'keywords' => 'dashboard beranda ringkasan home', 'url' => route('dashboard')],
            ['title' => 'Daftar Transaksi', 'kind_label' => 'Menu', 'subtitle' => 'Riwayat pemasukan, pengeluaran, dan transfer', 'keywords' => 'transaksi mutasi riwayat pengeluaran pemasukan', 'url' => route('transactions.index')],
            ['title' => 'Catat Transaksi Baru', 'kind_label' => 'Menu', 'subtitle' => 'Form lengkap pemasukan, pengeluaran, atau transfer', 'keywords' => 'catat buat tambah transaksi baru', 'url' => route('transactions.create')],
            ['title' => 'Rekening & Wallet', 'kind_label' => 'Menu', 'subtitle' => 'Kelola akun bank, e-wallet, kas tunai, dan investasi', 'keywords' => 'rekening bank wallet dompet kas saldo akun', 'url' => route('accounts.index')],
            ['title' => 'Kategori Transaksi', 'kind_label' => 'Menu', 'subtitle' => 'Kelola kategori pemasukan dan pengeluaran', 'keywords' => 'kategori label pos pengeluaran pemasukan', 'url' => route('categories.index')],
            ['title' => 'Anggaran Bulanan', 'kind_label' => 'Menu', 'subtitle' => 'Pantau batas pengeluaran per kategori', 'keywords' => 'anggaran budget limit batas bulanan', 'url' => route('budgets.index')],
            ['title' => 'Laporan Keuangan', 'kind_label' => 'Menu', 'subtitle' => 'Analisis arus kas, tren kategori, dan ekspor CSV', 'keywords' => 'laporan report analisis grafik statistik csv', 'url' => route('reports.index')],
            ['title' => 'Jadwal Berulang', 'kind_label' => 'Menu', 'subtitle' => 'Tagihan rutin, gaji berkala, dan langganan', 'keywords' => 'jadwal berulang recurring tagihan rutin langganan', 'url' => route('recurring.index')],
            ['title' => 'Kredit & Cicilan', 'kind_label' => 'Menu', 'subtitle' => 'Kartu kredit, paylater, tagihan bulanan, dan tenor', 'keywords' => 'kredit cicilan kartu paylater tagihan hutang', 'url' => route('credit.index')],
            ['title' => 'Target & Bersama', 'kind_label' => 'Menu', 'subtitle' => 'Target tabungan pribadi dan kontribusi bersama', 'keywords' => 'target goal tabungan impian dana bersama', 'url' => route('goals.index')],
            ['title' => 'Short Plan', 'kind_label' => 'Menu', 'subtitle' => 'Rencana belanja jangka pendek dan daftar kebutuhan', 'keywords' => 'short plan rencana belanja checklist', 'url' => route('short-plans.index')],
            ['title' => 'Pengingat', 'kind_label' => 'Menu', 'subtitle' => 'Notifikasi pengingat tagihan dan pencatatan harian', 'keywords' => 'pengingat reminder notifikasi alarm email whatsapp', 'url' => route('reminders.index')],
            ['title' => 'Log Aktivitas', 'kind_label' => 'General', 'subtitle' => 'Audit riwayat perubahan dan aktivitas ruang', 'keywords' => 'log aktivitas audit riwayat history', 'url' => route('activity-logs.index')],
            ['title' => 'Mata Uang', 'kind_label' => 'General', 'subtitle' => 'Kurs mata uang asing dan konverter nilai tukar', 'keywords' => 'mata uang currency kurs konversi usd idr', 'url' => route('currencies.index')],
            ['title' => 'Ruang Keuangan', 'kind_label' => 'General', 'subtitle' => 'Kelola ruang personal dan ruang bersama', 'keywords' => 'ruang space workspace personal bersama', 'url' => route('spaces.index')],
            ['title' => 'Profil Pengguna', 'kind_label' => 'General', 'subtitle' => 'Pengaturan akun, kata sandi, dan pembersihan data', 'keywords' => 'profil akun user password keamanan pengaturan', 'url' => route('profile.edit')],
        ];

        if (! $space->isPersonal()) {
            $pages[] = [
                'title' => 'Member Ruang Bersama',
                'kind_label' => 'General',
                'subtitle' => 'Kelola anggota dan partisipan transaksi ruang bersama',
                'keywords' => 'member anggota tim keluarga bersama',
                'url' => route('members.index'),
            ];
        }

        $needle = mb_strtolower($term);

        return collect($pages)
            ->filter(function (array $page) use ($needle): bool {
                $haystack = mb_strtolower($page['title'].' '.$page['subtitle'].' '.$page['keywords'].' '.$page['kind_label']);

                return str_contains($haystack, $needle);
            })
            ->take($limit)
            ->map(fn (array $page): array => [
                'title' => $page['title'],
                'kind_label' => $page['kind_label'],
                'subtitle' => $page['subtitle'],
                'url' => $page['url'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function buildTransactionsIndexUrl(array $filters): string
    {
        $params = [];

        if ($filters['q'] !== '') {
            $params['search'] = $filters['q'];
        }
        if (! empty($filters['type'])) {
            $params['type'] = $filters['type'];
        }
        if (! empty($filters['status']) && $filters['status'] !== 'posted') {
            $params['status'] = $filters['status'];
        }
        if (! empty($filters['account_id'])) {
            $params['account_id'] = $filters['account_id'];
        }
        if (! empty($filters['category_id'])) {
            $params['category_id'] = $filters['category_id'];
        }
        if (! empty($filters['member_id'])) {
            $params['member_id'] = $filters['member_id'];
        }
        if (! empty($filters['start_date'])) {
            $params['start_date'] = $filters['start_date'];
        }
        if (! empty($filters['end_date'])) {
            $params['end_date'] = $filters['end_date'];
        }
        if ($filters['min_amount'] !== null) {
            $params['min_amount'] = $filters['min_amount'];
        }
        if ($filters['max_amount'] !== null) {
            $params['max_amount'] = $filters['max_amount'];
        }
        if (! empty($filters['explicit_include_future'])) {
            $params['include_future'] = 1;
        }
        if (in_array($filters['sort'], ['date_asc', 'date_desc', 'amount_desc', 'amount_asc'], true) && $filters['sort'] !== 'date_desc') {
            $params['sort'] = $filters['sort'];
        }

        return route('transactions.index', $params);
    }
}
