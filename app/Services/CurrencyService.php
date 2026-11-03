<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class CurrencyService
{
    public function syncRatesFromApi(): array
    {
        $currencies = Currency::where('is_active', true)->get();
        if ($currencies->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Tidak ada mata uang aktif untuk disinkronkan.',
                'updated_count' => 0,
            ];
        }

        $ratesToIdr = $this->fetchRatesFromPublicApi();
        if (empty($ratesToIdr)) {
            return [
                'success' => false,
                'message' => 'Gagal menghubungi Public Currency API. Silakan coba beberapa saat lagi.',
                'updated_count' => 0,
            ];
        }

        $now = Carbon::now();
        $updatedCount = 0;

        DB::transaction(function () use ($currencies, $ratesToIdr, $now, &$updatedCount) {
            foreach ($currencies as $curr) {
                $code = strtoupper($curr->code);

                if ($code === 'IDR') {
                    $curr->update([
                        'exchange_rate' => 1.000000,
                        'last_synced_at' => $now,
                    ]);
                    $updatedCount++;
                    continue;
                }

                if (isset($ratesToIdr[$code]) && $ratesToIdr[$code] > 0) {
                    $curr->update([
                        'exchange_rate' => round($ratesToIdr[$code], 6),
                        'last_synced_at' => $now,
                    ]);
                    $updatedCount++;
                }
            }
        });

        return [
            'success' => true,
            'message' => 'Berhasil memperbarui kurs ' . $updatedCount . ' mata uang dari Public API.',
            'updated_count' => $updatedCount,
            'synced_at' => $now->toDateTimeString(),
        ];
    }

    public function fetchRatesFromPublicApi(): array
    {
        try {
            $response = Http::timeout(6)
                ->acceptJson()
                ->get('https://api.frankfurter.dev/v1/latest', [
                    'base' => 'USD',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $rates = $data['rates'] ?? [];
                $usdToIdr = $rates['IDR'] ?? null;

                if ($usdToIdr && $usdToIdr > 0) {
                    $result = [
                        'USD' => (float) $usdToIdr,
                        'IDR' => 1.0,
                    ];

                    foreach ($rates as $currCode => $rateAgainstUsd) {
                        if ($currCode !== 'IDR' && $rateAgainstUsd > 0) {
                            $result[strtoupper($currCode)] = (float) ($usdToIdr / $rateAgainstUsd);
                        }
                    }

                    return $result;
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            $fallback = Http::timeout(6)
                ->acceptJson()
                ->get('https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json');

            if ($fallback->successful()) {
                $data = $fallback->json();
                $usd = $data['usd'] ?? [];
                $usdToIdr = $usd['idr'] ?? null;

                if ($usdToIdr && $usdToIdr > 0) {
                    $result = [
                        'USD' => (float) $usdToIdr,
                        'IDR' => 1.0,
                    ];

                    foreach ($usd as $cCode => $rateAgainstUsd) {
                        if ($cCode !== 'idr' && $rateAgainstUsd > 0) {
                            $result[strtoupper($cCode)] = (float) ($usdToIdr / $rateAgainstUsd);
                        }
                    }

                    return $result;
                }
            }
        } catch (\Throwable $e) {
        }

        return [];
    }

    public function fetchPublicCurrenciesCatalog(): array
    {
        try {
            $resp = Http::timeout(5)
                ->acceptJson()
                ->get('https://api.frankfurter.dev/v1/currencies');

            if ($resp->successful()) {
                $data = $resp->json();
                if (is_array($data) && ! empty($data)) {
                    $catalog = [];
                    foreach ($data as $code => $name) {
                        $catalog[strtoupper($code)] = $name;
                    }

                    return $catalog;
                }
            }
        } catch (\Throwable $e) {
        }

        return [
            'IDR' => 'Indonesian Rupiah',
            'USD' => 'United States Dollar',
            'EUR' => 'Euro',
            'SGD' => 'Singapore Dollar',
            'MYR' => 'Malaysian Ringgit',
            'JPY' => 'Japanese Yen',
            'GBP' => 'British Pound Sterling',
            'AUD' => 'Australian Dollar',
            'CNY' => 'Chinese Yuan',
            'SAR' => 'Saudi Riyal',
            'CAD' => 'Canadian Dollar',
            'CHF' => 'Swiss Franc',
            'THB' => 'Thai Baht',
            'KRW' => 'South Korean Won',
            'HKD' => 'Hong Kong Dollar',
            'NZD' => 'New Zealand Dollar',
        ];
    }

    public function createCurrency(array $data): Currency
    {
        $code = strtoupper(trim($data['code']));
        $data['code'] = $code;

        if (empty($data['exchange_rate']) || (float) $data['exchange_rate'] <= 0) {
            if ($code === 'IDR') {
                $data['exchange_rate'] = 1.000000;
            } else {
                $rates = $this->fetchRatesFromPublicApi();
                $data['exchange_rate'] = $rates[$code] ?? 1.000000;
                if (isset($rates[$code])) {
                    $data['last_synced_at'] = Carbon::now();
                }
            }
        }

        if (! empty($data['is_default'])) {
            Currency::query()->update(['is_default' => false]);
            $data['is_active'] = true;
        }

        return Currency::create($data);
    }

    public function updateCurrency(Currency $currency, array $data): Currency
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }

        if (! empty($data['is_default'])) {
            Currency::where('id', '!=', $currency->id)->update(['is_default' => false]);
            $data['is_active'] = true;
        }

        if ($currency->is_default && isset($data['is_active']) && ! $data['is_active']) {
            throw ValidationException::withMessages([
                'is_active' => 'Mata uang default utama tidak dapat dinonaktifkan.',
            ]);
        }

        $currency->update($data);

        return $currency;
    }

    public function toggleActive(Currency $currency): bool
    {
        if ($currency->is_default && $currency->is_active) {
            throw ValidationException::withMessages([
                'is_active' => 'Mata uang default utama tidak dapat dinonaktifkan.',
            ]);
        }

        $currency->is_active = ! $currency->is_active;
        $currency->save();

        return $currency->is_active;
    }

    public function setDefault(Currency $currency): void
    {
        DB::transaction(function () use ($currency) {
            Currency::query()->update(['is_default' => false]);
            $currency->is_default = true;
            $currency->is_active = true;
            $currency->save();
        });
    }

    public function deleteCurrency(Currency $currency): void
    {
        if ($currency->is_default) {
            throw ValidationException::withMessages([
                'currency' => 'Mata uang default utama tidak dapat dihapus.',
            ]);
        }

        $currency->delete();
    }

    public function convert(float|int $amount, string $fromCode, string $toCode): array
    {
        $from = Currency::where('code', strtoupper($fromCode))->first();
        $to = Currency::where('code', strtoupper($toCode))->first();

        if (! $from || ! $to) {
            return [
                'success' => false,
                'message' => 'Mata uang tidak ditemukan.',
                'result' => 0.0,
            ];
        }

        $result = $from->convertTo($amount, $to);

        return [
            'success' => true,
            'amount' => (float) $amount,
            'from' => $from->code,
            'to' => $to->code,
            'result' => round($result, $to->decimal_digits),
            'formatted_result' => $to->formatAmount($result),
            'exchange_rate' => $from->exchange_rate > 0 ? (float) ($to->exchange_rate / $from->exchange_rate) : 0,
        ];
    }
}
