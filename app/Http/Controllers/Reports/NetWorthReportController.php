<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Services\ActivityLogService;
use App\Services\NetWorthAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NetWorthReportController extends Controller
{
    public function __construct(
        protected NetWorthAnalysisService $netWorthService,
        protected ActivityLogService $activityLog
    ) {}

    public function index(Request $request): View
    {
        /** @var Space $space */
        $space = app('currentSpace');
        if ($request->filled('space_id')) {
            $requestedSpace = Space::findOrFail($request->input('space_id'));
            Gate::authorize('view', $requestedSpace);
            $space = $requestedSpace;
        } elseif ($space) {
            Gate::authorize('view', $space);
        } else {
            abort(403);
        }

        $now = $space->now();
        $horizon = (int) $request->input('horizon', 6);
        if (! in_array($horizon, [3, 6, 12, 24], true)) {
            $horizon = 6;
        }

        $year = (int) $request->input('year', $now->year);
        $month = (int) $request->input('month', $now->month);

        $balanceSheet = $this->netWorthService->getCurrentBalanceSheet($space);
        $trends = $this->netWorthService->getHistoricalNetWorthTrend($space, $horizon, 'month', $year, $month);
        $debtHistory = $this->netWorthService->getDebtHistorySummary($space);
        $insights = $this->netWorthService->generateInsights($balanceSheet, $trends, $debtHistory);

        return view('reports.net-worth', compact(
            'space',
            'horizon',
            'year',
            'month',
            'balanceSheet',
            'trends',
            'debtHistory',
            'insights'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        /** @var Space $space */
        $space = app('currentSpace');
        if ($request->filled('space_id')) {
            $requestedSpace = Space::findOrFail($request->input('space_id'));
            Gate::authorize('view', $requestedSpace);
            $space = $requestedSpace;
        } elseif ($space) {
            Gate::authorize('view', $space);
        } else {
            abort(403);
        }

        $now = $space->now();
        $horizon = (int) $request->input('horizon', 12);
        if (! in_array($horizon, [3, 6, 12, 24], true)) {
            $horizon = 12;
        }

        $year = (int) $request->input('year', $now->year);
        $month = (int) $request->input('month', $now->month);

        $trends = $this->netWorthService->getHistoricalNetWorthTrend($space, $horizon, 'month', $year, $month);

        $filename = 'analisis-kekayaan-bersih-'.$space->id.'-'.date('Ymd-His').'.csv';

        $callback = function () use ($trends) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM

            // Header kolom CSV
            fputcsv($output, [
                'Bulan',
                'Tanggal Tutup Buku',
                'Total Aset (IDR)',
                'Kas & Bank (IDR)',
                'Investasi (IDR)',
                'Total Kewajiban (IDR)',
                'Utang Kartu Kredit (IDR)',
                'Pokok Cicilan (IDR)',
                'Kekayaan Bersih (IDR)',
                'Perubahan Net Worth (IDR)',
                'Pertumbuhan (%)',
                'Debt-to-Asset Ratio (%)',
            ]);

            foreach ($trends['trend'] as $row) {
                fputcsv($output, [
                    $row['full_label'],
                    $row['date'],
                    (int) round($row['assets']),
                    (int) round($row['liquid_assets']),
                    (int) round($row['investment_assets']),
                    (int) round($row['liabilities']),
                    (int) round($row['credit_debt']),
                    (int) round($row['installment_debt']),
                    (int) round($row['net_worth']),
                    (int) round($row['delta']),
                    $row['delta_percentage'].'%',
                    $row['debt_to_asset_ratio'].'%',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
