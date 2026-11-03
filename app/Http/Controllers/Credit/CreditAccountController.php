<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Credit\StoreCreditAccountRequest;
use App\Http\Requests\Credit\UpdateCreditAccountRequest;
use App\Models\CreditAccount;
use App\Models\InstallmentPlan;
use App\Services\CreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CreditAccountController extends Controller
{
    public function __construct(
        protected CreditService $creditService
    ) {}

    public function index(Request $request): View
    {
        $space = app('currentSpace');
        Gate::authorize('viewAny', CreditAccount::class);

        $tab = $request->query('tab', 'accounts');

        $creditAccounts = $space->creditAccounts()
            ->with(['installmentPlans.installments'])
            ->orderBy('archived_at', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $activePlans = $space->installmentPlans()
            ->with(['creditAccount', 'category', 'installments'])
            ->where('status', 'active')
            ->orderBy('start_date', 'desc')
            ->get();

        $completedPlans = $space->installmentPlans()
            ->with(['creditAccount', 'category', 'installments'])
            ->where('status', '!=', 'active')
            ->orderBy('start_date', 'desc')
            ->get();

        $totalCreditLimit = (int) $creditAccounts->whereNull('archived_at')->sum('credit_limit');
        $totalLiability = (int) $space->creditLiabilities();
        $totalAvailableLimit = max(0, $totalCreditLimit - $totalLiability);

        $categories = $space->categories()->expense()->active()->orderBy('name')->get();
        $members = $space->customMembers()->active()->orderBy('name')->get();
        $cashAccounts = $space->accounts()->whereNull('archived_at')->whereIn('type', ['bank', 'cash', 'e-wallet'])->orderBy('name')->get();

        return view('credit.index', compact(
            'creditAccounts',
            'activePlans',
            'completedPlans',
            'totalCreditLimit',
            'totalLiability',
            'totalAvailableLimit',
            'categories',
            'members',
            'cashAccounts',
            'tab'
        ));
    }

    public function store(StoreCreditAccountRequest $request): RedirectResponse
    {
        $space = app('currentSpace');
        Gate::authorize('create', CreditAccount::class);

        $this->creditService->createCreditAccount($space, $request->validated());

        return redirect()->route('credit.index', ['tab' => 'accounts'])
            ->with('status', 'Akun kredit atau kartu berhasil ditambahkan.');
    }

    public function update(UpdateCreditAccountRequest $request, CreditAccount $creditAccount): RedirectResponse
    {
        Gate::authorize('update', $creditAccount);

        $this->creditService->updateCreditAccount($creditAccount, $request->validated());

        return redirect()->route('credit.index', ['tab' => 'accounts'])
            ->with('status', 'Data akun kredit berhasil diperbarui.');
    }

    public function destroy(CreditAccount $creditAccount): RedirectResponse
    {
        Gate::authorize('delete', $creditAccount);

        $creditAccount->archive();

        return redirect()->route('credit.index', ['tab' => 'accounts'])
            ->with('status', 'Akun kredit berhasil diarsipkan.');
    }

    public function unarchive(CreditAccount $creditAccount): RedirectResponse
    {
        Gate::authorize('update', $creditAccount);

        $creditAccount->unarchive();

        return redirect()->route('credit.index', ['tab' => 'accounts'])
            ->with('status', 'Akun kredit berhasil diaktifkan kembali.');
    }
}
