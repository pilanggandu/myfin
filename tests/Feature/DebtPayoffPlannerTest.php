<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Space;
use App\Models\User;
use App\Services\DebtPayoffPlannerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtPayoffPlannerTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

    private CreditAccount $creditCardA;

    private Category $electronicsCategory;

    private DebtPayoffPlannerService $payoffService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create(['name' => 'Gilang User A', 'email' => 'gilang.a@example.com']);
        $this->userB = User::factory()->create(['name' => 'Other User B', 'email' => 'other.b@example.com']);

        $this->spaceA = Space::create([
            'name' => 'Ruang Personal A',
            'type' => 'personal',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceA->members()->attach($this->userA->id, ['role' => 'owner', 'status' => 'active']);

        $this->spaceB = Space::create([
            'name' => 'Ruang Personal B',
            'type' => 'personal',
            'owner_id' => $this->userB->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceB->members()->attach($this->userB->id, ['role' => 'owner', 'status' => 'active']);

        $ledgerAcc = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'BCA Card (Kewajiban)',
            'type' => 'credit_card',
            'opening_balance' => 0,
            'opened_on' => '2026-01-01',
        ]);

        $this->creditCardA = CreditAccount::create([
            'space_id' => $this->spaceA->id,
            'account_id' => $ledgerAcc->id,
            'name' => 'BCA Visa Platinum',
            'type' => 'credit_card',
            'credit_limit' => 20000000,
            'billing_cycle_day' => 15,
            'due_day' => 5,
        ]);

        $this->electronicsCategory = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Elektronik & Gadget',
            'type' => 'expense',
        ]);

        $this->payoffService = app(DebtPayoffPlannerService::class);
    }

    public function test_user_can_access_payoff_planner_with_no_debts(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('credit.payoff.index'));

        $response->assertStatus(200);
        $response->assertSee('Perencanaan Pelunasan Utang');
        $response->assertSee('Bebas dari Utang &amp; Cicilan!', false);
    }

    public function test_user_can_access_payoff_planner_with_active_debts(): void
    {
        // Buat satu rencana cicilan aktif dengan 3 angsuran pending
        $tx = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'transaction_date' => '2026-11-01',
            'description' => 'Pembelian Monitor 4K',
            'category_id' => $this->electronicsCategory->id,
            'created_by' => $this->userA->id,
        ]);

        $plan = InstallmentPlan::create([
            'space_id' => $this->spaceA->id,
            'credit_account_id' => $this->creditCardA->id,
            'financial_transaction_id' => $tx->id,
            'category_id' => $this->electronicsCategory->id,
            'name' => 'Cicilan Monitor 4K',
            'total_amount' => 6000000,
            'tenor' => 3,
            'interest_rate' => 12.0,
            'start_date' => '2026-11-01',
            'status' => 'active',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            Installment::create([
                'space_id' => $this->spaceA->id,
                'installment_plan_id' => $plan->id,
                'installment_number' => $i,
                'due_date' => Carbon::parse('2026-11-01')->addMonths($i)->toDateString(),
                'principal_amount' => 2000000,
                'interest_amount' => 50000,
                'fee_amount' => 0,
                'total_amount' => 2050000,
                'status' => 'pending',
            ]);
        }

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('credit.payoff.index'));

        $response->assertStatus(200);
        $response->assertSee('Perencanaan Pelunasan Utang');
        $response->assertSee('Debt Avalanche');
        $response->assertSee('Debt Snowball');
        $response->assertSee('Cicilan Monitor 4K');
    }

    public function test_payoff_strategies_calculation_accurately_compares_avalanche_and_snowball(): void
    {
        // Buat 2 utang dengan karakteristik berbeda:
        // Utang 1: Bunga TINGGI (24% / thn), Saldo BESAR (Rp 10.000.000), Min Rp 1.000.000/bln
        // Utang 2: Bunga RENDAH (6% / thn), Saldo KECIL (Rp 2.000.000), Min Rp 500.000/bln
        $debts = [
            [
                'id' => 'debt_laptop',
                'name' => 'Laptop High Interest',
                'remaining_principal' => 10000000.0,
                'monthly_min_payment' => 1000000.0,
                'annual_interest_rate' => 24.0,
            ],
            [
                'id' => 'debt_smartphone',
                'name' => 'Smartphone Low Interest',
                'remaining_principal' => 2000000.0,
                'monthly_min_payment' => 500000.0,
                'annual_interest_rate' => 6.0,
            ],
        ];

        // Simulasi dengan dana ekstra Rp 500.000/bulan
        $result = $this->payoffService->calculateStrategies($debts, 500000.0, 0.0, Carbon::parse('2026-11-01'));

        $this->assertTrue($result['has_debts']);
        $this->assertEquals(12000000, $result['total_debt_amount']);

        $avalanche = $result['strategies']['avalanche'];
        $snowball = $result['strategies']['snowball'];
        $standard = $result['strategies']['standard'];

        // 1. Pada Avalanche: Laptop (bunga 24%) harus menjadi prioritas penyerangan pertama
        $this->assertEquals('debt_laptop', $avalanche['target_priority_order'][0]['id']);

        // 2. Pada Snowball: Smartphone (saldo Rp 2jt) harus menjadi prioritas penyerangan pertama
        $this->assertEquals('debt_smartphone', $snowball['target_priority_order'][0]['id']);

        // 3. Total bunga yang dibayar di Avalanche harus lebih kecil dari Snowball
        $this->assertLessThanOrEqual($snowball['total_interest_paid'], $avalanche['total_interest_paid']);

        // 4. Kedua strategi yang memakai dana ekstra harus lunas lebih cepat dan lebih hemat daripada jadwal standar
        $this->assertLessThanOrEqual($standard['total_months'], $avalanche['total_months']);
        $this->assertLessThanOrEqual($standard['total_interest_paid'], $avalanche['total_interest_paid']);
        $this->assertGreaterThan(0, $result['comparison']['avalanche_vs_standard']['interest_saved']);
    }

    public function test_extra_monthly_payment_accelerates_payoff(): void
    {
        $debts = [
            [
                'id' => 'debt_motor',
                'name' => 'Kredit Motor',
                'remaining_principal' => 15000000.0,
                'monthly_min_payment' => 1000000.0,
                'annual_interest_rate' => 12.0,
            ],
        ];

        // 0 ekstra
        $calcNoExtra = $this->payoffService->calculateStrategies($debts, 0.0);
        // Rp 1.000.000 ekstra (total bayar Rp 2jt/bulan)
        $calcWithExtra = $this->payoffService->calculateStrategies($debts, 1000000.0);

        $monthsNoExtra = $calcNoExtra['strategies']['avalanche']['total_months'];
        $monthsWithExtra = $calcWithExtra['strategies']['avalanche']['total_months'];

        $this->assertLessThan($monthsNoExtra, $monthsWithExtra);
        $this->assertGreaterThan(0, $calcWithExtra['comparison']['avalanche_vs_standard']['months_saved']);
    }

    public function test_one_time_lump_sum_reduces_initial_debt_and_payoff_time(): void
    {
        $debts = [
            [
                'id' => 'debt_furniture',
                'name' => 'Cicilan Kasur',
                'remaining_principal' => 6000000.0,
                'monthly_min_payment' => 1000000.0,
                'annual_interest_rate' => 12.0,
            ],
        ];

        // Rp 3.000.000 lump sum langsung di awal
        $calcLumpSum = $this->payoffService->calculateStrategies($debts, 0.0, 3000000.0);

        $this->assertLessThan(6, $calcLumpSum['strategies']['avalanche']['total_months']);
        $this->assertGreaterThan(0, $calcLumpSum['comparison']['avalanche_vs_standard']['interest_saved']);
    }

    public function test_simulate_endpoint_returns_json_comparison(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('credit.payoff.simulate'), [
                'extra_monthly' => 500000,
                'one_time_payment' => 1000000,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'extra_monthly' => 500000,
                'one_time_payment' => 1000000,
            ],
        ]);
    }

    public function test_user_cannot_view_payoff_planner_for_unauthorized_space(): void
    {
        // User A mencoba mengakses Ruang B tanpa menjadi anggota Ruang B
        $response = $this->actingAs($this->userA)
            ->get(route('credit.payoff.index', ['space_id' => $this->spaceB->id]));

        $response->assertStatus(403);
    }
}
