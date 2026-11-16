<?php

namespace App\Http\Controllers\Simulations;

use App\Http\Controllers\Controller;
use App\Models\FinancialSimulation;
use App\Models\Space;
use App\Services\ActivityLogService;
use App\Services\FinancialSimulationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FinancialSimulationController extends Controller
{
    public function __construct(
        protected FinancialSimulationService $simulationService,
        protected ActivityLogService $activityLog
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', FinancialSimulation::class);

        /** @var Space $space */
        $space = app('currentSpace');
        if ($request->filled('space_id')) {
            $requestedSpace = Space::findOrFail($request->input('space_id'));
            Gate::authorize('view', $requestedSpace);
            $space = $requestedSpace;
        }

        $baseline = $this->simulationService->getBaseline($space);

        // Riwayat simulasi tersimpan
        $simulations = $space->financialSimulations()
            ->with('user')
            ->orderByDesc('is_starred')
            ->orderByDesc('created_at')
            ->get();

        // Default skenario awal saat membuka halaman
        $defaultScenarioType = $request->input('scenario_type', 'asset_purchase');
        if (! in_array($defaultScenarioType, ['asset_purchase', 'loan_installment', 'saving_target'], true)) {
            $defaultScenarioType = 'asset_purchase';
        }

        // Jalankan kalkulasi awal untuk preview default
        $initialParams = match ($defaultScenarioType) {
            'asset_purchase' => [
                'purchase_price' => 35000000,
                'payment_method' => 'credit',
                'down_payment' => 7000000,
                'tenor_months' => 36,
                'interest_rate_p_a' => 8.5,
                'monthly_installment' => 0,
                'running_cost_monthly' => 600000,
            ],
            'loan_installment' => [
                'loan_amount' => 20000000,
                'tenor_months' => 24,
                'interest_rate_p_a' => 9.0,
                'monthly_installment' => 0,
                'admin_fee' => 500000,
                'admin_fee_deducted' => true,
            ],
            'saving_target' => [
                'target_amount' => 50000000,
                'target_months' => 24,
                'monthly_deposit' => 0,
                'annual_return_rate' => 5.0,
            ],
        };

        $initialResult = $this->simulationService->simulate($baseline, $defaultScenarioType, $initialParams);

        return view('simulations.index', compact(
            'space',
            'baseline',
            'simulations',
            'defaultScenarioType',
            'initialParams',
            'initialResult'
        ));
    }

    public function simulate(Request $request): JsonResponse
    {
        Gate::authorize('create', FinancialSimulation::class);

        /** @var Space $space */
        $space = app('currentSpace');
        if ($request->filled('space_id')) {
            $requestedSpace = Space::findOrFail($request->input('space_id'));
            Gate::authorize('view', $requestedSpace);
            $space = $requestedSpace;
        }

        $validated = $request->validate([
            'scenario_type' => ['required', 'in:asset_purchase,loan_installment,saving_target'],
            'baseline_income' => ['nullable', 'numeric', 'min:0'],
            'baseline_expense' => ['nullable', 'numeric', 'min:0'],
            'baseline_debt' => ['nullable', 'numeric', 'min:0'],
            'baseline_liquid_cash' => ['nullable', 'numeric'],
            'parameters' => ['required', 'array'],
        ]);

        $defaultBaseline = $this->simulationService->getBaseline($space);

        $customBaseline = [
            'monthly_income' => isset($validated['baseline_income']) && $validated['baseline_income'] !== ''
                ? (float) $validated['baseline_income']
                : $defaultBaseline['monthly_income'],
            'monthly_expense' => isset($validated['baseline_expense']) && $validated['baseline_expense'] !== ''
                ? (float) $validated['baseline_expense']
                : $defaultBaseline['monthly_expense'],
            'monthly_debt' => isset($validated['baseline_debt']) && $validated['baseline_debt'] !== ''
                ? (float) $validated['baseline_debt']
                : $defaultBaseline['monthly_debt'],
            'liquid_cash' => isset($validated['baseline_liquid_cash']) && $validated['baseline_liquid_cash'] !== ''
                ? (float) $validated['baseline_liquid_cash']
                : $defaultBaseline['liquid_cash'],
        ];

        $simulationResult = $this->simulationService->simulate(
            $customBaseline,
            $validated['scenario_type'],
            $validated['parameters']
        );

        return response()->json([
            'success' => true,
            'baseline' => $customBaseline,
            'result' => $simulationResult,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        Gate::authorize('create', FinancialSimulation::class);

        /** @var Space $space */
        $space = app('currentSpace');
        if ($request->filled('space_id')) {
            $requestedSpace = Space::findOrFail($request->input('space_id'));
            Gate::authorize('view', $requestedSpace);
            $space = $requestedSpace;
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'scenario_type' => ['required', 'in:asset_purchase,loan_installment,saving_target'],
            'description' => ['nullable', 'string', 'max:1000'],
            'baseline_income' => ['nullable', 'numeric', 'min:0'],
            'baseline_expense' => ['nullable', 'numeric', 'min:0'],
            'baseline_debt' => ['nullable', 'numeric', 'min:0'],
            'baseline_liquid_cash' => ['nullable', 'numeric'],
            'parameters' => ['required', 'array'],
        ]);

        $defaultBaseline = $this->simulationService->getBaseline($space);

        $baseline = [
            'monthly_income' => isset($validated['baseline_income']) && $validated['baseline_income'] !== ''
                ? (float) $validated['baseline_income']
                : $defaultBaseline['monthly_income'],
            'monthly_expense' => isset($validated['baseline_expense']) && $validated['baseline_expense'] !== ''
                ? (float) $validated['baseline_expense']
                : $defaultBaseline['monthly_expense'],
            'monthly_debt' => isset($validated['baseline_debt']) && $validated['baseline_debt'] !== ''
                ? (float) $validated['baseline_debt']
                : $defaultBaseline['monthly_debt'],
            'liquid_cash' => isset($validated['baseline_liquid_cash']) && $validated['baseline_liquid_cash'] !== ''
                ? (float) $validated['baseline_liquid_cash']
                : $defaultBaseline['liquid_cash'],
        ];

        $results = $this->simulationService->simulate(
            $baseline,
            $validated['scenario_type'],
            $validated['parameters']
        );

        $simulation = $space->financialSimulations()->create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'scenario_type' => $validated['scenario_type'],
            'description' => $validated['description'] ?? null,
            'baseline_income' => $baseline['monthly_income'],
            'baseline_expense' => $baseline['monthly_expense'],
            'baseline_debt' => $baseline['monthly_debt'],
            'baseline_liquid_cash' => $baseline['liquid_cash'],
            'parameters' => $validated['parameters'],
            'results' => $results,
        ]);

        $this->activityLog->log(
            module: 'simulation',
            action: 'created',
            description: "Menyimpan simulasi keputusan '{$simulation->title}' ({$simulation->scenarioTypeLabel()})",
            space: $space,
            actor: $request->user(),
            subject: $simulation,
            subjectLabel: $simulation->title,
            properties: [
                'scenario_type' => $simulation->scenario_type,
                'health_status' => $results['health_status'],
                'health_score' => $results['health_score'],
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'simulation' => $simulation,
                'message' => "Simulasi '{$simulation->title}' berhasil disimpan.",
            ]);
        }

        return redirect()->route('simulations.index')
            ->with('success', "Simulasi keputusan '{$simulation->title}' berhasil disimpan.");
    }

    public function show(FinancialSimulation $simulation): JsonResponse
    {
        Gate::authorize('view', $simulation);

        return response()->json([
            'success' => true,
            'simulation' => $simulation,
        ]);
    }

    public function toggleStarred(FinancialSimulation $simulation): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $simulation);

        $simulation->update(['is_starred' => ! $simulation->is_starred]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_starred' => $simulation->is_starred,
            ]);
        }

        return redirect()->back();
    }

    public function destroy(FinancialSimulation $simulation): RedirectResponse
    {
        Gate::authorize('delete', $simulation);

        $title = $simulation->title;
        $space = $simulation->space;

        $simulation->delete();

        $this->activityLog->log(
            module: 'simulation',
            action: 'deleted',
            description: "Menghapus simulasi keputusan '{$title}'",
            space: $space,
            subjectLabel: $title
        );

        return redirect()->route('simulations.index')
            ->with('success', "Simulasi '{$title}' berhasil dihapus.");
    }
}
