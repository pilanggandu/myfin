<?php

namespace App\Http\Controllers\Currencies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Currency\StoreCurrencyRequest;
use App\Http\Requests\Currency\UpdateCurrencyRequest;
use App\Models\Currency;
use App\Services\ActivityLogService;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected ActivityLogService $activityLog
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
        $space = app()->bound('currentSpace') ? app('currentSpace') : null;

        $this->activityLog->log(
            module: 'currency',
            action: 'created',
            description: "Menambahkan mata uang {$currency->code} ({$currency->name})",
            space: $space,
            actor: $request->user(),
            subject: $currency,
            subjectLabel: $currency->code,
            properties: [
                'code' => $currency->code,
                'name' => $currency->name,
                'rate_to_idr' => $currency->rate_to_idr ?? null,
            ],
            request: $request,
        );

        return redirect()->route('currencies.index')
            ->with('success', "Mata uang {$currency->code} ({$currency->name}) berhasil ditambahkan ke master data.");
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $before = [
            'name' => $currency->name,
            'rate_to_idr' => $currency->rate_to_idr ?? null,
        ];
        $this->currencyService->updateCurrency($currency, $request->validated());
        $currency->refresh();
        $space = app()->bound('currentSpace') ? app('currentSpace') : null;

        $this->activityLog->log(
            module: 'currency',
            action: 'updated',
            description: "Memperbarui data mata uang {$currency->code}",
            space: $space,
            actor: $request->user(),
            subject: $currency,
            subjectLabel: $currency->code,
            properties: $this->activityLog->buildDiff($before, [
                'name' => $currency->name,
                'rate_to_idr' => $currency->rate_to_idr ?? null,
            ], [
                'name' => 'Nama',
                'rate_to_idr' => 'Kurs ke IDR',
            ]),
            request: $request,
        );

        return redirect()->route('currencies.index')
            ->with('success', "Data mata uang {$currency->code} berhasil diperbarui.");
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        $code = $currency->code;
        $this->currencyService->deleteCurrency($currency);
        $space = app()->bound('currentSpace') ? app('currentSpace') : null;

        $this->activityLog->log(
            module: 'currency',
            action: 'deleted',
            description: "Menghapus mata uang {$code} dari master data",
            space: $space,
            subjectLabel: $code,
        );

        return redirect()->route('currencies.index')
            ->with('success', "Mata uang {$code} berhasil dihapus dari master data.");
    }

    public function toggleActive(Currency $currency): RedirectResponse
    {
        $isActive = $this->currencyService->toggleActive($currency);
        $statusText = $isActive ? 'diaktifkan' : 'dinonaktifkan';
        $space = app()->bound('currentSpace') ? app('currentSpace') : null;

        $this->activityLog->log(
            module: 'currency',
            action: 'toggled',
            description: "Mengubah status mata uang {$currency->code} menjadi ".($isActive ? 'aktif' : 'nonaktif'),
            space: $space,
            subject: $currency,
            subjectLabel: $currency->code,
            properties: ['is_active' => $isActive],
        );

        return redirect()->route('currencies.index')
            ->with('success', "Mata uang {$currency->code} berhasil {$statusText}.");
    }

    public function setDefault(Currency $currency): RedirectResponse
    {
        $this->currencyService->setDefault($currency);
        $space = app()->bound('currentSpace') ? app('currentSpace') : null;

        $this->activityLog->log(
            module: 'currency',
            action: 'set_default',
            description: "Menetapkan {$currency->code} sebagai mata uang default utama",
            space: $space,
            subject: $currency,
            subjectLabel: $currency->code,
        );

        return redirect()->route('currencies.index')
            ->with('success', "Mata uang {$currency->code} berhasil ditetapkan sebagai mata uang default utama.");
    }

    public function syncRates(Request $request): RedirectResponse
    {
        $result = $this->currencyService->syncRatesFromApi();

        if ($result['success']) {
            $space = app()->bound('currentSpace') ? app('currentSpace') : null;

            $this->activityLog->log(
                module: 'currency',
                action: 'synced',
                description: 'Menyinkronkan kurs mata uang terbaru dari API eksternal',
                space: $space,
                actor: $request->user(),
                request: $request,
            );

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
