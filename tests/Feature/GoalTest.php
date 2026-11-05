<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalContribution;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

    private Account $bca;

    private Category $savingsCategory;

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

        $this->savingsCategory = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Tabungan & Investasi',
            'type' => 'expense',
            'color' => '#174C40',
            'icon' => '🎯',
        ]);
    }

    public function test_user_can_view_goals_index(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['current_space_id' => $this->spaceA->id])
            ->get(route('goals.index'));

        $response->assertOk();
        $response->assertSee('Target Tabungan & Bersama');
        $response->assertSee('Belum Ada Target Tabungan');
    }

    public function test_user_can_create_goal_without_initial_deposit(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['current_space_id' => $this->spaceA->id])
            ->post(route('goals.store'), [
                'name' => 'Liburan ke Jepang',
                'target_amount' => '25.000.000',
                'start_date' => '2026-11-01',
                'target_date' => '2027-11-01',
                'icon' => '✈️',
                'color' => '#0284c7',
                'notes' => 'Tiket dan hotel selama 10 hari',
            ]);

        $goal = Goal::first();
        $this->assertNotNull($goal);
        $this->assertSame('Liburan ke Jepang', $goal->name);
        $this->assertEquals(25000000, (float) $goal->target_amount);
        $this->assertEquals(0, (float) $goal->current_amount);
        $this->assertSame('active', $goal->status);

        $response->assertRedirect(route('goals.show', $goal));
    }

    public function test_user_can_create_goal_with_initial_deposit_and_cash_deduction(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['current_space_id' => $this->spaceA->id])
            ->post(route('goals.store'), [
                'name' => 'Dana Darurat 6 Bulan',
                'target_amount' => '30.000.000',
                'start_date' => '2026-11-01',
                'initial_deposit' => '5.000.000',
                'account_id' => $this->bca->id,
                'category_id' => $this->savingsCategory->id,
                'deduct_from_account' => '1',
            ]);

        $goal = Goal::first();
        $this->assertNotNull($goal);
        $this->assertEquals(5000000, (float) $goal->current_amount);
        $this->assertEquals(16.7, $goal->percentage());

        // Check contribution record
        $contrib = GoalContribution::where('goal_id', $goal->id)->first();
        $this->assertNotNull($contrib);
        $this->assertEquals(5000000, (float) $contrib->amount);
        $this->assertSame('deposit', $contrib->type);
        $this->assertNotNull($contrib->financial_transaction_id);

        // Check account balance deducted (10.000.000 - 5.000.000 = 5.000.000)
        $this->assertEquals(5000000, $this->bca->fresh()->currentBalance());

        $response->assertRedirect(route('goals.show', $goal));
    }

    public function test_user_can_contribute_to_goal_and_auto_achieve_when_target_met(): void
    {
        $goal = Goal::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Beli Laptop Baru',
            'target_amount' => 10000000,
            'current_amount' => 8000000,
            'start_date' => '2026-11-01',
            'status' => 'active',
            'created_by' => $this->userA->id,
            'updated_by' => $this->userA->id,
        ]);

        GoalContribution::create([
            'space_id' => $this->spaceA->id,
            'goal_id' => $goal->id,
            'user_id' => $this->userA->id,
            'type' => 'deposit',
            'amount' => 8000000,
            'contribution_date' => '2026-11-01',
            'created_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['current_space_id' => $this->spaceA->id])
            ->post(route('goals.contribute', $goal), [
                'amount' => '2.000.000',
                'contribution_date' => '2026-11-05',
                'notes' => 'Bonus proyek',
            ]);

        $response->assertRedirect(route('goals.show', $goal));

        $goal->refresh();
        $this->assertEquals(10000000, (float) $goal->current_amount);
        $this->assertSame('achieved', $goal->status);
        $this->assertEquals(100, $goal->percentage());
    }

    public function test_user_can_withdraw_from_goal(): void
    {
        $goal = Goal::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Tabungan Kurban',
            'target_amount' => 5000000,
            'current_amount' => 3000000,
            'start_date' => '2026-11-01',
            'status' => 'active',
            'created_by' => $this->userA->id,
            'updated_by' => $this->userA->id,
        ]);

        GoalContribution::create([
            'space_id' => $this->spaceA->id,
            'goal_id' => $goal->id,
            'user_id' => $this->userA->id,
            'type' => 'deposit',
            'amount' => 3000000,
            'contribution_date' => '2026-11-01',
            'created_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['current_space_id' => $this->spaceA->id])
            ->post(route('goals.withdraw', $goal), [
                'amount' => '1.000.000',
                'withdrawal_date' => '2026-11-05',
                'notes' => 'Penarikan darurat',
            ]);

        $response->assertRedirect(route('goals.show', $goal));

        $goal->refresh();
        $this->assertEquals(2000000, (float) $goal->current_amount);
    }

    public function test_user_cannot_withdraw_more_than_current_amount(): void
    {
        $goal = Goal::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Tabungan Kurban',
            'target_amount' => 5000000,
            'current_amount' => 1000000,
            'start_date' => '2026-11-01',
            'status' => 'active',
            'created_by' => $this->userA->id,
            'updated_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['current_space_id' => $this->spaceA->id])
            ->post(route('goals.withdraw', $goal), [
                'amount' => '2.000.000',
                'withdrawal_date' => '2026-11-05',
            ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(1000000, (float) $goal->fresh()->current_amount);
    }

    public function test_user_can_toggle_goal_status(): void
    {
        $goal = Goal::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Renovasi Rumah',
            'target_amount' => 50000000,
            'start_date' => '2026-11-01',
            'status' => 'active',
            'created_by' => $this->userA->id,
            'updated_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['current_space_id' => $this->spaceA->id])
            ->post(route('goals.toggle-status', $goal), [
                'status' => 'paused',
            ]);

        $response->assertRedirect(route('goals.show', $goal));
        $this->assertSame('paused', $goal->fresh()->status);
    }

    public function test_user_can_update_and_delete_goal(): void
    {
        $goal = Goal::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Beli Motor',
            'target_amount' => 20000000,
            'start_date' => '2026-11-01',
            'status' => 'active',
            'created_by' => $this->userA->id,
            'updated_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['current_space_id' => $this->spaceA->id])
            ->put(route('goals.update', $goal), [
                'name' => 'Beli Motor Listrik',
                'target_amount' => '22.000.000',
                'start_date' => '2026-11-01',
                'status' => 'active',
                'icon' => '🏍️',
                'color' => '#174C40',
            ]);

        $response->assertRedirect(route('goals.show', $goal));
        $this->assertSame('Beli Motor Listrik', $goal->fresh()->name);
        $this->assertEquals(22000000, (float) $goal->fresh()->target_amount);

        // Delete
        $deleteResponse = $this->actingAs($this->userA)
            ->withSession(['current_space_id' => $this->spaceA->id])
            ->delete(route('goals.destroy', $goal));

        $deleteResponse->assertRedirect(route('goals.index'));
        $this->assertDatabaseMissing('goals', ['id' => $goal->id]);
    }

    public function test_space_isolation_prevents_unauthorized_access(): void
    {
        $goalA = Goal::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Target Space A',
            'target_amount' => 10000000,
            'start_date' => '2026-11-01',
            'status' => 'active',
            'created_by' => $this->userA->id,
            'updated_by' => $this->userA->id,
        ]);

        // User B tries to view Goal A
        $response = $this->actingAs($this->userB)
            ->withSession(['current_space_id' => $this->spaceB->id])
            ->get(route('goals.show', $goalA));

        $response->assertForbidden();

        // User B tries to contribute to Goal A
        $contribResponse = $this->actingAs($this->userB)
            ->withSession(['current_space_id' => $this->spaceB->id])
            ->post(route('goals.contribute', $goalA), [
                'amount' => '500.000',
                'contribution_date' => '2026-11-05',
            ]);

        $contribResponse->assertForbidden();

        // User B tries to delete Goal A
        $deleteResponse = $this->actingAs($this->userB)
            ->withSession(['current_space_id' => $this->spaceB->id])
            ->delete(route('goals.destroy', $goalA));

        $deleteResponse->assertForbidden();
    }
}
