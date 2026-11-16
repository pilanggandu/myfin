<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialSimulation;
use App\Models\Space;
use App\Models\User;
use App\Services\FinancialSimulationService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialSimulationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

    private Account $accountBank;

    private Account $accountCash;

    private Category $salaryCategory;

    private Category $foodCategory;

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
            'name' => 'BCA Utama',
            'type' => 'bank',
            'opening_balance' => 20000000, // 20 Juta
            'opened_on' => '2026-01-01',
        ]);

        $this->accountCash = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Dompet Tunai',
            'type' => 'cash',
            'opening_balance' => 2500000, // 2.5 Juta
            'opened_on' => '2026-01-01',
        ]);

        $this->salaryCategory = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Gaji Pokok',
            'type' => 'income',
        ]);

        $this->foodCategory = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Konsumsi Rutin',
            'type' => 'expense',
        ]);
    }

    public function test_baseline_calculation_aggregates_liquid_cash_and_transactions(): void
    {
        $txService = app(TransactionService::class);
        $recentDate1 = now()->subDays(20)->toDateString();
        $recentDate2 = now()->subDays(10)->toDateString();

        // Record income in last 90 days: Rp 30,000,000 across 3 months -> Avg Rp 10,000,000/bln
        $txService->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $this->accountBank->id,
            'category_id' => $this->salaryCategory->id,
            'amount' => 30000000,
            'transaction_date' => $recentDate1,
            'description' => 'Gaji Total 3 Bulan',
        ]);

        // Record expense in last 90 days: Rp 12,000,000 across 3 months -> Avg Rp 4,000,000/bln
        $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->accountBank->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 12000000,
            'transaction_date' => $recentDate2,
            'description' => 'Biaya Hidup 3 Bulan',
        ]);

        $simService = app(FinancialSimulationService::class);
        $baseline = $simService->getBaseline($this->spaceA);

        $this->assertEquals(10000000, $baseline['monthly_income']);
        $this->assertEquals(4000000, $baseline['monthly_expense']);
        $this->assertEquals(0, $baseline['monthly_debt']);
        // Liquid cash = 20M + 2.5M + 30M income - 12M expense = 40.5M
        $this->assertEquals(40500000, $baseline['liquid_cash']);
        $this->assertEquals(6000000, $baseline['free_cashflow']); // 10M - 4M
        $this->assertEquals(0.0, $baseline['dsr']);
        $this->assertEquals(10.1, $baseline['runway_months']); // 40.5M / 4M
    }

    public function test_simulate_asset_purchase_credit_evaluates_correctly(): void
    {
        $simService = app(FinancialSimulationService::class);

        $baseline = [
            'monthly_income' => 12000000,
            'monthly_expense' => 5000000,
            'monthly_debt' => 1000000,
            'liquid_cash' => 35000000,
        ];

        // Skenario beli motor Rp 35.000.000, DP Rp 7.000.000, Tenor 24 bln, Bunga 8% flat p.a, Biaya operasional 500rb
        // Pokok pinjaman = 28.000.000
        // Total bunga 2 thn = 28.000.000 * 8% * 2 = 4.480.000
        // Total angsuran = (28.000.000 + 4.480.000) / 24 = 1.353.333,33/bln
        $result = $simService->simulate($baseline, 'asset_purchase', [
            'purchase_price' => 35000000,
            'payment_method' => 'credit',
            'down_payment' => 7000000,
            'tenor_months' => 24,
            'interest_rate_p_a' => 8.0,
            'running_cost_monthly' => 500000,
        ]);

        $this->assertEquals('asset_purchase', $result['scenario_type']);
        $this->assertEquals(7000000, $result['summary']['upfront_outflow']);
        $this->assertEquals(1353333.33, $result['summary']['monthly_debt_addon']);
        $this->assertEquals(500000, $result['summary']['monthly_expense_addon']);

        // Sisa kas sesudah DP: 35M - 7M = 28M
        $this->assertEquals(28000000, $result['comparison']['after']['liquid_cash']);

        // DSR sesudah: (1M + 1.353.333,33) / 12M = ~19.6% (aman <= 30%)
        $this->assertEquals(19.6, $result['comparison']['after']['dsr']);

        // Free cash flow sesudah: 12M - (5M + 500rb) - (1M + 1.353.333,33) = ~4.146.666,67
        $this->assertEquals(4146666.67, $result['comparison']['after']['free_cashflow']);
        $this->assertEquals('safe', $result['health_status']);
    }

    public function test_simulate_asset_purchase_cash_exhausting_runway_warns_user(): void
    {
        $simService = app(FinancialSimulationService::class);

        $baseline = [
            'monthly_income' => 10000000,
            'monthly_expense' => 6000000,
            'monthly_debt' => 0,
            'liquid_cash' => 30000000,
        ];

        // Beli mobil tunai Rp 28.000.000 -> sisa kas tinggal Rp 2.000.000 (hanya 0.3 bulan pengeluaran!)
        $result = $simService->simulate($baseline, 'asset_purchase', [
            'purchase_price' => 28000000,
            'payment_method' => 'cash',
            'running_cost_monthly' => 700000,
        ]);

        $this->assertEquals(28000000, $result['summary']['upfront_outflow']);
        $this->assertEquals(2000000, $result['comparison']['after']['liquid_cash']);
        $this->assertEquals(0.3, $result['comparison']['after']['runway_months']);
        // Runway sangat rendah (< 1 bulan) memicu status danger/warning
        $this->assertContains($result['health_status'], ['danger', 'warning']);
    }

    public function test_simulate_loan_installment_high_dsr_triggers_danger(): void
    {
        $simService = app(FinancialSimulationService::class);

        // Pemasukan 8.000.000, utang saat ini 2.000.000 (DSR 25%)
        $baseline = [
            'monthly_income' => 8000000,
            'monthly_expense' => 4500000,
            'monthly_debt' => 2000000,
            'liquid_cash' => 10000000,
        ];

        // Ambil pinjaman cicilan baru Rp 2.500.000/bln -> Total cicilan 4.500.000 -> DSR = 56.3% (> 40% bahaya!)
        // Free cash flow: 8M - 4.5M - 4.5M = -1.000.000 (DEFISIT!)
        $result = $simService->simulate($baseline, 'loan_installment', [
            'loan_amount' => 40000000,
            'tenor_months' => 20,
            'monthly_installment' => 2500000,
            'admin_fee' => 500000,
            'admin_fee_deducted' => true,
        ]);

        $this->assertEquals('loan_installment', $result['scenario_type']);
        $this->assertEquals(2500000, $result['summary']['monthly_debt_addon']);
        $this->assertEquals(56.3, $result['comparison']['after']['dsr']);
        $this->assertEquals(-1000000, $result['comparison']['after']['free_cashflow']);
        $this->assertEquals('danger', $result['health_status']);
        $this->assertNotEmpty($result['recommendations']);
    }

    public function test_simulate_saving_target_calculates_monthly_need(): void
    {
        $simService = app(FinancialSimulationService::class);

        $baseline = [
            'monthly_income' => 15000000,
            'monthly_expense' => 7000000,
            'monthly_debt' => 1000000,
            'liquid_cash' => 30000000,
        ];

        // Target tabungan Rp 48.000.000 dalam 24 bulan tanpa bunga -> butuh Rp 2.000.000/bln
        $result = $simService->simulate($baseline, 'saving_target', [
            'target_amount' => 48000000,
            'target_months' => 24,
            'annual_return_rate' => 0,
        ]);

        $this->assertEquals('saving_target', $result['scenario_type']);
        $this->assertEquals(2000000, $result['summary']['monthly_savings_addon']);
        // Free cash flow: 15M - 7M - 1M - 2M = 5.000.000
        $this->assertEquals(5000000, $result['comparison']['after']['free_cashflow']);
        $this->assertEquals('safe', $result['health_status']);
    }

    public function test_user_can_view_simulation_page(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/simulations');

        $response->assertOk();
        $response->assertViewHas('baseline');
        $response->assertViewHas('simulations');
        $response->assertSee('Simulasi Keputusan Keuangan');
    }

    public function test_user_can_run_simulation_endpoint(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson('/simulations/simulate', [
                'scenario_type' => 'asset_purchase',
                'parameters' => [
                    'purchase_price' => 25000000,
                    'payment_method' => 'credit',
                    'down_payment' => 5000000,
                    'tenor_months' => 24,
                    'interest_rate_p_a' => 7.5,
                ],
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'baseline',
            'result' => [
                'scenario_type',
                'summary',
                'comparison',
                'health_status',
                'health_score',
            ],
        ]);
    }

    public function test_user_can_store_and_delete_simulation(): void
    {
        $storeResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/simulations', [
                'title' => 'Simulasi Beli Motor Honda Vario',
                'scenario_type' => 'asset_purchase',
                'description' => 'Untuk transportasi kerja sehari-hari',
                'parameters' => [
                    'purchase_price' => 26000000,
                    'payment_method' => 'credit',
                    'down_payment' => 6000000,
                    'tenor_months' => 24,
                    'interest_rate_p_a' => 8.0,
                    'running_cost_monthly' => 450000,
                ],
            ]);

        $storeResponse->assertRedirect(route('simulations.index'));
        $this->assertDatabaseHas('financial_simulations', [
            'space_id' => $this->spaceA->id,
            'title' => 'Simulasi Beli Motor Honda Vario',
            'scenario_type' => 'asset_purchase',
        ]);

        $simulation = FinancialSimulation::where('space_id', $this->spaceA->id)->first();

        // Delete simulation
        $deleteResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->delete("/simulations/{$simulation->id}");

        $deleteResponse->assertRedirect(route('simulations.index'));
        $this->assertDatabaseMissing('financial_simulations', ['id' => $simulation->id]);
    }

    public function test_user_cannot_access_simulation_from_unauthorized_space(): void
    {
        $otherSim = FinancialSimulation::create([
            'space_id' => $this->spaceB->id,
            'user_id' => $this->userB->id,
            'title' => 'Simulasi Rahasia B',
            'scenario_type' => 'loan_installment',
            'parameters' => ['loan_amount' => 50000000],
            'results' => ['health_status' => 'safe'],
        ]);

        // User A cannot view User B's simulation
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->getJson("/simulations/{$otherSim->id}");

        $response->assertForbidden();
    }
}
