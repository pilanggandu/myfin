<?php

namespace App\Http\Controllers\Projections;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projection\CashflowProjectionRequest;
use App\Models\Space;
use App\Services\ActivityLogService;
use App\Services\CashflowProjectionService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashflowProjectionController extends Controller
{
    public function __construct(
        protected CashflowProjectionService $projectionService,
        protected ActivityLogService $activityLog
    ) {}

    public function index(CashflowProjectionRequest $request): View
    {
        /** @var Space $space */
        $space = app('currentSpace');
        $validated = $request->validated();

        $activeTab = $validated['tab'] ?? 'projection';
        $horizonDays = (int) ($validated['horizon'] ?? 30);
        $selectedAccountId = ! empty($validated['account_id']) ? (int) $validated['account_id'] : null;

        $includeRecurring = $request->has('include_recurring') ? $request->boolean('include_recurring') : true;
        $includeInstallments = $request->has('include_installments') ? $request->boolean('include_installments') : true;
        $includePlannedIncomes = $request->has('include_planned_incomes') ? $request->boolean('include_planned_incomes') : true;
        $includeShortPlans = $request->has('include_short_plans') ? $request->boolean('include_short_plans') : false;

        $projectionOptions = [
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'horizon_days' => $horizonDays,
            'account_id' => $selectedAccountId,
            'include_recurring' => $includeRecurring,
            'include_installments' => $includeInstallments,
            'include_planned_incomes' => $includePlannedIncomes,
            'include_short_plans' => $includeShortPlans,
        ];

        $projectionData = $this->projectionService->project($space, $projectionOptions);

        // Data pendukung untuk formulir & modal
        $accounts = $space->accounts()->whereNull('archived_at')->orderBy('type')->orderBy('name')->get();
        $incomeCategories = $space->categories()->income()->active()->orderBy('name')->get();
        $members = $space->members()->orderBy('name')->get();

        // Daftar Rencana Pemasukan untuk tab manajemen rencana pemasukan
        $plannedIncomes = $space->plannedIncomes()
            ->with(['account', 'category', 'member', 'creator'])
            ->orderByRaw("CASE status WHEN 'planned' THEN 0 WHEN 'received' THEN 1 ELSE 2 END ASC")
            ->orderBy('expected_date')
            ->orderByDesc('id')
            ->get();

        $plannedIncomeSummary = [
            'total_planned' => (float) $plannedIncomes->where('status', 'planned')->sum('amount'),
            'total_received' => (float) $plannedIncomes->where('status', 'received')->sum(fn ($p) => $p->actual_amount ?? $p->amount),
            'count_planned' => $plannedIncomes->where('status', 'planned')->count(),
            'count_received' => $plannedIncomes->where('status', 'received')->count(),
            'count_cancelled' => $plannedIncomes->where('status', 'cancelled')->count(),
        ];

        return view('projections.index', [
            'projection' => $projectionData,
            'activeTab' => $activeTab,
            'horizonDays' => $horizonDays,
            'selectedAccountId' => $selectedAccountId,
            'includeRecurring' => $includeRecurring,
            'includeInstallments' => $includeInstallments,
            'includePlannedIncomes' => $includePlannedIncomes,
            'includeShortPlans' => $includeShortPlans,
            'accounts' => $accounts,
            'incomeCategories' => $incomeCategories,
            'members' => $members,
            'plannedIncomes' => $plannedIncomes,
            'plannedIncomeSummary' => $plannedIncomeSummary,
        ]);
    }

    public function exportCsv(CashflowProjectionRequest $request): StreamedResponse
    {
        /** @var Space $space */
        $space = app('currentSpace');
        $validated = $request->validated();

        $horizonDays = (int) ($validated['horizon'] ?? 30);
        $selectedAccountId = ! empty($validated['account_id']) ? (int) $validated['account_id'] : null;

        $projectionOptions = [
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'horizon_days' => $horizonDays,
            'account_id' => $selectedAccountId,
            'include_recurring' => $request->has('include_recurring') ? $request->boolean('include_recurring') : true,
            'include_installments' => $request->has('include_installments') ? $request->boolean('include_installments') : true,
            'include_planned_incomes' => $request->has('include_planned_incomes') ? $request->boolean('include_planned_incomes') : true,
            'include_short_plans' => $request->has('include_short_plans') ? $request->boolean('include_short_plans') : false,
        ];

        $projection = $this->projectionService->project($space, $projectionOptions);

        $filename = 'Proyeksi_Arus_Kas_'.Carbon::parse($projection['start_date'])->format('Ymd').'_'.Carbon::parse($projection['end_date'])->format('Ymd').'.csv';

        $this->activityLog->log(
            module: 'report',
            action: 'exported',
            description: "Mengekspor CSV Proyeksi Arus Kas & Saldo periode {$projection['start_date']} s/d {$projection['end_date']}",
            space: $space,
            actor: $request->user(),
            request: $request
        );

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($projection) {
            $handle = fopen('php://output', 'w');
            // Menulis UTF-8 BOM agar terbaca baik di Microsoft Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // Header Proyeksi
            fputcsv($handle, ['LAPORAN PROYEKSI SALDO DAN ARUS KAS']);
            fputcsv($handle, ['Ruang Keuangan', $projection['space']->name]);
            fputcsv($handle, ['Periode Horizon', $projection['start_date'].' s/d '.$projection['end_date'].' ('.$projection['horizon_days'].' hari)']);
            fputcsv($handle, ['Rekening Difilter', $projection['selected_account']?->name ?? 'Semua Rekening Kas & Bank']);
            fputcsv($handle, ['Saldo Awal', number_format($projection['starting_balance'], 2, '.', '')]);
            fputcsv($handle, ['Total Estimasi Masuk', number_format($projection['total_inflows'], 2, '.', '')]);
            fputcsv($handle, ['Total Estimasi Keluar', number_format($projection['total_outflows'], 2, '.', '')]);
            fputcsv($handle, ['Proyeksi Saldo Akhir', number_format($projection['ending_balance'], 2, '.', '')]);
            fputcsv($handle, ['Titik Saldo Terendah', number_format($projection['lowest_balance'], 2, '.', '').' ('.$projection['lowest_balance_date'].')']);
            fputcsv($handle, ['Status Risiko Defisit', $projection['is_deficit_risk'] ? 'BERISIKO DEFISIT (Minus Rp '.number_format($projection['max_deficit_amount'], 2, '.', '').')' : 'AMAN']);
            fputcsv($handle, []);

            // Header Tabel
            fputcsv($handle, [
                'No',
                'Tanggal',
                'Hari',
                'Sumber Arus Kas',
                'Deskripsi / Judul',
                'Kategori',
                'Rekening Terkait',
                'Arus Kas Masuk (IDR)',
                'Arus Kas Keluar (IDR)',
                'Proyeksi Saldo (IDR)',
                'Status Saldo',
            ]);

            $no = 1;
            foreach ($projection['timeline_events'] as $evt) {
                fputcsv($handle, [
                    $no++,
                    $evt['date'],
                    $evt['day_name'],
                    $evt['source_label'],
                    $evt['title'],
                    $evt['category_name'],
                    $evt['account_name'],
                    $evt['flow'] === 'in' ? number_format($evt['amount'], 2, '.', '') : '0.00',
                    $evt['flow'] === 'out' ? number_format($evt['amount'], 2, '.', '') : '0.00',
                    number_format($evt['running_balance'], 2, '.', ''),
                    $evt['is_negative'] ? 'DEFISIT / MINUS' : 'POSITIF',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
