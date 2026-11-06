<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FinancialTransaction;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

    private Account $accountA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create(['name' => 'User A', 'email' => 'usera@example.com']);
        $this->spaceA = Space::create([
            'name' => 'Ruang Personal A',
            'type' => 'personal',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceA->members()->attach($this->userA->id, ['role' => 'owner', 'status' => 'active']);

        $this->accountA = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Dompet Tunai',
            'type' => 'cash',
            'opening_balance' => 50000,
            'opened_on' => '2026-01-01',
            'is_default' => true,
        ]);

        $this->userB = User::factory()->create(['name' => 'User B', 'email' => 'userb@example.com']);
        $this->spaceB = Space::create([
            'name' => 'Ruang Personal B',
            'type' => 'personal',
            'owner_id' => $this->userB->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceB->members()->attach($this->userB->id, ['role' => 'owner', 'status' => 'active']);
    }

    public function test_user_can_adjust_account_balance_upwards(): void
    {
        $this->assertEquals(50000, $this->accountA->currentBalance());

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post("/accounts/{$this->accountA->id}/adjust", [
                'target_balance' => '80.000',
                'adjustment_date' => '2026-11-06',
                'adjustment_time' => '10:00',
                'reason' => 'Opname kas fisik lebih',
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals(80000, $this->accountA->fresh()->currentBalance());

        $this->assertDatabaseHas('financial_transactions', [
            'space_id' => $this->spaceA->id,
            'type' => 'income',
        ]);

        $this->assertDatabaseHas('transaction_entries', [
            'account_id' => $this->accountA->id,
            'amount_signed' => 30000,
        ]);
    }

    public function test_user_can_adjust_account_balance_downwards(): void
    {
        $this->assertEquals(50000, $this->accountA->currentBalance());

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post("/accounts/{$this->accountA->id}/adjust", [
                'target_balance' => 20000,
                'adjustment_date' => '2026-11-06',
                'adjustment_time' => '10:00',
                'reason' => 'Koreksi selisih kas fisik',
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals(20000, $this->accountA->fresh()->currentBalance());

        $this->assertDatabaseHas('financial_transactions', [
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
        ]);

        $this->assertDatabaseHas('transaction_entries', [
            'account_id' => $this->accountA->id,
            'amount_signed' => -30000,
        ]);
    }

    public function test_adjusting_with_same_balance_creates_no_transactions(): void
    {
        $initialTxCount = FinancialTransaction::count();

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post("/accounts/{$this->accountA->id}/adjust", [
                'target_balance' => 50000,
                'adjustment_date' => '2026-11-06',
                'adjustment_time' => '10:00',
            ]);

        $response->assertSessionHas('info');
        $this->assertEquals(50000, $this->accountA->fresh()->currentBalance());
        $this->assertEquals($initialTxCount, FinancialTransaction::count());
    }

    public function test_cannot_adjust_archived_account(): void
    {
        $this->accountA->update(['archived_at' => now()]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post("/accounts/{$this->accountA->id}/adjust", [
                'target_balance' => 100000,
                'adjustment_date' => '2026-11-06',
            ]);

        $response->assertSessionHas('error');
        $this->assertEquals(50000, $this->accountA->fresh()->currentBalance());
    }

    public function test_user_cannot_adjust_account_from_another_space(): void
    {
        $response = $this->actingAs($this->userB)
            ->withSession(['active_space_id' => $this->spaceB->id])
            ->post("/accounts/{$this->accountA->id}/adjust", [
                'target_balance' => 100000,
                'adjustment_date' => '2026-11-06',
            ]);

        $response->assertForbidden();
    }
}
