<?php

namespace App\Http\Controllers\Currencies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Currency\StoreCurrencyRequest;
use App\Http\Requests\Currency\UpdateCurrencyRequest;
use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    public function index(Request $request): View
    {
        $query = Currency::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $currencies = $query->orderByDesc('is_default')
            ->orderByDesc('is_active')
            ->orderBy('code')
            ->get();

        $allCurrencies = Currency::orderBy('code')->get();
        $totalCurrencies = $allCurrencies->count();
        $activeCurrenciesCount = $allCurrencies->where('is_active', true)->count();
        $defaultCurrency = $allCurrencies->firstWhere('is_default', true) ?? $allCurrencies->firstWhere('code', 'IDR');
        $lastSyncedAt = $allCurrencies->whereNotNull('last_synced_at')->max('last_synced_at');

        $publicCatalog = $this->currencyService->fetchPublicCurrenciesCatalog();

        return view('currencies.index', compact(
            'currencies',
            'allCurrencies',
            'totalCurrencies',
            'activeCurrenciesCount',
            'defaultCurrency',
            'lastSyncedAt',
            'publicCatalog'
        ));
    }

    public function store(StoreCurrencyRequest $request): RedirectResponse
    {
        $currency = $this->currencyService->createCurrency($request->validated());

        return redirect()->route('currencies.index')
            ->with('success', "Mata uang {$currency->code} ({$currency->name}) berhasil ditambahkan ke master data.");
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $this->currencyService->updateCurrency($currency, $request->validated());

        return redirect()->route('currencies.index')
            ->with('success', "Data mata uang {$currency->code} berhasil diperbarui.");
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        $code = $currency->code;
        $this->currencyService->deleteCurrency($currency);

        return redirect()->route('currencies.index')
            ->with('success', "Mata uang {$code} berhasil dihapus dari master data.");
    }

    public function toggleActive(Currency $currency): RedirectResponse
    {
        $isActive = $this->currencyService->toggleActive($currency);
        $statusText = $isActive ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('currencies.index')
            ->with('success', "Mata uang {$currency->code} berhasil {$statusText}.");
    }

    public function setDefault(Currency $currency): RedirectResponse
    {
        $this->currencyService->setDefault($currency);

        return redirect()->route('currencies.index')
            ->with('success', "Mata uang {$currency->code} berhasil ditetapkan sebagai mata uang default utama.");
    }

    public function syncRates(Request $request): RedirectResponse
    {
        $result = $this->currencyService->syncRatesFromApi();

        if ($result['success']) {
            return redirect()->route('currencies.index')
                ->with('success', $result['message']);
        }

        return redirect()->route('currencies.index')
            ->with('warning', $result['message']);
    }

    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'from' => ['required', 'string', 'size:3'],
            'to' => ['required', 'string', 'size:3'],
        ]);

        $conversion = $this->currencyService->convert(
            (float) $request->input('amount'),
            $request->input('from'),
            $request->input('to')
        );

        return response()->json($conversion);
    }
}
