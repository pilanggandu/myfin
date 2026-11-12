<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Member;
use App\Models\RecurringSchedule;
use App\Models\Space;
use App\Models\User;
use App\Services\CreditService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdvancedAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Space $space;

    private Account $bca;

    private Account $bibit;

    private Category $incomeCat;

    private Category $foodCat;

    private Category $transportCat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Analis Utama', 'email' => 'analis@example.com']);
        $this->space = Space::create([
            'name' => 'Ruang Analis',
            'type' => 'shared',
            'owner_id' => $this->user->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->space->members()->attach($this->user->id, ['role' => 'owner', 'status' => 'active']);

        $this->bca = Account::create([
            'space_id' => $this->space->id,
            'name' => 'Bank BCA',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->bibit = Account::create([
            'space_id' => $this->space->id,
            'name' => 'Bibit Reksadana',
            'type' => 'investment',
            'opening_balance' => 5000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->incomeCat = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Gaji',
            'type' => 'income',
        ]);

        $this->foodCat = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Makanan',
            'type' => 'expense',
        ]);

        $this->transportCat = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Transportasi',
            'type' => 'expense',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_advanced_analytics(): void
    {
        $response = $this->get('/reports?tab=advanced');

        $response->assertRedirect(route('login'));
    }

    public function test_advanced_analytics_handles_empty_dataset_gracefully(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get('/reports?tab=advanced&year=2025&month=1');

        $response->assertOk();
        $response->assertSee('Analisis Lanjutan');
        $response->assertSee('Rasio Tabungan');

        $aa = $response->viewData('advancedAnalytics');
        $this->assertSame(0, $aa['periodComparison']['current']['income']);
        $this->assertSame(0, $aa['periodComparison']['current']['expense']);
        $this->assertNull($aa['periodComparison']['current']['savings_rate']);
        $this->assertNull($aa['runwayMonths']);
        $this->assertSame(15000000, $aa['balanceSheet']['net_worth']);
    }

    public function test_advanced_analytics_computes_health_ratios_mom_comparison_and_budget_variance(): void
    {
        Carbon::setTestNow('2026-05-15 10:00:00');
        $txService = app(TransactionService::class);
        $creditService = app(CreditService::class);

        // Bulan sebelumnya (April 2026): Pemasukan 8.000.000, Pengeluaran 3.000.000
        $txService->recordIncome($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->incomeCat->id,
            'amount' => 8000000,
            'transaction_date' => '2026-04-01',
            'description' => 'Gaji April',
        ]);
        $txService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCat->id,
            'amount' => 3000000,
            'transaction_date' => '2026-04-10',
            'description' => 'Belanja April',
        ]);

        // Bulan berjalan (Mei 2026): Pemasukan 10.000.000, Pengeluaran 4.000.000 (Makanan 3.500.000 + Transport 500.000)
        $txService->recordIncome($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->incomeCat->id,
            'amount' => 10000000,
            'transaction_date' => '2026-05-01',
            'description' => 'Gaji Mei',
        ]);
        $txService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCat->id,
            'amount' => 3500000,
            'transaction_date' => '2026-05-09', // Sabtu
            'description' => 'Belanja Mei Besar',
        ]);
        $txService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->transportCat->id,
            'amount' => 500000,
            'transaction_date' => '2026-05-12', // Selasa
            'description' => 'Bensin Mei',
        ]);

        // Anggaran Mei: Makanan limit 3.000.000 (over), Transport tidak dianggarkan
        Budget::create([
            'space_id' => $this->space->id,
            'category_id' => $this->foodCat->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 3000000,
        ]);

        // Kartu kredit limit 10.000.000 dengan cicilan pending pokok 2.000.000
        $creditAccount = $creditService->createCreditAccount($this->space, [
            'name' => 'BCA Visa',
            'type' => 'credit_card',
            'credit_limit' => 10000000,
            'billing_cycle_day' => 5,
            'due_day' => 25,
        ]);
        $plan = InstallmentPlan::create([
            'space_id' => $this->space->id,
            'credit_account_id' => $creditAccount->id,
            'category_id' => $this->foodCat->id,
            'name' => 'Cicilan Laptop',
            'total_amount' => 2200000,
            'tenor' => 2,
            'interest_rate' => 0,
            'admin_fee' => 200000,
            'start_date' => '2026-05-01',
            'status' => 'active',
        ]);
        Installment::create([
            'space_id' => $this->space->id,
            'installment_plan_id' => $plan->id,
            'installment_number' => 1,
            'due_date' => '2026-05-25',
            'principal_amount' => 1000000,
            'interest_amount' => 0,
            'fee_amount' => 100000,
            'total_amount' => 1100000,
            'status' => 'pending',
        ]);
        Installment::create([
            'space_id' => $this->space->id,
            'installment_plan_id' => $plan->id,
            'installment_number' => 2,
            'due_date' => '2026-06-25',
            'principal_amount' => 1000000,
            'interest_amount' => 0,
            'fee_amount' => 100000,
            'total_amount' => 1100000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get('/reports?tab=advanced&year=2026&month=5');

        $response->assertOk();
        $aa = $response->viewData('advancedAnalytics');

        // Savings rate = (10jt - 4jt) / 10jt = 60.0%
        $this->assertSame(10000000, $aa['periodComparison']['current']['income']);
        $this->assertSame(4000000, $aa['periodComparison']['current']['expense']);
        $this->assertSame(6000000, $aa['periodComparison']['current']['net']);
        $this->assertSame(60.0, $aa['periodComparison']['current']['savings_rate']);
        $this->assertSame(1000000, $aa['periodComparison']['expense_delta']);

        // Saldo BCA = 10jt + 8jt - 3jt + 10jt - 4jt = 21.000.000; Bibit = 5.000.000; Utang kredit = 2.000.000 -> Net Worth = 24.000.000
        $this->assertSame(21000000, $aa['balanceSheet']['liquid_cash']);
        $this->assertSame(5000000, $aa['balanceSheet']['investment_assets']);
        $this->assertSame(2000000, $aa['balanceSheet']['credit_liabilities']);
        $this->assertSame(24000000, $aa['balanceSheet']['net_worth']);
        $this->assertSame(20.0, $aa['balanceSheet']['credit_utilization']);

        // Weekend spending: 3.5jt dari 4jt = 87.5%
        $this->assertSame(87.5, $aa['dailyPattern']['weekend_pct']);
        $this->assertSame('Sabtu', $aa['dailyPattern']['peak_day']->label);

        // Budget variance: 1 over budget (Makanan) & 1 unbudgeted (Transportasi)
        $this->assertSame(1, $aa['budgetAnalysis']['over_count']);
        $this->assertSame('Transportasi', $aa['budgetAnalysis']['unbudgeted']->first()->name);

        // Proyeksi cicilan 6 bulan: 2.200.000 (pokok 2jt + admin 200rb)
        $this->assertSame(2200000, $aa['commitmentsForecast']['total_6_months']);
        $this->assertSame(2000000, $aa['commitmentsForecast']['remaining_principal']);
        $this->assertSame(200000, $aa['commitmentsForecast']['remaining_interest_and_fee']);

        Carbon::setTestNow();
    }

    public function test_advanced_analytics_isolates_other_space_data_and_foreign_account_filter(): void
    {
        $otherUser = User::factory()->create();
        $otherSpace = Space::create([
            'name' => 'Ruang Lain',
            'type' => 'personal',
            'owner_id' => $otherUser->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $otherSpace->members()->attach($otherUser->id, ['role' => 'owner', 'status' => 'active']);
        $otherAccount = Account::create([
            'space_id' => $otherSpace->id,
            'name' => 'Mandiri Asing',
            'type' => 'bank',
            'opening_balance' => 50000000,
            'opened_on' => '2026-01-01',
        ]);
        $otherCategory = Category::create([
            'space_id' => $otherSpace->id,
            'name' => 'Gaji Asing',
            'type' => 'income',
        ]);

        app(TransactionService::class)->recordIncome($otherSpace, $otherUser, [
            'account_id' => $otherAccount->id,
            'category_id' => $otherCategory->id,
            'amount' => 99000000,
            'transaction_date' => '2026-05-05',
            'description' => 'Rahasia Ruang Lain',
        ]);

        // Mencoba filter account_id milik ruang lain harus diabaikan & data ruang lain tidak boleh bocor
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get('/reports?tab=advanced&year=2026&month=5&account_id='.$otherAccount->id);

        $response->assertOk();
        $response->assertDontSee('Rahasia Ruang Lain');
        $response->assertViewHas('selectedAccountId', null);

        $aa = $response->viewData('advancedAnalytics');
        $this->assertSame(0, $aa['periodComparison']['current']['income']);
        $this->assertSame(15000000, $aa['balanceSheet']['net_worth']);
    }

    public function test_advanced_analytics_supports_twelve_month_horizon_members_and_recurring_forecast(): void
    {
        Carbon::setTestNow('2026-05-10 09:00:00');
        $txService = app(TransactionService::class);

        $member = Member::create([
            'space_id' => $this->space->id,
            'name' => 'Pasangan',
            'color' => '#0B4A8B',
        ]);

        $txService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCat->id,
            'amount' => 750000,
            'transaction_date' => '2026-05-04',
            'description' => 'Belanja Mingguan Pasangan',
            'member_id' => $member->id,
        ]);

        RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Internet Rumah',
            'category_id' => $this->transportCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 20,
            'start_date' => '2026-01-01',
            'next_due_date' => '2026-05-20',
            'amount' => 400000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get('/reports?tab=advanced&year=2026&month=5&horizon=12');

        $response->assertOk();
        $aa = $response->viewData('advancedAnalytics');

        $this->assertSame(12, $aa['horizonMonths']);
        $this->assertCount(12, $aa['trendData']['months']);
        $this->assertCount(1, $aa['memberBreakdown']);
        $this->assertSame('Pasangan', $aa['memberBreakdown']->first()->name);
        $this->assertSame(2400000, $aa['commitmentsForecast']['total_6_months']);
        $this->assertSame('Belanja Mingguan Pasangan', $aa['topExpenses']->first()->description);

        Carbon::setTestNow();
    }

    public function test_advanced_analytics_rejects_invalid_horizon(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get('/reports?tab=advanced&horizon=99');

        $response->assertSessionHasErrors(['horizon']);
    }
}
