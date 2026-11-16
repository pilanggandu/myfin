<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Services\DebtPayoffPlannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DebtPayoffController extends Controller
{
    public function __construct(
        protected DebtPayoffPlannerService $payoffService
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

        $extraMonthly = (float) $request->input('extra_monthly', 0);
        $oneTimePayment = (float) $request->input('one_time_payment', 0);

        $debts = $this->payoffService->collectDebts($space);
        $planData = $this->payoffService->calculateStrategies(
            $debts,
            $extraMonthly,
            $oneTimePayment
        );

        $creditAccountsCount = $space->creditAccounts()->whereNull('archived_at')->count();
        $activePlansCount = $space->installmentPlans()->active()->count();
        $completedPlansCount = $space->installmentPlans()->where('status', '!=', 'active')->count();

        return view('credit.payoff-planner', compact(
            'planData',
            'extraMonthly',
            'oneTimePayment',
            'creditAccountsCount',
            'activePlansCount',
            'completedPlansCount'
        ));
    }

    public function simulate(Request $request): JsonResponse
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

        $validated = $request->validate([
            'extra_monthly' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'one_time_payment' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
        ]);

        $extraMonthly = (float) ($validated['extra_monthly'] ?? 0);
        $oneTimePayment = (float) ($validated['one_time_payment'] ?? 0);

        $debts = $this->payoffService->collectDebts($space);
        $result = $this->payoffService->calculateStrategies(
            $debts,
            $extraMonthly,
            $oneTimePayment
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
