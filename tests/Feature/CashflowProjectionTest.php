<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\PlannedIncome;
use App\Models\RecurringSchedule;
use App\Models\Space;
use App\Models\User;
use App\Services\CashflowProjectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CashflowProjectionTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

    private Account $bca;

    private Account $mandiri;

    private Category $incomeCat;

    private Category $expenseCat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'name' => 'User A',
            'email' => 'a@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->spaceA = Space::create([
            'name' => 'Ruang Utama A',
            'type' => 'personal',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceA->members()->attach($this->userA->id, ['role' => 'owner', 'status' => 'active']);

        $this->userB = User::factory()->create([
            'name' => 'User B',
            'email' => 'b@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->spaceB = Space::create([
            'name' => 'Ruang Utama B',
            'type' => 'personal',
            'owner_id' => $this->userB->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceB->members()->attach($this->userB->id, ['role' => 'owner', 'status' => 'active']);

        $this->bca = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'BCA Utama',
            'type' => 'bank',
            'opening_balance' => 5000000,
            'opened_on' => '2026-01-01',
            'is_default' => true,
        ]);

        $this->mandiri = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Mandiri Tabungan',
            'type' => 'bank',
            'opening_balance' => 3000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->incomeCat = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Gaji & Bonus',
            'type' => 'income',
        ]);

        $this->expenseCat = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Tagihan Rutin',
            'type' => 'expense',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('projections.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_projections_dashboard(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('projections.index'));

        $response->assertOk();
        $response->assertSee('Proyeksi Saldo dan Arus Kas');
        $response->assertSee('Saldo Kas Awal');
        $response->assertSee('Rp 8.000.000'); // 5jt BCA + 3jt Mandiri
        $response->assertSee('Simulasi dan Grafik Proyeksi');
    }

    public function test_projection_calculates_balance_from_recurring_transactions_installments_and_planned_incomes(): void
    {
        Carbon::setTestNow('2026-11-13 10:00:00');

        // 1. Recurring Expense: Sewa Wifi Rp 400.000 jatuh tempo 2026-11-20
        RecurringSchedule::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'name' => 'Wifi Internet',
            'category_id' => $this->expenseCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 20,
            'start_date' => '2026-11-01',
            'next_due_date' => '2026-11-20',
            'amount' => 400000,
            'status' => 'active',
            'created_by' => $this->userA->id,
        ]);

        // 2. Recurring Income: Gaji Rp 6.000.000 jatuh tempo 2026-11-25
        RecurringSchedule::create([
            'space_id' => $this->spaceA->id,
            'type' => 'income',
            'name' => 'Gaji Pokok',
            'category_id' => $this->incomeCat->id,
            'source_account_id' => $this->bca->id,
            'destination_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 25,
            'start_date' => '2026-11-01',
            'next_due_date' => '2026-11-25',
            'amount' => 6000000,
            'status' => 'active',
            'created_by' => $this->userA->id,
        ]);

        // 3. Installment Plan & Pending Installment: Rp 1.500.000 jatuh tempo 2026-11-28
        $creditCardAcc = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Kartu Kredit',
            'type' => 'credit_card',
            'opening_balance' => 0,
            'opened_on' => '2026-01-01',
        ]);
        $creditAcc = CreditAccount::create([
            'space_id' => $this->spaceA->id,
            'account_id' => $creditCardAcc->id,
            'name' => 'Kartu Kredit BCA',
            'credit_limit' => 20000000,
            'billing_cycle_day' => 15,
            'payment_due_day' => 28,
        ]);

        $purchaseTx = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'transaction_date' => '2026-11-01',
            'description' => 'Beli Handphone',
            'category_id' => $this->expenseCat->id,
            'status' => 'posted',
            'created_by' => $this->userA->id,
        ]);
        $plan = InstallmentPlan::create([
            'space_id' => $this->spaceA->id,
            'financial_transaction_id' => $purchaseTx->id,
            'credit_account_id' => $creditAcc->id,
            'category_id' => $this->expenseCat->id,
            'name' => 'Cicilan HP',
            'title' => 'Cicilan HP',
            'total_amount' => 4500000,
            'tenor' => 3,
            'interest_rate' => 0,
            'admin_fee' => 0,
            'installment_amount' => 1500000,
            'start_date' => '2026-11-01',
            'status' => 'active',
        ]);
        Installment::create([
            'space_id' => $this->spaceA->id,
            'installment_plan_id' => $plan->id,
            'installment_number' => 1,
            'due_date' => '2026-11-28',
            'principal_amount' => 1500000,
            'interest_amount' => 0,
            'fee_amount' => 0,
            'total_amount' => 1500000,
            'status' => 'pending',
        ]);

        // 4. Planned Income: Bonus Rp 2.000.000 jatuh tempo 2026-11-30
        PlannedIncome::create([
            'space_id' => $this->spaceA->id,
            'title' => 'Bonus Kuartal',
            'amount' => 2000000,
            'expected_date' => '2026-11-30',
            'account_id' => $this->bca->id,
            'category_id' => $this->incomeCat->id,
            'status' => 'planned',
            'created_by' => $this->userA->id,
        ]);

        /** @var CashflowProjectionService $service */
        $service = app(CashflowProjectionService::class);
        $projection = $service->project($this->spaceA, [
            'start_date' => '2026-11-13',
            'horizon_days' => 30,
        ]);

        // Math:
        // Saldo Awal = 8.000.000
        // Inflows = 6.000.000 (Gaji) + 2.000.000 (Bonus) = 8.000.000
        // Outflows = 400.000 (Wifi) + 1.500.000 (Cicilan) = 1.900.000
        // Net Change = +6.100.000
        // Ending Balance = 8.000.000 + 6.100.000 = 14.100.000
        $this->assertEquals(8000000, $projection['starting_balance']);
        $this->assertEquals(8000000, $projection['total_inflows']);
        $this->assertEquals(1900000, $projection['total_outflows']);
        $this->assertEquals(6100000, $projection['net_change']);
        $this->assertEquals(14100000, $projection['ending_balance']);
        $this->assertFalse($projection['is_deficit_risk']);

        // Check timeline event items count
        $this->assertCount(4, $projection['timeline_events']);

        // Check web response displays the data
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('projections.index'));

        $response->assertOk();
        $response->assertSee('Wifi Internet');
        $response->assertSee('Gaji Pokok');
        $response->assertSee('Cicilan HP');
        $response->assertSee('Bonus Kuartal');
        $response->assertSee('+Rp 8.000.000');
        $response->assertSee('-Rp 1.900.000');
    }

    public function test_projection_detects_deficit_risk_when_balance_dips_below_zero(): void
    {
        Carbon::setTestNow('2026-11-13 10:00:00');

        // Zero-out accounts and set starting balance to small amount 500.000
        $this->bca->update(['opening_balance' => 500000]);
        $this->mandiri->update(['opening_balance' => 0]);

        // Add large recurring expense: Sewa Gedung Rp 3.000.000 pada 2026-11-18
        RecurringSchedule::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'name' => 'Sewa Gedung',
            'category_id' => $this->expenseCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 18,
            'start_date' => '2026-11-01',
            'next_due_date' => '2026-11-18',
            'amount' => 3000000,
            'status' => 'active',
            'created_by' => $this->userA->id,
        ]);

        /** @var CashflowProjectionService $service */
        $service = app(CashflowProjectionService::class);
        $projection = $service->project($this->spaceA, [
            'start_date' => '2026-11-13',
            'horizon_days' => 30,
        ]);

        // Starting 500.000 - 3.000.000 = -2.500.000
        $this->assertTrue($projection['is_deficit_risk']);
        $this->assertEquals('2026-11-18', $projection['deficit_date']);
        $this->assertEquals(-2500000, $projection['lowest_balance']);
        $this->assertEquals(2500000, $projection['max_deficit_amount']);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('projections.index'));

        $response->assertOk();
        $response->assertSee('Peringatan Risiko Defisit Kas!');
        $response->assertSee('Rp -2.500.000');
    }

    public function test_projection_filters_by_specific_account(): void
    {
        Carbon::setTestNow('2026-11-13 10:00:00');

        // BCA has 5.000.000, Mandiri has 3.000.000
        // Expense on BCA: 1.000.000
        RecurringSchedule::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'name' => 'Biaya Server BCA',
            'category_id' => $this->expenseCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 20,
            'start_date' => '2026-11-01',
            'next_due_date' => '2026-11-20',
            'amount' => 1000000,
            'status' => 'active',
            'created_by' => $this->userA->id,
        ]);

        // Expense on Mandiri: 500.000
        RecurringSchedule::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'name' => 'Biaya Mandiri',
            'category_id' => $this->expenseCat->id,
            'source_account_id' => $this->mandiri->id,
            'frequency' => 'monthly',
            'anchor_day' => 22,
            'start_date' => '2026-11-01',
            'next_due_date' => '2026-11-22',
            'amount' => 500000,
            'status' => 'active',
            'created_by' => $this->userA->id,
        ]);

        /** @var CashflowProjectionService $service */
        $service = app(CashflowProjectionService::class);

        // Filter only BCA
        $projection = $service->project($this->spaceA, [
            'start_date' => '2026-11-13',
            'horizon_days' => 30,
            'account_id' => $this->bca->id,
        ]);

        $this->assertEquals(5000000, $projection['starting_balance']);
        $this->assertEquals(1000000, $projection['total_outflows']);
        $this->assertEquals(4000000, $projection['ending_balance']);
        $this->assertCount(1, $projection['timeline_events']);
        $this->assertEquals('Biaya Server BCA', $projection['timeline_events'][0]['title']);
    }

    public function test_user_can_create_planned_income(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('planned-incomes.store'), [
                'title' => 'Honor Konsultasi',
                'amount' => 3500000,
                'expected_date' => '2026-11-25',
                'account_id' => $this->bca->id,
                'category_id' => $this->incomeCat->id,
                'notes' => 'Proyek audit sistem keuangan',
            ]);

        $response->assertRedirect(route('projections.index', ['tab' => 'planned_incomes']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('planned_incomes', [
            'space_id' => $this->spaceA->id,
            'title' => 'Honor Konsultasi',
            'amount' => 3500000,
            'expected_date' => '2026-11-25',
            'status' => 'planned',
        ]);
    }

    public function test_user_can_update_and_delete_planned_income(): void
    {
        $plan = PlannedIncome::create([
            'space_id' => $this->spaceA->id,
            'title' => 'Dividen Saham',
            'amount' => 1500000,
            'expected_date' => '2026-11-20',
            'status' => 'planned',
            'created_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put(route('planned-incomes.update', $plan), [
                'title' => 'Dividen Saham BBCA',
                'amount' => 1750000,
                'expected_date' => '2026-11-22',
            ]);

        $response->assertRedirect(route('projections.index', ['tab' => 'planned_incomes']));
        $this->assertDatabaseHas('planned_incomes', [
            'id' => $plan->id,
            'title' => 'Dividen Saham BBCA',
            'amount' => 1750000,
        ]);

        // Delete
        $deleteResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->delete(route('planned-incomes.destroy', $plan));

        $deleteResponse->assertRedirect(route('projections.index', ['tab' => 'planned_incomes']));
        $this->assertDatabaseMissing('planned_incomes', ['id' => $plan->id]);
    }

    public function test_user_can_realize_planned_income_and_record_financial_transaction(): void
    {
        $plan = PlannedIncome::create([
            'space_id' => $this->spaceA->id,
            'title' => 'Pelunasan Hutang Teman',
            'amount' => 2000000,
            'expected_date' => '2026-11-18',
            'account_id' => $this->bca->id,
            'category_id' => $this->incomeCat->id,
            'status' => 'planned',
            'created_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('planned-incomes.realize', $plan), [
                'actual_amount' => 2000000,
                'record_transaction' => '1',
                'account_id' => $this->bca->id,
                'category_id' => $this->incomeCat->id,
                'received_date' => '2026-11-18',
            ]);

        $response->assertRedirect(route('projections.index', ['tab' => 'planned_incomes']));

        $plan->refresh();
        $this->assertEquals('received', $plan->status);
        $this->assertNotNull($plan->received_at);
        $this->assertNotNull($plan->financial_transaction_id);

        $this->assertDatabaseHas('financial_transactions', [
            'id' => $plan->financial_transaction_id,
            'space_id' => $this->spaceA->id,
            'type' => 'income',
            'status' => 'posted',
        ]);
    }

    public function test_user_can_cancel_and_reopen_planned_income(): void
    {
        $plan = PlannedIncome::create([
            'space_id' => $this->spaceA->id,
            'title' => 'Proyek Freelance',
            'amount' => 5000000,
            'expected_date' => '2026-11-20',
            'status' => 'planned',
            'created_by' => $this->userA->id,
        ]);

        // Cancel
        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('planned-incomes.cancel', $plan))
            ->assertRedirect(route('projections.index', ['tab' => 'planned_incomes']));

        $this->assertEquals('cancelled', $plan->fresh()->status);

        // Reopen
        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('planned-incomes.reopen', $plan))
            ->assertRedirect(route('projections.index', ['tab' => 'planned_incomes']));

        $this->assertEquals('planned', $plan->fresh()->status);
    }

    public function test_space_isolation_prevents_unauthorized_access(): void
    {
        $planB = PlannedIncome::create([
            'space_id' => $this->spaceB->id,
            'title' => 'Pemasukan Rahasia B',
            'amount' => 10000000,
            'expected_date' => '2026-11-25',
            'status' => 'planned',
            'created_by' => $this->userB->id,
        ]);

        // User A cannot update User B's planned income
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put(route('planned-incomes.update', $planB), [
                'title' => 'Hacked',
                'amount' => 100,
                'expected_date' => '2026-11-25',
            ]);

        $response->assertForbidden();

        // User A cannot delete User B's planned income
        $delResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->delete(route('planned-incomes.destroy', $planB));

        $delResponse->assertForbidden();
    }

    public function test_user_can_export_projection_csv(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('projections.export-csv', ['horizon' => 30]));

        $response->assertOk();
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('content-type'));
        $this->assertStringContainsString('LAPORAN PROYEKSI SALDO DAN ARUS KAS', $response->streamedContent());
    }
}
