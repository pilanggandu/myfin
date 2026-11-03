<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Space;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private Space $spaceA;
    private Space $spaceB;
    private Account $bca;
    private Category $expenseCat;
    private Category $incomeCat;

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

        $this->bca = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Bank BCA',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->expenseCat = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Makanan & Minuman',
            'type' => 'expense',
        ]);

        $this->incomeCat = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Gaji Pokok',
            'type' => 'income',
        ]);
    }

    public function test_user_can_create_budget_for_expense_category(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/budgets', [
                'category_id' => $this->expenseCat->id,
                'year' => 2026,
                'month' => 5,
                'limit_amount' => 2000000,
            ]);

        $response->assertRedirect('/budgets?year=2026&month=5');
        $this->assertDatabaseHas('budgets', [
            'space_id' => $this->spaceA->id,
            'category_id' => $this->expenseCat->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 2000000,
        ]);
    }

    public function test_cannot_create_budget_for_income_category(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/budgets', [
                'category_id' => $this->incomeCat->id,
                'year' => 2026,
                'month' => 5,
                'limit_amount' => 1000000,
            ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertEquals(0, Budget::count());
    }

    public function test_duplicate_budget_in_same_period_fails_validation(): void
    {
        Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->expenseCat->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 1500000,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/budgets', [
                'category_id' => $this->expenseCat->id,
                'year' => 2026,
                'month' => 5,
                'limit_amount' => 2500000,
            ]);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_user_can_update_budget_limit(): void
    {
        $budget = Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->expenseCat->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 1500000,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put("/budgets/{$budget->id}", [
                'limit_amount' => 3000000,
            ]);

        $response->assertRedirect('/budgets?year=2026&month=5');
        $this->assertEquals(3000000, $budget->fresh()->limit_amount);
    }

    public function test_user_can_delete_budget(): void
    {
        $budget = Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->expenseCat->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 1500000,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->delete("/budgets/{$budget->id}");

        $response->assertRedirect('/budgets?year=2026&month=5');
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    public function test_budget_spent_calculation_matches_posted_expense_transactions(): void
    {
        $budget = Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->expenseCat->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 1000000,
        ]);

        $service = app(TransactionService::class);

        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 450000,
            'transaction_date' => '2026-05-10',
            'description' => 'Makan Resto',
        ]);

        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 150000,
            'transaction_date' => '2026-05-15',
            'description' => 'Beli Kopi',
        ]);

        $voidTx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 300000,
            'transaction_date' => '2026-05-20',
            'description' => 'Transaksi Batal',
        ]);
        $service->voidTransaction($voidTx, $this->userA);

        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 500000,
            'transaction_date' => '2026-06-02',
            'description' => 'Bulan Lain',
        ]);

        $this->assertEquals(600000, $budget->spentAmount());
        $this->assertEquals(400000, $budget->remainingAmount());
        $this->assertEquals(60.0, $budget->percentageSpent());
        $this->assertFalse($budget->isOverBudget());

        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 500000,
            'transaction_date' => '2026-05-25',
            'description' => 'Makan Mewah',
        ]);

        $this->assertEquals(1100000, $budget->spentAmount());
        $this->assertEquals(-100000, $budget->remainingAmount());
        $this->assertEquals(110.0, $budget->percentageSpent());
        $this->assertTrue($budget->isOverBudget());
    }

    public function test_space_isolation_cannot_view_or_modify_other_space_budget(): void
    {
        $categoryB = Category::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Belanja B',
            'type' => 'expense',
        ]);

        $budgetB = Budget::create([
            'space_id' => $this->spaceB->id,
            'category_id' => $categoryB->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 2000000,
        ]);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put("/budgets/{$budgetB->id}", [
                'limit_amount' => 5000000,
            ])
            ->assertStatus(403);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->delete("/budgets/{$budgetB->id}")
            ->assertStatus(403);
    }

    public function test_space_isolation_cannot_create_budget_with_other_space_category(): void
    {
        $categoryB = Category::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Belanja B',
            'type' => 'expense',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/budgets', [
                'category_id' => $categoryB->id,
                'year' => 2026,
                'month' => 5,
                'limit_amount' => 1000000,
            ]);

        $response->assertSessionHasErrors('category_id');
    }
}
