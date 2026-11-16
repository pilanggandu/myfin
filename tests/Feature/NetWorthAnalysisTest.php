<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Space;
use App\Models\User;
use App\Services\NetWorthAnalysisService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetWorthAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

    private Account $accountBank;

    private Account $accountCash;

    private Account $accountInvestment;

    private Category $salaryCategory;

    private Category $shoppingCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create(['name' => 'User A', 'email' => 'user.a@example.com']);
        $this->spaceA = Space::create([
            'name' => 'Ruang Personal A',
            'type' => 'personal',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceA->members()->attach($this->userA->id, ['role' => 'owner', 'status' => 'active']);

        $this->userB = User::factory()->create(['name' => 'User B', 'email' => 'user.b@example.com']);
        $this->spaceB = Space::create([
            'name' => 'Ruang Personal B',
            'type' => 'personal',
            'owner_id' => $this->userB->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceB->members()->attach($this->userB->id, ['role' => 'owner', 'status' => 'active']);

        $this->accountBank = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Bank Mandiri',
            'type' => 'bank',
            'opening_balance' => 15000000, // 15 Juta
            'opened_on' => '2026-01-01',
        ]);

        $this->accountCash = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Uang Kas Dompet',
            'type' => 'cash',
            'opening_balance' => 3000000, // 3 Juta
            'opened_on' => '2026-01-01',
        ]);

        $this->accountInvestment = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Bibit Reksadana',
            'type' => 'investment',
            'opening_balance' => 20000000, // 20 Juta
            'opened_on' => '2026-01-01',
        ]);

        $this->salaryCategory = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Gaji Pokok',
            'type' => 'income',
        ]);

        $this->shoppingCategory = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Belanja Barang',
            'type' => 'expense',
        ]);
    }

    public function test_current_balance_sheet_calculates_gross_assets_and_liabilities(): void
    {
        $ledgerAcc = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'BCA Card Ledger',
            'type' => 'credit_card',
            'opening_balance' => 0,
            'opened_on' => '2026-01-01',
        ]);

        $creditAccount = CreditAccount::create([
            'space_id' => $this->spaceA->id,
            'account_id' => $ledgerAcc->id,
            'name' => 'Kartu Kredit BCA',
            'type' => 'credit_card',
            'credit_limit' => 20000000,
            'statement_day' => 15,
            'due_day' => 5,
        ]);

        // Buat installment plan aktif
        $plan = InstallmentPlan::create([
            'space_id' => $this->spaceA->id,
            'credit_account_id' => $creditAccount->id,
            'category_id' => $this->shoppingCategory->id,
            'name' => 'Cicilan iPhone',
            'total_amount' => 12000000,
            'tenor' => 12,
            'interest_rate' => 0,
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        // Buat 12 installment @ 1 Juta, 4 sudah bayar, 8 pending -> 8 Juta sisa pokok
        for ($i = 1; $i <= 12; $i++) {
            Installment::create([
                'space_id' => $this->spaceA->id,
                'installment_plan_id' => $plan->id,
                'installment_number' => $i,
                'due_date' => Carbon::createFromDate(2026, $i, 5)->toDateString(),
                'principal_amount' => 1000000,
                'interest_amount' => 0,
                'fee_amount' => 0,
                'total_amount' => 1000000,
                'status' => $i <= 4 ? 'paid' : 'pending',
                'paid_at' => $i <= 4 ? Carbon::createFromDate(2026, $i, 5)->toDateTimeString() : null,
            ]);
        }

        $service = app(NetWorthAnalysisService::class);
        $sheet = $service->getCurrentBalanceSheet($this->spaceA);

        // Gross Assets = 15M (Bank) + 3M (Kas) + 20M (Investasi) = 38.000.000
        $this->assertEquals(38000000, $sheet['gross_assets']);
        $this->assertEquals(18000000, $sheet['liquid_cash']);
        $this->assertEquals(20000000, $sheet['investment_assets']);

        // Liabilities = 8.000.000 (sisa pokok cicilan)
        $this->assertEquals(8000000, $sheet['total_liabilities']);
        $this->assertEquals(8000000, $sheet['credit_card_debt']);

        // Net Worth = 38M - 8M = 30.000.000
        $this->assertEquals(30000000, $sheet['net_worth']);

        // D/A Ratio = 8M / 38M * 100 = ~21.1%
        $this->assertEquals(21.1, $sheet['debt_to_asset_ratio']);
        $this->assertEquals('healthy', $sheet['solvency_status']['code']);

        // Asset composition is ordered by balance desc: Bibit Reksadana (20M), Bank Mandiri (15M), Uang Kas (3M)
        $this->assertCount(3, $sheet['asset_composition']);
        $this->assertEquals('Bibit Reksadana', $sheet['asset_composition'][0]['name']);
        $this->assertEquals('Bank Mandiri', $sheet['asset_composition'][1]['name']);
        $this->assertEquals('Uang Kas Dompet', $sheet['asset_composition'][2]['name']);
    }

    public function test_historical_net_worth_trend_tracks_monthly_changes_over_time(): void
    {
        $txService = app(TransactionService::class);

        // Bulan Mei 2026: Pemasukan 10 Juta
        $txService->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $this->accountBank->id,
            'category_id' => $this->salaryCategory->id,
            'amount' => 10000000,
            'transaction_date' => '2026-05-10',
            'description' => 'Gaji Mei',
        ]);

        // Bulan Juni 2026: Pemasukan 10 Juta, Pengeluaran 4 Juta
        $txService->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $this->accountBank->id,
            'category_id' => $this->salaryCategory->id,
            'amount' => 10000000,
            'transaction_date' => '2026-06-10',
            'description' => 'Gaji Juni',
        ]);
        $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->accountBank->id,
            'category_id' => $this->shoppingCategory->id,
            'amount' => 4000000,
            'transaction_date' => '2026-06-15',
            'description' => 'Belanja Juni',
        ]);

        $service = app(NetWorthAnalysisService::class);
        $trends = $service->getHistoricalNetWorthTrend($this->spaceA, 6, 'month', 2026, 6);

        $this->assertEquals(6, $trends['horizon_months']);
        $this->assertCount(6, $trends['trend']);

        // Bulan Juni (index terakhir): Aset bertambah akumulasi dari Mei & Juni
        $lastMonth = $trends['trend'][5];
        $this->assertEquals('Jun 2026', $lastMonth['label']);

        // Awal saldo 38M + 10M Mei + 10M Juni - 4M Juni = 54.000.000
        $this->assertEquals(54000000, $lastMonth['assets']);
        $this->assertEquals(54000000, $lastMonth['net_worth']); // tidak ada utang

        // Total growth over 6 months: 54M - 38M = 16.000.000
        $this->assertEquals(16000000, $trends['summary']['total_growth']);
        $this->assertEquals(42.1, $trends['summary']['total_growth_percentage']);
    }

    public function test_debt_history_summary_calculates_payoff_progress(): void
    {
        $ledgerAcc = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Paylater Ledger',
            'type' => 'paylater',
            'opening_balance' => 0,
            'opened_on' => '2026-01-01',
        ]);

        $creditAccount = CreditAccount::create([
            'space_id' => $this->spaceA->id,
            'account_id' => $ledgerAcc->id,
            'name' => 'Shopee Paylater',
            'type' => 'paylater',
            'credit_limit' => 15000000,
            'statement_day' => 20,
            'due_day' => 5,
        ]);

        $plan = InstallmentPlan::create([
            'space_id' => $this->spaceA->id,
            'credit_account_id' => $creditAccount->id,
            'category_id' => $this->shoppingCategory->id,
            'name' => 'Pinjaman Laptop',
            'total_amount' => 10000000,
            'tenor' => 10,
            'interest_rate' => 0,
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        // 6 paid, 4 pending -> 60% progress
        for ($i = 1; $i <= 10; $i++) {
            Installment::create([
                'space_id' => $this->spaceA->id,
                'installment_plan_id' => $plan->id,
                'installment_number' => $i,
                'due_date' => Carbon::createFromDate(2026, $i, 10)->toDateString(),
                'principal_amount' => 1000000,
                'interest_amount' => 0,
                'fee_amount' => 0,
                'total_amount' => 1000000,
                'status' => $i <= 6 ? 'paid' : 'pending',
                'paid_at' => $i <= 6 ? Carbon::createFromDate(2026, $i, 10)->toDateTimeString() : null,
            ]);
        }

        $service = app(NetWorthAnalysisService::class);
        $summary = $service->getDebtHistorySummary($this->spaceA);

        $this->assertEquals(10000000, $summary['total_borrowed']);
        $this->assertEquals(6000000, $summary['total_paid']);
        $this->assertEquals(4000000, $summary['total_remaining']);
        $this->assertEquals(60.0, $summary['overall_payoff_percentage']);
        $this->assertEquals(1, $summary['active_plans_count']);
        $this->assertEquals(0, $summary['completed_plans_count']);
    }

    public function test_user_can_view_net_worth_report_page(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports/net-worth');

        $response->assertOk();
        $response->assertViewHas('balanceSheet');
        $response->assertViewHas('trends');
        $response->assertViewHas('debtHistory');
        $response->assertViewHas('insights');
        $response->assertSee('Analisis Kekayaan Bersih');
        $response->assertSee('Total Aset Bruto');
    }

    public function test_user_can_export_net_worth_csv(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports/net-worth/export-csv?horizon=6');

        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), 'analisis-kekayaan-bersih'));
    }

    public function test_unauthorized_user_cannot_access_other_space_net_worth(): void
    {
        $response = $this->actingAs($this->userB)
            ->withSession(['active_space_id' => $this->spaceB->id])
            ->get("/reports/net-worth?space_id={$this->spaceA->id}");

        $response->assertForbidden();
    }
}
