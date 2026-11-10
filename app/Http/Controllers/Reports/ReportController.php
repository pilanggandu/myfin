<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\RecurringSchedule;
use App\Models\Space;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $space = app('currentSpace');
        $now = $space ? $space->now() : Carbon::now('Asia/Jakarta');

        $validated = $request->validate([
            'tab' => ['nullable', 'string', 'in:daily,analytics'],
            'view' => ['nullable', 'string', 'in:list,calendar'],
            'period_type' => ['nullable', 'string', 'in:month,custom'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'account_id' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
            'show_bills' => ['nullable', 'string', 'in:0,1,true,false'],
        ]);

        $activeTab = $validated['tab'] ?? 'daily';
        $viewMode = $validated['view'] ?? 'list';
        $periodType = $validated['period_type'] ?? 'month';
        $year = (int) ($validated['year'] ?? $now->year);
        $month = (int) ($validated['month'] ?? $now->month);
        $selectedAccountId = ! empty($validated['account_id']) ? (int) $validated['account_id'] : null;
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $showBills = $request->has('show_bills') ? $request->boolean('show_bills') : true;

        if ($periodType === 'custom' && ! empty($validated['start_date']) && ! empty($validated['end_date'])) {
            $parsedStart = Carbon::parse($validated['start_date'])->startOfDay();
            $parsedEnd = Carbon::parse($validated['end_date'])->endOfDay();

            if ($parsedStart->gt($parsedEnd)) {
                $temp = $parsedStart;
                $parsedStart = $parsedEnd->startOfDay();
                $parsedEnd = $temp->endOfDay();
            }

            $startDate = $parsedStart->toDateString();
            $endDate = $parsedEnd->toDateString();
            $periodLabel = $parsedStart->format('d M Y').' s/d '.$parsedEnd->format('d M Y');
        } else {
            $periodType = 'month';
            $cDate = Carbon::createFromDate($year, $month, 1, $space?->timezone ?: 'Asia/Jakarta');
            $startDate = $cDate->copy()->startOfMonth()->toDateString();
            $endDate = $cDate->copy()->endOfMonth()->toDateString();
            $periodLabel = $cDate->translatedFormat('F Y');
        }

        $spaceId = $space?->id;

        // Ringkasan Pemasukan & Pengeluaran Kas
        $incomeQuery = FinancialTransaction::query()
            ->where('financial_transactions.space_id', $spaceId)
            ->posted()
            ->income()
            ->whereBetween('financial_transactions.transaction_date', [$startDate, $endDate])
            ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id');

        $totalIncome = (int) $incomeQuery->sum('transaction_entries.amount_signed');
        $incomeCount = FinancialTransaction::query()
            ->where('space_id', $spaceId)
            ->posted()
            ->income()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->count();

        $expenseQuery = FinancialTransaction::query()
            ->where('financial_transactions.space_id', $spaceId)
            ->posted()
            ->expense()
            ->whereBetween('financial_transactions.transaction_date', [$startDate, $endDate])
            ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id');

        $directExpense = (int) $expenseQuery->sum(DB::raw('ABS(transaction_entries.amount_signed)'));
        $transferFeeSum = (int) FinancialTransaction::query()
            ->where('space_id', $spaceId)
            ->posted()
            ->transfer()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('fee_amount');

        $totalExpense = $directExpense + $transferFeeSum;
        $expenseCount = FinancialTransaction::query()
            ->where('space_id', $spaceId)
            ->posted()
            ->expense()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->count();

        $netCashFlow = $totalIncome - $totalExpense;

        $categoryBreakdown = DB::table('financial_transactions')
            ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
            ->join('categories', 'financial_transactions.category_id', '=', 'categories.id')
            ->where('financial_transactions.space_id', $spaceId)
            ->where('financial_transactions.status', 'posted')
            ->where('financial_transactions.type', 'expense')
            ->whereBetween('financial_transactions.transaction_date', [$startDate, $endDate])
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(DISTINCT financial_transactions.id) as transaction_count'),
                DB::raw('SUM(ABS(transaction_entries.amount_signed)) as total_amount')
            )
            ->orderByDesc('total_amount')
            ->get();

        $transferFeesByCategory = DB::table('financial_transactions')
            ->join('categories', 'financial_transactions.fee_category_id', '=', 'categories.id')
            ->where('financial_transactions.space_id', $spaceId)
            ->where('financial_transactions.status', 'posted')
            ->where('financial_transactions.type', 'transfer')
            ->where('financial_transactions.fee_amount', '>', 0)
            ->whereBetween('financial_transactions.transaction_date', [$startDate, $endDate])
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(DISTINCT financial_transactions.id) as transaction_count'),
                DB::raw('SUM(financial_transactions.fee_amount) as total_amount')
            )
            ->get()
            ->keyBy('id');

        $combinedCategories = collect();
        $processedFeeCatIds = [];

        foreach ($categoryBreakdown as $catItem) {
            $catId = $catItem->id;
            $catAmount = (int) $catItem->total_amount;
            $catCount = (int) $catItem->transaction_count;

            if ($transferFeesByCategory->has($catId)) {
                $feeItem = $transferFeesByCategory->get($catId);
                $catAmount += (int) $feeItem->total_amount;
                $catCount += (int) $feeItem->transaction_count;
                $processedFeeCatIds[] = $catId;
            }

            $percentage = $totalExpense > 0 ? round(($catAmount / $totalExpense) * 100, 1) : 0;
            $combinedCategories->push((object) [
                'id' => $catId,
                'name' => $catItem->name,
                'count' => $catCount,
                'amount' => $catAmount,
                'percentage' => $percentage,
            ]);
        }

        foreach ($transferFeesByCategory as $catId => $feeItem) {
            if (! in_array($catId, $processedFeeCatIds)) {
                $catAmount = (int) $feeItem->total_amount;
                $catCount = (int) $feeItem->transaction_count;
                $percentage = $totalExpense > 0 ? round(($catAmount / $totalExpense) * 100, 1) : 0;
                $combinedCategories->push((object) [
                    'id' => $catId,
                    'name' => $feeItem->name,
                    'count' => $catCount,
                    'amount' => $catAmount,
                    'percentage' => $percentage,
                ]);
            }
        }

        $categoryBreakdown = $combinedCategories->sortByDesc('amount')->values();

        $monthlyTrend = [];
        $startOfCurrentMonth = $now->copy()->startOfMonth();
        for ($i = 5; $i >= 0; $i--) {
            $mDate = $startOfCurrentMonth->copy()->subMonthsNoOverflow($i);
            $mStart = $mDate->copy()->startOfMonth()->toDateString();
            $mEnd = $mDate->copy()->endOfMonth()->toDateString();

            $mIncome = (int) DB::table('financial_transactions')
                ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
                ->where('financial_transactions.space_id', $spaceId)
                ->where('financial_transactions.status', 'posted')
                ->where('financial_transactions.type', 'income')
                ->whereBetween('financial_transactions.transaction_date', [$mStart, $mEnd])
                ->sum('transaction_entries.amount_signed');

            $mExpense = (int) DB::table('financial_transactions')
                ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
                ->where('financial_transactions.space_id', $spaceId)
                ->where('financial_transactions.status', 'posted')
                ->where('financial_transactions.type', 'expense')
                ->whereBetween('financial_transactions.transaction_date', [$mStart, $mEnd])
                ->sum(DB::raw('ABS(transaction_entries.amount_signed)'));

            $mTransferFee = (int) DB::table('financial_transactions')
                ->where('space_id', $spaceId)
                ->where('status', 'posted')
                ->where('type', 'transfer')
                ->whereBetween('transaction_date', [$mStart, $mEnd])
                ->sum('fee_amount');

            $mTotalExpense = $mExpense + $mTransferFee;

            $monthlyTrend[] = (object) [
                'label' => $mDate->translatedFormat('M Y'),
                'year' => (int) $mDate->year,
                'month' => (int) $mDate->month,
                'income' => $mIncome,
                'expense' => $mTotalExpense,
                'net' => $mIncome - $mTotalExpense,
            ];
        }

        $dailyData = $this->buildDailyCashflowData($space, $startDate, $endDate, $selectedAccountId, $sortOrder);

        $prevCarbon = Carbon::createFromDate($year, $month, 1, $space?->timezone ?: 'Asia/Jakarta')->subMonthNoOverflow();
        $nextCarbon = Carbon::createFromDate($year, $month, 1, $space?->timezone ?: 'Asia/Jakarta')->addMonthNoOverflow();

        $calendarWeeks = $this->buildCalendarGrid($year, $month, $dailyData['dailyCashflows'], $space);

        return view('reports.index', array_merge([
            'space' => $space,
            'activeTab' => $activeTab,
            'viewMode' => $viewMode,
            'periodType' => $periodType,
            'year' => $year,
            'month' => $month,
            'prevMonth' => $prevCarbon->month,
            'prevYear' => $prevCarbon->year,
            'nextMonth' => $nextCarbon->month,
            'nextYear' => $nextCarbon->year,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'periodLabel' => $periodLabel,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netCashFlow' => $netCashFlow,
            'incomeCount' => $incomeCount,
            'expenseCount' => $expenseCount,
            'categoryBreakdown' => $categoryBreakdown,
            'monthlyTrend' => $monthlyTrend,
            'selectedAccountId' => $selectedAccountId,
            'sortOrder' => $sortOrder,
            'calendarWeeks' => $calendarWeeks,
            'showBills' => $showBills,
        ], $dailyData));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $space = app('currentSpace');
        $now = $space ? $space->now() : Carbon::now('Asia/Jakarta');

        $validated = $request->validate([
            'period_type' => ['nullable', 'string', 'in:month,custom'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'account_id' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $periodType = $validated['period_type'] ?? 'month';
        $year = (int) ($validated['year'] ?? $now->year);
        $month = (int) ($validated['month'] ?? $now->month);
        $selectedAccountId = ! empty($validated['account_id']) ? (int) $validated['account_id'] : null;
        $sortOrder = $validated['sort_order'] ?? 'asc'; // Traditional export defaults to chronological

        if ($periodType === 'custom' && ! empty($validated['start_date']) && ! empty($validated['end_date'])) {
            $parsedStart = Carbon::parse($validated['start_date'])->startOfDay();
            $parsedEnd = Carbon::parse($validated['end_date'])->endOfDay();

            if ($parsedStart->gt($parsedEnd)) {
                $temp = $parsedStart;
                $parsedStart = $parsedEnd->startOfDay();
                $parsedEnd = $temp->endOfDay();
            }

            $startDate = $parsedStart->toDateString();
            $endDate = $parsedEnd->toDateString();
            $filenameLabel = $parsedStart->format('Ymd').'_'.$parsedEnd->format('Ymd');
        } else {
            $cDate = Carbon::createFromDate($year, $month, 1, $space?->timezone ?: 'Asia/Jakarta');
            $startDate = $cDate->copy()->startOfMonth()->toDateString();
            $endDate = $cDate->copy()->endOfMonth()->toDateString();
            $filenameLabel = $cDate->format('Y_m');
        }

        $dailyData = $this->buildDailyCashflowData($space, $startDate, $endDate, $selectedAccountId, $sortOrder);
        $dailyCashflows = $dailyData['dailyCashflows'];

        $filename = 'ARUS_Cashflow_RawData_'.$filenameLabel.'.csv';

        return response()->streamDownload(function () use ($dailyCashflows) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header
            fputcsv($handle, [
                'Tanggal',
                'Hari',
                'Waktu',
                'Keterangan',
                'Jenis',
                'Kategori',
                'Rekening Sumber',
                'Rekening Tujuan',
                'Anggota',
                'Kas Masuk (Rp)',
                'Kas Keluar (Rp)',
                'Net Kas (Rp)',
                'Saldo Kas Penutupan (Rp)',
            ]);

            foreach ($dailyCashflows as $day) {
                foreach ($day->items as $item) {
                    $tx = $item->transaction;
                    $src = $tx->sourceAccount();
                    $dst = $tx->destinationAccount();

                    $typeLabel = match ($tx->type) {
                        'income' => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                        'transfer' => 'Transfer',
                        default => $tx->type,
                    };

                    fputcsv($handle, [
                        $day->date,
                        $day->day_name,
                        $tx->formattedTime() ?: '-',
                        $tx->description,
                        $typeLabel,
                        $tx->category?->name ?? ($tx->isTransfer() ? 'Transfer' : '-'),
                        $src?->name ?? '-',
                        $dst?->name ?? '-',
                        $tx->member?->name ?? '-',
                        $item->inflow > 0 ? $item->inflow : 0,
                        $item->outflow > 0 ? $item->outflow : 0,
                        $item->net,
                        $day->closing_balance,
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Membangun ledger data mentah arus kas harian secara kronologis dan running balance.
     *
     * @return array{
     *     startingCashBalance: int,
     *     closingCashBalance: int,
     *     dailyCashflows: array<int, object>,
     *     cashAccounts: Collection,
     *     totalPeriodInflow: int,
     *     totalPeriodOutflow: int,
     *     totalPeriodNet: int,
     *     activeDaysCount: int
     * }
     */
    private function buildDailyCashflowData(?Space $space, string $startDate, string $endDate, ?int $selectedAccountId = null, string $sortOrder = 'desc'): array
    {
        $spaceId = $space?->id;

        $cashAccounts = $space
            ? $space->accounts()->whereNull('archived_at')->whereIn('type', ['bank', 'cash', 'e-wallet'])->orderBy('name')->get()
            : collect();

        // Saldo awal kas sebelum startDate
        $openingBalanceQuery = DB::table('transaction_entries')
            ->join('financial_transactions', 'transaction_entries.transaction_id', '=', 'financial_transactions.id')
            ->join('accounts', 'transaction_entries.account_id', '=', 'accounts.id')
            ->where('accounts.space_id', $spaceId)
            ->whereNull('accounts.archived_at')
            ->whereIn('accounts.type', ['bank', 'cash', 'e-wallet'])
            ->where('financial_transactions.status', 'posted')
            ->where('financial_transactions.transaction_date', '<', $startDate);

        if ($selectedAccountId) {
            $openingBalanceQuery->where('accounts.id', $selectedAccountId);
            $initialOpeningBalance = (int) ($space?->accounts()->find($selectedAccountId)?->opening_balance ?? 0);
        } else {
            $initialOpeningBalance = (int) ($space?->accounts()->whereNull('archived_at')->whereIn('type', ['bank', 'cash', 'e-wallet'])->sum('opening_balance') ?? 0);
        }

        $startingCashBalance = $initialOpeningBalance + (int) $openingBalanceQuery->sum('transaction_entries.amount_signed');

        // Query transaksi posted pada rentang tanggal
        $txQuery = FinancialTransaction::query()
            ->where('financial_transactions.space_id', $spaceId)
            ->posted()
            ->whereBetween('financial_transactions.transaction_date', [$startDate, $endDate])
            ->with(['category', 'entries.account', 'member', 'creator', 'installmentPlan.creditAccount']);

        if ($selectedAccountId) {
            $txQuery->whereHas('entries', function ($q) use ($selectedAccountId) {
                $q->where('account_id', $selectedAccountId);
            });
        } else {
            // Hanya transaksi yang melibatkan rekening kas aktual
            $txQuery->whereHas('entries.account', function ($q) {
                $q->whereIn('type', ['bank', 'cash', 'e-wallet']);
            });
        }

        // Urutkan selalu kronologis terlebih dahulu agar saldo kas berjalan akurat
        $transactions = $txQuery->oldestFirst()->get();

        $groupedByDate = $transactions->groupBy(fn ($tx) => $tx->transaction_date->toDateString());

        $runningBalance = $startingCashBalance;
        $dailyLedger = [];
        $totalPeriodInflow = 0;
        $totalPeriodOutflow = 0;

        foreach ($groupedByDate as $dateStr => $txs) {
            $dayInflow = 0;
            $dayOutflow = 0;
            $dayItems = [];

            foreach ($txs as $tx) {
                $inflow = 0;
                $outflow = 0;

                if ($selectedAccountId) {
                    $entry = $tx->entries->firstWhere('account_id', $selectedAccountId);
                    $signed = $entry ? (int) $entry->amount_signed : 0;
                    if ($signed > 0) {
                        $inflow = $signed;
                    } else {
                        $outflow = abs($signed);
                    }
                } else {
                    if ($tx->isIncome()) {
                        $inflow = $tx->totalAmount();
                    } elseif ($tx->isExpense()) {
                        $outflow = $tx->totalAmount();
                    } elseif ($tx->isTransfer()) {
                        $src = $tx->sourceAccount();
                        $dst = $tx->destinationAccount();
                        $srcIsCash = $src && in_array($src->type, ['bank', 'cash', 'e-wallet']);
                        $dstIsCash = $dst && in_array($dst->type, ['bank', 'cash', 'e-wallet']);

                        if ($dstIsCash && ! $srcIsCash) {
                            $inflow = $tx->totalAmount();
                        } elseif ($srcIsCash && ! $dstIsCash) {
                            $outflow = $tx->totalAmount() + (float) ($tx->fee_amount ?? 0);
                        } else {
                            if ((float) ($tx->fee_amount ?? 0) > 0) {
                                $outflow = (float) $tx->fee_amount;
                            }
                        }
                    }
                }

                $dayInflow += $inflow;
                $dayOutflow += $outflow;

                $dayItems[] = (object) [
                    'transaction' => $tx,
                    'inflow' => $inflow,
                    'outflow' => $outflow,
                    'net' => $inflow - $outflow,
                ];
            }

            $dayNet = $dayInflow - $dayOutflow;
            $runningBalance += $dayNet;

            $totalPeriodInflow += $dayInflow;
            $totalPeriodOutflow += $dayOutflow;

            $cDate = Carbon::parse($dateStr);

            $dailyLedger[] = (object) [
                'date' => $dateStr,
                'carbon' => $cDate,
                'day_name' => $cDate->translatedFormat('l'),
                'date_formatted' => $cDate->translatedFormat('d F Y'),
                'date_short' => $cDate->translatedFormat('d M Y'),
                'inflow' => $dayInflow,
                'outflow' => $dayOutflow,
                'net' => $dayNet,
                'closing_balance' => $runningBalance,
                'count' => count($dayItems),
                'items' => $dayItems,
            ];
        }

        $closingCashBalance = $runningBalance;
        $totalPeriodNet = $totalPeriodInflow - $totalPeriodOutflow;

        if ($sortOrder === 'desc') {
            $dailyCashflows = array_reverse($dailyLedger);
        } else {
            $dailyCashflows = $dailyLedger;
        }

        return [
            'startingCashBalance' => $startingCashBalance,
            'closingCashBalance' => $closingCashBalance,
            'dailyCashflows' => $dailyCashflows,
            'cashAccounts' => $cashAccounts,
            'totalPeriodInflow' => $totalPeriodInflow,
            'totalPeriodOutflow' => $totalPeriodOutflow,
            'totalPeriodNet' => $totalPeriodNet,
            'activeDaysCount' => count($dailyLedger),
        ];
    }

    private function buildCalendarGrid(int $year, int $month, array $dailyCashflows, ?Space $space): array
    {
        $timezone = $space?->timezone ?: 'Asia/Jakarta';
        $startOfMonth = Carbon::createFromDate($year, $month, 1, $timezone)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $todayStr = ($space ? $space->now() : Carbon::now($timezone))->toDateString();

        $cashflowMap = [];
        foreach ($dailyCashflows as $day) {
            $cashflowMap[$day->date] = $day;
        }

        $startDayOfWeek = $startOfMonth->dayOfWeekIso;
        $calendarStart = $startOfMonth->copy()->subDays($startDayOfWeek - 1);

        $endDayOfWeek = $endOfMonth->dayOfWeekIso;
        $calendarEnd = $endOfMonth->copy()->addDays(7 - $endDayOfWeek);

        $installments = $space ? Installment::where('space_id', $space->id)
            ->active()
            ->whereBetween('due_date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->with(['installmentPlan.creditAccount'])
            ->get() : collect();

        $recurringSchedules = $space ? RecurringSchedule::where('space_id', $space->id)
            ->active()
            ->with(['category', 'sourceAccount', 'occurrences' => function ($q) use ($calendarStart, $calendarEnd) {
                $q->whereBetween('due_date', [$calendarStart->toDateString(), $calendarEnd->toDateString()]);
            }])
            ->get() : collect();

        $calendarWeeks = [];
        $currentDay = $calendarStart->copy();

        while ($currentDay->lte($calendarEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateStr = $currentDay->toDateString();
                $isCurrentMonth = $currentDay->month === $month;
                $isToday = $dateStr === $todayStr;
                $cashflow = $cashflowMap[$dateStr] ?? null;

                $modalItems = [];
                if ($cashflow && ! empty($cashflow->items)) {
                    foreach ($cashflow->items as $item) {
                        $tx = $item->transaction;
                        $modalItems[] = [
                            'id' => $tx->id,
                            'description' => $tx->description,
                            'time' => $tx->formattedTime() ?: '-',
                            'type' => $tx->type,
                            'category_name' => $tx->category?->name ?? ($tx->isTransfer() ? 'Transfer' : '-'),
                            'source_account' => $tx->sourceAccount()?->name,
                            'dest_account' => $tx->destinationAccount()?->name,
                            'member_name' => $tx->member?->name,
                            'inflow' => $item->inflow,
                            'outflow' => $item->outflow,
                            'net' => $item->net,
                            'url' => route('transactions.show', $tx),
                        ];
                    }
                }

                $dayBills = [];

                foreach ($installments as $inst) {
                    if ($inst->due_date->toDateString() === $dateStr) {
                        $dayBills[] = [
                            'id' => $inst->id,
                            'kind' => 'installment',
                            'title' => $inst->installmentPlan?->name ?? 'Cicilan Kartu Kredit',
                            'subtitle' => ($inst->installmentPlan?->creditAccount?->name ?? 'Kartu Kredit').' (Ke-'.$inst->installment_number.'/'.($inst->installmentPlan?->tenor ?? '-').')',
                            'amount' => (float) $inst->total_amount,
                            'status' => $inst->status,
                            'status_label' => $inst->status === 'paid' ? 'Lunas' : 'Belum Lunas',
                            'url' => $inst->installment_plan_id ? route('credit.plans.show', $inst->installment_plan_id) : route('credit.index'),
                        ];
                    }
                }

                foreach ($recurringSchedules as $sch) {
                    $schStart = Carbon::parse($sch->start_date, $timezone)->startOfDay();
                    $schEnd = $sch->end_date ? Carbon::parse($sch->end_date, $timezone)->endOfDay() : null;

                    if ($currentDay->lt($schStart)) {
                        continue;
                    }
                    if ($schEnd && $currentDay->gt($schEnd)) {
                        continue;
                    }

                    $isDue = false;
                    if ($sch->frequency === 'daily') {
                        $isDue = true;
                    } elseif ($sch->frequency === 'weekly') {
                        $isDue = ($currentDay->dayOfWeekIso === $schStart->dayOfWeekIso);
                    } elseif ($sch->frequency === 'yearly') {
                        $anchor = (int) ($sch->anchor_day ?: $schStart->day);
                        $isDue = ($currentDay->month === $schStart->month && $currentDay->day === min($anchor, (int) $currentDay->daysInMonth));
                    } else {
                        $anchor = (int) ($sch->anchor_day ?: $schStart->day);
                        $isDue = ($currentDay->day === min($anchor, (int) $currentDay->daysInMonth));
                    }

                    if ($isDue) {
                        $occ = $sch->occurrences->first(function ($o) use ($dateStr) {
                            return $o->due_date->toDateString() === $dateStr;
                        });

                        if ($occ && in_array($occ->status, ['skipped', 'cancelled'])) {
                            continue;
                        }

                        $status = ($occ && $occ->status === 'confirmed') ? 'paid' : 'pending';
                        $amount = (float) (($occ && ! is_null($occ->amount)) ? $occ->amount : ($sch->amount ?? 0));

                        $dayBills[] = [
                            'id' => $sch->id,
                            'kind' => 'recurring',
                            'title' => $sch->name,
                            'subtitle' => $sch->category?->name ?? ($sch->isTransfer() ? 'Transfer Rutin' : 'Tagihan Rutin'),
                            'amount' => $amount,
                            'status' => $status,
                            'status_label' => $status === 'paid' ? 'Lunas' : 'Belum Lunas',
                            'url' => route('recurring.show', $sch),
                        ];
                    }
                }

                $billsPendingCount = 0;
                $billsTotalAmount = 0;
                $billsPendingAmount = 0;

                foreach ($dayBills as $b) {
                    $billsTotalAmount += $b['amount'];
                    if ($b['status'] !== 'paid') {
                        $billsPendingCount++;
                        $billsPendingAmount += $b['amount'];
                    }
                }

                $week[] = (object) [
                    'date' => $dateStr,
                    'day_number' => $currentDay->day,
                    'carbon' => $currentDay->copy(),
                    'is_current_month' => $isCurrentMonth,
                    'is_today' => $isToday,
                    'is_weekend' => in_array($currentDay->dayOfWeekIso, [6, 7]),
                    'has_transactions' => $cashflow !== null && $cashflow->count > 0,
                    'inflow' => $cashflow ? $cashflow->inflow : 0,
                    'outflow' => $cashflow ? $cashflow->outflow : 0,
                    'net' => $cashflow ? $cashflow->net : 0,
                    'count' => $cashflow ? $cashflow->count : 0,
                    'modal_items' => $modalItems,
                    'closing_balance' => $cashflow ? $cashflow->closing_balance : null,
                    'bills' => $dayBills,
                    'bills_count' => count($dayBills),
                    'bills_pending_count' => $billsPendingCount,
                    'bills_total_amount' => $billsTotalAmount,
                    'bills_pending_amount' => $billsPendingAmount,
                ];

                $currentDay->addDay();
            }
            $calendarWeeks[] = $week;
        }

        return $calendarWeeks;
    }
}
