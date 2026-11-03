<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Member;
use App\Models\RecurringSchedule;
use App\Models\Space;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DataCleanTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

    private Account $bca;

    private Category $foodCategory;

    private Member $memberMe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'name' => 'Owner A',
            'email' => 'ownerA@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->spaceA = Space::create([
            'name' => 'Ruang Utama A',
            'type' => 'personal',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceA->members()->attach($this->userA->id, ['role' => 'owner', 'status' => 'active']);
        $this->spaceA->createDefaultCategories();

        $this->userB = User::factory()->create([
            'name' => 'Owner B',
            'email' => 'ownerB@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->spaceB = Space::create([
            'name' => 'Ruang B',
            'type' => 'personal',
            'owner_id' => $this->userB->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceB->members()->attach($this->userB->id, ['role' => 'owner', 'status' => 'active']);

        $this->bca = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'BCA',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->foodCategory = Category::where('space_id', $this->spaceA->id)
            ->where('name', 'Makanan & Minuman')
            ->first() ?? Category::create([
                'space_id' => $this->spaceA->id,
                'name' => 'Makanan & Minuman',
                'type' => 'expense',
            ]);

        $this->memberMe = Member::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Me',
            'color' => '#1B4D3E',
            'created_by' => $this->userA->id,
        ]);
    }

    public function test_user_can_clean_transactions_only(): void
    {
        $txService = app(TransactionService::class);
        $tx = $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 50000,
            'transaction_date' => '2026-05-10',
            'description' => 'Makan Bakso',
            'member_id' => $this->memberMe->id,
        ]);

        $this->assertDatabaseHas('financial_transactions', ['id' => $tx->id]);

        $response = $this->actingAs($this->userA)
            ->post('/profile/clean-data', [
                'space_id' => $this->spaceA->id,
                'password' => 'secret123',
                'transactions' => 1,
            ]);

        $response->assertRedirect('/profile');
        $this->assertDatabaseMissing('financial_transactions', ['id' => $tx->id]);
        $this->assertDatabaseHas('accounts', ['id' => $this->bca->id]);
        $this->assertDatabaseHas('categories', ['id' => $this->foodCategory->id]);
        $this->assertDatabaseHas('members', ['id' => $this->memberMe->id]);
    }

    public function test_user_can_clean_budgets_only(): void
    {
        $budget = Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->foodCategory->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 1500000,
        ]);

        $this->assertDatabaseHas('budgets', ['id' => $budget->id]);

        $response = $this->actingAs($this->userA)
            ->post('/profile/clean-data', [
                'space_id' => $this->spaceA->id,
                'password' => 'secret123',
                'budgets' => 1,
            ]);

        $response->assertRedirect('/profile');
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
        $this->assertDatabaseHas('categories', ['id' => $this->foodCategory->id]);
    }

    public function test_user_can_clean_recurring_schedules_only(): void
    {
        $recurring = RecurringSchedule::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'name' => 'Langganan Netflix',
            'category_id' => $this->foodCategory->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 10,
            'start_date' => '2026-01-10',
            'next_due_date' => '2026-01-10',
            'amount' => 186000,
            'status' => 'active',
            'created_by' => $this->userA->id,
        ]);

        $this->assertDatabaseHas('recurring_schedules', ['id' => $recurring->id]);

        $response = $this->actingAs($this->userA)
            ->post('/profile/clean-data', [
                'space_id' => $this->spaceA->id,
                'password' => 'secret123',
                'recurring' => 1,
            ]);

        $response->assertRedirect('/profile');
        $this->assertDatabaseMissing('recurring_schedules', ['id' => $recurring->id]);
    }

    public function test_user_can_clean_all_data_in_space(): void
    {
        $txService = app(TransactionService::class);
        $tx = $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 100000,
            'transaction_date' => '2026-05-10',
            'description' => 'Makan',
        ]);

        $budget = Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->foodCategory->id,
            'year' => 2026,
            'month' => 5,
            'limit_amount' => 1500000,
        ]);

        $response = $this->actingAs($this->userA)
            ->post('/profile/clean-data', [
                'space_id' => $this->spaceA->id,
                'password' => 'secret123',
                'all' => 1,
            ]);

        $response->assertRedirect('/profile');
        $this->assertDatabaseMissing('financial_transactions', ['id' => $tx->id]);
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
        $this->assertDatabaseMissing('accounts', ['id' => $this->bca->id]);
        $this->assertDatabaseMissing('members', ['id' => $this->memberMe->id]);
        $this->assertTrue(Category::where('space_id', $this->spaceA->id)->count() > 0);
    }

    public function test_clean_data_requires_valid_password(): void
    {
        $response = $this->actingAs($this->userA)
            ->post('/profile/clean-data', [
                'space_id' => $this->spaceA->id,
                'password' => 'wrong-password',
                'transactions' => 1,
            ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_user_cannot_clean_data_of_unowned_space(): void
    {
        $response = $this->actingAs($this->userA)
            ->post('/profile/clean-data', [
                'space_id' => $this->spaceB->id,
                'password' => 'secret123',
                'transactions' => 1,
            ]);

        $response->assertStatus(403);
    }

    public function test_cleaning_space_a_does_not_affect_space_b(): void
    {
        $accountB = Account::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Mandiri B',
            'type' => 'bank',
            'opening_balance' => 5000000,
            'opened_on' => '2026-01-01',
        ]);

        $catB = Category::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Operasional B',
            'type' => 'expense',
        ]);

        $txService = app(TransactionService::class);
        $txB = $txService->recordExpense($this->spaceB, $this->userB, [
            'account_id' => $accountB->id,
            'category_id' => $catB->id,
            'amount' => 750000,
            'transaction_date' => '2026-05-12',
            'description' => 'Operasional B',
        ]);

        $this->actingAs($this->userA)
            ->post('/profile/clean-data', [
                'space_id' => $this->spaceA->id,
                'password' => 'secret123',
                'all' => 1,
            ]);

        $this->assertDatabaseHas('financial_transactions', ['id' => $txB->id]);
        $this->assertDatabaseHas('accounts', ['id' => $accountB->id]);
    }
}
