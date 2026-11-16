<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Space;
use App\Models\User;
use App\Services\BudgetRolloverService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetRolloverTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

    private Account $account;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create(['name' => 'User A', 'email' => 'a@example.com']);
        $this->spaceA = Space::create([
            'name' => 'Ruang Personal A',
            'type' => 'personal',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceA->members()->attach($this->userA->id, ['role' => 'owner', 'status' => 'active']);

        $this->userB = User::factory()->create(['name' => 'User B', 'email' => 'b@example.com']);
        $this->spaceB = Space::create([
            'name' => 'Ruang Personal B',
            'type' => 'personal',
            'owner_id' => $this->userB->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceB->members()->attach($this->userB->id, ['role' => 'owner', 'status' => 'active']);

        $this->account = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Bank BCA',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->category = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Makanan & Minuman',
            'type' => 'expense',
            'rollover_enabled' => true,
            'rollover_mode' => 'all',
            'rollover_cap' => null,
        ]);
    }

    public function test_surplus_rolls_over_to_next_month_when_enabled(): void
    {
        // Month 1 (April 2026): Budget 1,000,000, spent 700,000 -> Surplus 300,000
        Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 4,
            'limit_amount' => 1000000,
        ]);

        $txService = app(TransactionService::class);
        $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 700000,
            'transaction_date' => '2026-04-10',
            'description' => 'Makan Resto',
        ]);

        // Month 2 (May 2026): Budget 1,000,000
        $mayBudget = Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 1000000,
        ]);

        $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 500000,
            'transaction_date' => '2026-05-15',
            'description' => 'Makan Siang',
        ]);

        $rolloverService = app(BudgetRolloverService::class);
        $rollover = $rolloverService->calculateRollover($this->spaceA, $this->category, 2026, 5);

        $this->assertEquals(300000, $rollover['rollover_amount']);
        $this->assertEquals(300000, $mayBudget->rolloverAmount());
        $this->assertEquals(1300000, $mayBudget->effectiveLimitAmount());
        $this->assertEquals(800000, $mayBudget->remainingAmount()); // 1,300,000 - 500,000
        $this->assertFalse($mayBudget->isOverBudget());
        $this->assertEquals(38.5, $mayBudget->percentageSpent()); // round(500,000 / 1,300,000 * 100, 1)
    }

    public function test_deficit_rolls_over_as_negative_deduction_when_mode_is_all(): void
    {
        // Month 1 (April 2026): Budget 1,000,000, spent 1,200,000 -> Deficit -200,000
        Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 4,
            'limit_amount' => 1000000,
        ]);

        $txService = app(TransactionService::class);
        $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 1200000,
            'transaction_date' => '2026-04-20',
            'description' => 'Traktir Teman',
        ]);

        // Month 2 (May 2026): Budget 1,000,000
        $mayBudget = Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 1000000,
        ]);

        $rolloverService = app(BudgetRolloverService::class);
        $rollover = $rolloverService->calculateRollover($this->spaceA, $this->category, 2026, 5);

        $this->assertEquals(-200000, $rollover['rollover_amount']);
        $this->assertEquals(800000, $mayBudget->effectiveLimitAmount()); // 1,000,000 - 200,000
        $this->assertEquals(800000, $mayBudget->remainingAmount()); // no expenses yet in May
    }

    public function test_surplus_only_mode_does_not_deduct_deficits(): void
    {
        $this->category->update([
            'rollover_mode' => 'surplus_only',
        ]);

        // April has deficit
        Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 4,
            'limit_amount' => 1000000,
        ]);

        $txService = app(TransactionService::class);
        $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 1300000,
            'transaction_date' => '2026-04-25',
            'description' => 'Belanja Besar',
        ]);

        $mayBudget = Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 1000000,
        ]);

        $rolloverService = app(BudgetRolloverService::class);
        $rollover = $rolloverService->calculateRollover($this->spaceA, $this->category, 2026, 5);

        $this->assertEquals(0, $rollover['rollover_amount']);
        $this->assertEquals(1000000, $mayBudget->effectiveLimitAmount());
    }

    public function test_deficit_only_mode_does_not_carry_surplus(): void
    {
        $this->category->update([
            'rollover_mode' => 'deficit_only',
        ]);

        // April has surplus
        Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 4,
            'limit_amount' => 1000000,
        ]);

        $txService = app(TransactionService::class);
        $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 600000,
            'transaction_date' => '2026-04-15',
            'description' => 'Makan Hemat',
        ]);

        $mayBudget = Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 1000000,
        ]);

        $rolloverService = app(BudgetRolloverService::class);
        $rollover = $rolloverService->calculateRollover($this->spaceA, $this->category, 2026, 5);

        $this->assertEquals(0, $rollover['rollover_amount']);
        $this->assertEquals(1000000, $mayBudget->effectiveLimitAmount());
    }

    public function test_rollover_cap_restricts_maximum_positive_surplus(): void
    {
        $this->category->update([
            'rollover_mode' => 'all',
            'rollover_cap' => 250000,
        ]);

        // April has 800,000 surplus
        Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 4,
            'limit_amount' => 1000000,
        ]);

        $txService = app(TransactionService::class);
        $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 200000,
            'transaction_date' => '2026-04-12',
            'description' => 'Makan Murah',
        ]);

        $mayBudget = Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 1000000,
        ]);

        $rolloverService = app(BudgetRolloverService::class);
        $rollover = $rolloverService->calculateRollover($this->spaceA, $this->category, 2026, 5);

        // Cap is 250,000 so rollover is capped at 250,000 instead of 800,000
        $this->assertEquals(250000, $rollover['rollover_amount']);
        $this->assertEquals(1250000, $mayBudget->effectiveLimitAmount());
    }

    public function test_chained_rollover_across_three_consecutive_months(): void
    {
        $txService = app(TransactionService::class);

        // Month 1 (March): Budget 1,000,000, spent 600,000 -> Surplus 400,000
        Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 3,
            'limit_amount' => 1000000,
        ]);
        $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 600000,
            'transaction_date' => '2026-03-10',
            'description' => 'Makan Maret',
        ]);

        // Month 2 (April): Budget 1,000,000, rollover from March is +400,000 -> Effective 1,400,000
        // Spent 1,100,000 -> Remaining in April is 300,000 surplus
        Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 4,
            'limit_amount' => 1000000,
        ]);
        $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 1100000,
            'transaction_date' => '2026-04-15',
            'description' => 'Makan April',
        ]);

        // Month 3 (May): Budget 1,000,000, rollover should be +300,000 -> Effective 1,300,000
        $mayBudget = Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 1000000,
        ]);

        $rolloverService = app(BudgetRolloverService::class);

        $aprilRollover = $rolloverService->calculateRollover($this->spaceA, $this->category, 2026, 4);
        $this->assertEquals(400000, $aprilRollover['rollover_amount']);

        $mayRollover = $rolloverService->calculateRollover($this->spaceA, $this->category, 2026, 5);
        $this->assertEquals(300000, $mayRollover['rollover_amount']);
        $this->assertEquals(1300000, $mayBudget->effectiveLimitAmount());
    }

    public function test_disabled_rollover_produces_zero_rollover(): void
    {
        $this->category->update(['rollover_enabled' => false]);

        Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 4,
            'limit_amount' => 1000000,
        ]);

        $txService = app(TransactionService::class);
        $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 400000,
            'transaction_date' => '2026-04-10',
            'description' => 'Makan',
        ]);

        $mayBudget = Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 1000000,
        ]);

        $rolloverService = app(BudgetRolloverService::class);
        $rollover = $rolloverService->calculateRollover($this->spaceA, $this->category, 2026, 5);

        $this->assertEquals(0, $rollover['rollover_amount']);
        $this->assertEquals(1000000, $mayBudget->effectiveLimitAmount());
    }

    public function test_user_can_update_category_rollover_settings_via_endpoint(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post("/categories/{$this->category->id}/rollover", [
                'rollover_enabled' => 1,
                'rollover_mode' => 'surplus_only',
                'rollover_cap' => 350000,
            ]);

        $response->assertSessionHas('success');
        $this->category->refresh();

        $this->assertTrue($this->category->rollover_enabled);
        $this->assertEquals('surplus_only', $this->category->rollover_mode);
        $this->assertEquals(350000, $this->category->rollover_cap);
    }

    public function test_user_cannot_update_rollover_settings_of_another_space(): void
    {
        $otherCategory = Category::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Kategori Luar',
            'type' => 'expense',
            'rollover_enabled' => false,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post("/categories/{$otherCategory->id}/rollover", [
                'rollover_enabled' => 1,
                'rollover_mode' => 'all',
            ]);

        $response->assertForbidden();
    }

    public function test_budget_index_view_displays_rollover_data(): void
    {
        Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 4,
            'limit_amount' => 1000000,
        ]);

        $txService = app(TransactionService::class);
        $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 700000,
            'transaction_date' => '2026-04-10',
            'description' => 'Makan',
        ]);

        Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 1000000,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/budgets?year=2026&month=5');

        $response->assertOk();
        $response->assertViewHas('totalBudget', 1300000);
        $response->assertViewHas('totalRollover', 300000);
        $response->assertSee('Bergulir');
        $response->assertSee('+Rp 300.000');
    }
}
