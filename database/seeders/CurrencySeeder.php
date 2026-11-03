<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'IDR',
                'name' => 'Rupiah Indonesia',
                'symbol' => 'Rp',
                'exchange_rate' => 1.000000,
                'decimal_digits' => 0,
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'exchange_rate' => 16250.000000,
                'decimal_digits' => 2,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'exchange_rate' => 17650.000000,
                'decimal_digits' => 2,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'SGD',
                'name' => 'Singapore Dollar',
                'symbol' => 'S$',
                'exchange_rate' => 12250.000000,
                'decimal_digits' => 2,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'MYR',
                'name' => 'Malaysian Ringgit',
                'symbol' => 'RM',
                'exchange_rate' => 3650.000000,
                'decimal_digits' => 2,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'JPY',
                'name' => 'Japanese Yen',
                'symbol' => '¥',
                'exchange_rate' => 108.500000,
                'decimal_digits' => 0,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound Sterling',
                'symbol' => '£',
                'exchange_rate' => 20850.000000,
                'decimal_digits' => 2,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'AUD',
                'name' => 'Australian Dollar',
                'symbol' => 'A$',
                'exchange_rate' => 10750.000000,
                'decimal_digits' => 2,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'CNY',
                'name' => 'Chinese Yuan Renminbi',
                'symbol' => '¥',
                'exchange_rate' => 2260.000000,
                'decimal_digits' => 2,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'SAR',
                'name' => 'Saudi Riyal',
                'symbol' => 'SR',
                'exchange_rate' => 4330.000000,
                'decimal_digits' => 2,
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($currencies as $curr) {
            Currency::updateOrCreate(
                ['code' => $curr['code']],
                $curr
            );
        }
    }
}
