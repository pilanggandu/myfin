<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Credit\PayInstallmentRequest;
use App\Http\Requests\Credit\PayoffPlanRequest;
use App\Http\Requests\Credit\StoreCreditDebitNoteRequest;
use App\Http\Requests\Credit\StoreCreditPurchaseRequest;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Services\CreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class InstallmentController extends Controller
{
    public function __construct(
        protected CreditService $creditService
    ) {}

    public function create(): View
    {
        $space = app('currentSpace');
        Gate::authorize('create', InstallmentPlan::class);

        $creditAccounts = $space->creditAccounts()->active()->orderBy('name')->get();
        $categories = $space->categories()->expense()->active()->orderBy('name')->get();
        $members = $space->customMembers()->active()->orderBy('name')->get();

        return view('credit.create-purchase', compact('creditAccounts', 'categories', 'members'));
    }

    public function store(StoreCreditPurchaseRequest $request): RedirectResponse
    {
        $space = app('currentSpace');
        Gate::authorize('create', InstallmentPlan::class);

        $plan = $this->creditService->recordCreditPurchase($space, auth()->user(), $request->validated());

        return redirect()->route('credit.plans.show', $plan)
            ->with('status', 'Pembelian dengan cicilan berhasil dicatat.');
    }

    public function show(InstallmentPlan $plan): View
    {
        Gate::authorize('view', $plan);
        $space = app('currentSpace');

        $relations = [
            'creditAccount',
            'category',
            'member',
            'purchaseTransaction',
            'installments.payments.sourceAccount',
        ];

        if (Schema::hasTable('credit_debit_notes')) {
            $relations[] = 'creditDebitNotes';
        }

        $plan->load($relations);

        $cashAccounts = $space->accounts()->whereNull('archived_at')->whereIn('type', ['bank', 'cash', 'e-wallet'])->orderBy('name')->get();

        return view('credit.show-plan', compact('plan', 'cashAccounts'));
    }

    public function pay(PayInstallmentRequest $request, Installment $installment): RedirectResponse
    {
        Gate::authorize('view', $installment->installmentPlan);
        $space = app('currentSpace');

        $this->creditService->payInstallment($space, auth()->user(), $installment, $request->validated());

        return redirect()->route('credit.plans.show', $installment->installmentPlan)
            ->with('status', 'Pembayaran cicilan ke-'.$installment->installment_number.' berhasil diposting.');
    }

    public function markPaidWithoutCash(Request $request, Installment $installment): RedirectResponse
    {
        Gate::authorize('view', $installment->installmentPlan);
        $space = app('currentSpace');

        $this->creditService->markInstallmentPaidWithoutCash($space, auth()->user(), $installment, $request->input('notes'));

        return redirect()->route('credit.plans.show', $installment->installmentPlan)
            ->with('status', 'Cicilan ke-'.$installment->installment_number.' berhasil ditandai lunas tanpa memotong saldo kas/bank.');
    }

    public function markOverduePaidWithoutCash(Request $request, InstallmentPlan $plan): RedirectResponse
    {
        Gate::authorize('view', $plan);
        $space = app('currentSpace');

        $count = $this->creditService->markOverdueInstallmentsPaidWithoutCash($space, auth()->user(), $plan, $request->input('notes'));

        return redirect()->route('credit.plans.show', $plan)
            ->with('status', $count.' cicilan lampau berhasil ditandai lunas tanpa memotong saldo kas/bank.');
    }

    public function resetToPending(Request $request, Installment $installment): RedirectResponse
    {
        Gate::authorize('view', $installment->installmentPlan);
        $space = app('currentSpace');

        $this->creditService->resetInstallmentToPending($space, auth()->user(), $installment);

        return redirect()->route('credit.plans.show', $installment->installmentPlan)
            ->with('status', 'Status cicilan ke-'.$installment->installment_number.' berhasil dikembalikan menjadi menunggu pembayaran.');
    }

    public function payoff(PayoffPlanRequest $request, InstallmentPlan $plan): RedirectResponse
    {
        Gate::authorize('view', $plan);
        $space = app('currentSpace');

        $result = $this->creditService->payoffPlan($space, auth()->user(), $plan, $request->validated());

        return redirect()->route('credit.plans.show', $plan)
            ->with('status', 'Seluruh sisa '.$result['settled_count'].' cicilan sebesar Rp '.number_format($result['total_paid'], 0, ',', '.').' berhasil dilunasi sekaligus (Lunas Selesai).');
    }

    public function void(Request $request, InstallmentPlan $plan): RedirectResponse
    {
        Gate::authorize('delete', $plan);
        $space = app('currentSpace');

        $this->creditService->voidCreditPurchase($space, auth()->user(), $plan);

        return redirect()->route('credit.index', ['tab' => 'plans'])
            ->with('status', 'Rencana cicilan dan transaksi pembelian berhasil dibatalkan (void).');
    }

    public function storeNote(StoreCreditDebitNoteRequest $request): RedirectResponse
    {
        $space = app('currentSpace');
        $note = $this->creditService->recordCreditDebitNote($space, auth()->user(), $request->validated());

        $redirect = $note->installmentPlan
            ? redirect()->route('credit.plans.show', $note->installmentPlan)
            : redirect()->route('credit.index', ['tab' => 'accounts']);

        return $redirect->with('status', $note->typeLabel().' sebesar Rp '.number_format($note->amount, 0, ',', '.').' berhasil diposting.');
    }
}
