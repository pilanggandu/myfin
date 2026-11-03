<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Space;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $budi = User::firstOrCreate(
            ['email' => 'budi@arus.local'],
            [
                'name' => 'Budi Pratama',
                'password' => Hash::make('Password123!'),
            ]
        );

        $budiPersonal = Space::firstOrCreate(
            [
                'owner_id' => $budi->id,
                'type' => 'personal',
            ],
            [
                'name' => 'Keuangan Pribadi Budi',
                'timezone' => 'Asia/Jakarta',
            ]
        );

        $budiPersonal->members()->syncWithoutDetaching([
            $budi->id => ['role' => 'owner', 'status' => 'active'],
        ]);
        $budiPersonal->createDefaultCategories();

        $siti = User::firstOrCreate(
            ['email' => 'siti@arus.local'],
            [
                'name' => 'Siti Rahma',
                'password' => Hash::make('Password123!'),
            ]
        );

        $sitiPersonal = Space::firstOrCreate(
            [
                'owner_id' => $siti->id,
                'type' => 'personal',
            ],
            [
                'name' => 'Keuangan Pribadi Siti',
                'timezone' => 'Asia/Jakarta',
            ]
        );

        $sitiPersonal->members()->syncWithoutDetaching([
            $siti->id => ['role' => 'owner', 'status' => 'active'],
        ]);
        $sitiPersonal->createDefaultCategories();

        $sharedSpace = Space::firstOrCreate(
            [
                'owner_id' => $budi->id,
                'type' => 'shared',
                'name' => 'Keuangan Keluarga',
            ],
            [
                'timezone' => 'Asia/Jakarta',
            ]
        );

        $sharedSpace->members()->syncWithoutDetaching([
            $budi->id => ['role' => 'owner', 'status' => 'active'],
            $siti->id => ['role' => 'member', 'status' => 'active'],
        ]);
        $sharedSpace->createDefaultCategories();

        $bca = Account::firstOrCreate(
            [
                'space_id' => $budiPersonal->id,
                'name' => 'Bank BCA',
            ],
            [
                'type' => 'bank',
                'opening_balance' => 10000000,
                'opened_on' => Carbon::now()->subMonths(2)->startOfMonth(),
                'is_default' => true,
            ]
        );
        $bca->makeDefault();

        $gopay = Account::firstOrCreate(
            [
                'space_id' => $budiPersonal->id,
                'name' => 'GoPay',
            ],
            [
                'type' => 'e-wallet',
                'opening_balance' => 500000,
                'opened_on' => Carbon::now()->subMonths(2)->startOfMonth(),
            ]
        );

        $cash = Account::firstOrCreate(
            [
                'space_id' => $budiPersonal->id,
                'name' => 'Dompet Tunai',
            ],
            [
                'type' => 'cash',
                'opening_balance' => 300000,
                'opened_on' => Carbon::now()->subMonths(2)->startOfMonth(),
            ]
        );

        if ($budiPersonal->financialTransactions()->count() === 0) {
            $service = app(TransactionService::class);
            $incomeCat = Category::where('space_id', $budiPersonal->id)->where('type', 'income')->first();
            $expenseCat = Category::where('space_id', $budiPersonal->id)->where('type', 'expense')->first();

            if ($incomeCat && $expenseCat) {
                $service->recordIncome($budiPersonal, $budi, [
                    'account_id' => $bca->id,
                    'category_id' => $incomeCat->id,
                    'amount' => 15000000,
                    'transaction_date' => Carbon::now()->startOfMonth()->toDateString(),
                    'description' => 'Gaji Bulanan',
                ]);

                $service->recordTransfer($budiPersonal, $budi, [
                    'source_account_id' => $bca->id,
                    'destination_account_id' => $gopay->id,
                    'amount' => 500000,
                    'transaction_date' => Carbon::now()->subDays(5)->toDateString(),
                    'description' => 'Top Up GoPay dari BCA',
                ]);

                $service->recordExpense($budiPersonal, $budi, [
                    'account_id' => $bca->id,
                    'category_id' => $expenseCat->id,
                    'amount' => 1250000,
                    'transaction_date' => Carbon::now()->subDays(3)->toDateString(),
                    'description' => 'Belanja Kebutuhan Pokok',
                ]);

                $service->recordExpense($budiPersonal, $budi, [
                    'account_id' => $gopay->id,
                    'category_id' => $expenseCat->id,
                    'amount' => 45000,
                    'transaction_date' => Carbon::now()->subDay()->toDateString(),
                    'description' => 'Makan Siang & Kopi',
                ]);
            }
        }

        $now = Carbon::now();
        $expenseCategories = Category::where('space_id', $budiPersonal->id)
            ->where('type', 'expense')
            ->take(3)
            ->get();

        foreach ($expenseCategories as $index => $cat) {
            $limit = match ($index) {
                0 => 2500000,
                1 => 1000000,
                2 => 1500000,
                default => 500000,
            };

            Budget::firstOrCreate(
                [
                    'space_id' => $budiPersonal->id,
                    'category_id' => $cat->id,
                    'year' => $now->year,
                    'month' => $now->month,
                ],
                [
                    'limit_amount' => $limit,
                ]
            );
        }
    }
}
