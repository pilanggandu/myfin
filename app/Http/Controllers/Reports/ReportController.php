<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $space = app('currentSpace');
        $now = $space ? $space->now() : Carbon::now('Asia/Jakarta');

        $validated = $request->validate([
            'period_type' => ['nullable', 'string', 'in:month,custom'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $periodType = $validated['period_type'] ?? 'month';
        $year = (int) ($validated['year'] ?? $now->year);
        $month = (int) ($validated['month'] ?? $now->month);

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
            $periodLabel = $parsedStart->format('d M Y') . ' s/d ' . $parsedEnd->format('d M Y');
        } else {
            $periodType = 'month';
            $cDate = Carbon::createFromDate($year, $month, 1, $space?->timezone ?: 'Asia/Jakarta');
            $startDate = $cDate->copy()->startOfMonth()->toDateString();
            $endDate = $cDate->copy()->endOfMonth()->toDateString();
            $periodLabel = $cDate->translatedFormat('F Y');
        }

        $spaceId = $space?->id;

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

        $totalExpense = (int) $expenseQuery->sum(DB::raw('ABS(transaction_entries.amount_signed)'));
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

        $categoryBreakdown = $categoryBreakdown->map(function ($item) use ($totalExpense) {
            $amount = (int) $item->total_amount;
            $percentage = $totalExpense > 0 ? round(($amount / $totalExpense) * 100, 1) : 0;
            return (object) [
                'id' => $item->id,
                'name' => $item->name,
                'count' => (int) $item->transaction_count,
                'amount' => $amount,
                'percentage' => $percentage,
            ];
        });

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

            $monthlyTrend[] = (object) [
                'label' => $mDate->translatedFormat('M Y'),
                'year' => (int) $mDate->year,
                'month' => (int) $mDate->month,
                'income' => $mIncome,
                'expense' => $mExpense,
                'net' => $mIncome - $mExpense,
            ];
        }

        return view('reports.index', compact(
            'space',
            'periodType',
            'year',
            'month',
            'startDate',
            'endDate',
            'periodLabel',
            'totalIncome',
            'totalExpense',
            'netCashFlow',
            'incomeCount',
            'expenseCount',
            'categoryBreakdown',
            'monthlyTrend'
        ));
    }
}
