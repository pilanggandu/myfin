<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Space;
use App\Models\User;
use App\Services\CreditService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

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
    }

    public function test_user_can_create_account(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/accounts', [
                'name' => 'Bank Mandiri',
                'type' => 'bank',
                'opening_balance' => 2500000,
                'opened_on' => '2026-01-01',
            ]);

        $response->assertRedirect('/accounts');
        $this->assertDatabaseHas('accounts', [
            'space_id' => $this->spaceA->id,
            'name' => 'Bank Mandiri',
            'type' => 'bank',
            'opening_balance' => 2500000,
        ]);
    }

    public function test_user_cannot_view_accounts_from_another_space(): void
    {
        $accountB = Account::create([
            'space_id' => $this->spaceB->id,
            'name' => 'BCA Secret B',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get("/accounts/{$accountB->id}");

        $response->assertStatus(403);
    }

    public function test_user_cannot_edit_account_from_another_space(): void
    {
        $accountB = Account::create([
            'space_id' => $this->spaceB->id,
            'name' => 'BCA Secret B',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put("/accounts/{$accountB->id}", [
                'name' => 'Hacked Name',
                'type' => 'bank',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_archive_and_unarchive_account(): void
    {
        $account = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Old Wallet',
            'type' => 'cash',
            'opening_balance' => 100000,
            'opened_on' => '2026-01-01',
        ]);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post("/accounts/{$account->id}/archive")
            ->assertRedirect();

        $this->assertTrue($account->fresh()->isArchived());

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post("/accounts/{$account->id}/unarchive")
            ->assertRedirect();

        $this->assertFalse($account->fresh()->isArchived());
    }

    public function test_account_balance_reflects_opening_balance_and_transactions(): void
    {
        $account = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Bank BCA',
            'type' => 'bank',
            'opening_balance' => 1000000,
            'opened_on' => '2026-01-01',
        ]);

        $category = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Gaji',
            'type' => 'income',
        ]);

        $service = app(TransactionService::class);
        $service->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 500000,
            'transaction_date' => '2026-01-10',
            'description' => 'Bonus',
        ]);

        $this->assertEquals(1500000, $account->currentBalance());
    }

    public function test_user_can_set_account_as_default(): void
    {
        $account1 = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'BCA',
            'type' => 'bank',
            'opening_balance' => 1000000,
            'opened_on' => '2026-01-01',
        ]);

        $account2 = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Mandiri',
            'type' => 'bank',
            'opening_balance' => 500000,
            'opened_on' => '2026-01-01',
        ]);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post("/accounts/{$account1->id}/set-default")
            ->assertRedirect();

        $this->assertTrue($account1->fresh()->isDefault());
        $this->assertFalse($account2->fresh()->isDefault());

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post("/accounts/{$account2->id}/set-default")
            ->assertRedirect();

        $this->assertFalse($account1->fresh()->isDefault());
        $this->assertTrue($account2->fresh()->isDefault());
    }

    public function test_archived_account_cannot_be_set_as_default(): void
    {
        $account = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Arsip Bank',
            'type' => 'bank',
            'opening_balance' => 1000000,
            'opened_on' => '2026-01-01',
            'archived_at' => now(),
        ]);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post("/accounts/{$account->id}/set-default")
            ->assertRedirect();

        $this->assertFalse($account->fresh()->isDefault());
    }

    public function test_default_account_is_preselected_on_transaction_create(): void
    {
        $account = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'BCA Utama',
            'type' => 'bank',
            'opening_balance' => 1000000,
            'opened_on' => '2026-01-01',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.create'));

        $response->assertStatus(200);
        $response->assertSee('value="'.$account->id.'"', false);
        $response->assertSee('(Default)', false);
    }

    public function test_account_create_screen_renders_successfully(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.create'));

        $response->assertStatus(200);
        $response->assertSee('Tambah Rekening atau Dompet');
        $response->assertSee('Bank BCA');
        $response->assertSee('Saldo Awal Pembukuan');
        $response->assertSee('Jadikan Sebagai Rekening Default');
    }

    public function test_account_edit_screen_renders_successfully(): void
    {
        $account = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'BCA Tabungan',
            'type' => 'bank',
            'opening_balance' => 5000000,
            'opened_on' => '2026-01-01',
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.edit', $account));

        $response->assertStatus(200);
        $response->assertSee('Ubah Rekening: BCA Tabungan');
        $response->assertSee('5.000.000');
        $response->assertSee('Simpan Perubahan Rekening');
    }

    public function test_account_update_can_set_is_default(): void
    {
        $account = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'BCA Tabungan',
            'type' => 'bank',
            'opening_balance' => 5000000,
            'opened_on' => '2026-01-01',
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put(route('accounts.update', $account), [
                'name' => 'BCA Utama Revisi',
                'type' => 'bank',
                'opening_balance' => 6000000,
                'opened_on' => '2026-01-01',
                'is_default' => true,
            ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertTrue($account->fresh()->isDefault());
        $this->assertEquals(6000000, $account->fresh()->opening_balance);
    }

    public function test_account_opening_balance_change_creates_audit_log(): void
    {
        $account = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'BCA Operasional',
            'type' => 'bank',
            'opening_balance' => 5000000,
            'opened_on' => '2026-01-01',
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put(route('accounts.update', $account), [
                'name' => 'BCA Operasional',
                'type' => 'bank',
                'opening_balance' => 7500000,
                'opened_on' => '2026-01-01',
                'reason' => 'Penyesuaian saldo awal rekonsiliasi bank',
            ]);

        $response->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('account_opening_balance_logs', [
            'account_id' => $account->id,
            'actor_id' => $this->userA->id,
            'old_opening_balance' => 5000000,
            'new_opening_balance' => 7500000,
            'reason' => 'Penyesuaian saldo awal rekonsiliasi bank',
        ]);

        $this->assertEquals(1, $account->openingBalanceLogs()->count());

        $showResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.show', $account));

        $showResponse->assertStatus(200);
        $showResponse->assertSee('Jejak Audit Perubahan Saldo Awal');
        $showResponse->assertSee('7.500.000');
        $showResponse->assertSee('Penyesuaian saldo awal rekonsiliasi bank');
    }

    public function test_user_can_create_and_update_investment_account(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/accounts', [
                'name' => 'Bibit Reksadana & SBN',
                'type' => 'investment',
                'opening_balance' => 5000000,
                'opened_on' => '2026-01-01',
            ]);

        $response->assertRedirect('/accounts');
        $this->assertDatabaseHas('accounts', [
            'space_id' => $this->spaceA->id,
            'name' => 'Bibit Reksadana & SBN',
            'type' => 'investment',
            'opening_balance' => 5000000,
        ]);

        $account = Account::where('name', 'Bibit Reksadana & SBN')->first();
        $this->assertNotNull($account);
        $this->assertEquals(5000000, $this->spaceA->fresh()->investmentBalance());
        $this->assertEquals(5000000, $this->spaceA->fresh()->totalBalance());

        $indexResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/accounts');

        $indexResponse->assertOk();
        $indexResponse->assertSee('INVESTASI &amp; RDN', false);
        $indexResponse->assertSee('Bibit Reksadana &amp; SBN', false);

        // Update
        $updateResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put("/accounts/{$account->id}", [
                'name' => 'Bibit & SBN Portofolio',
                'type' => 'investment',
                'opening_balance' => 6000000,
                'opened_on' => '2026-01-01',
                'reason' => 'Koreksi saldo awal aset',
            ]);

        $updateResponse->assertRedirect('/accounts');
        $this->assertSame('Bibit & SBN Portofolio', $account->fresh()->name);
        $this->assertEquals(6000000, $this->spaceA->fresh()->investmentBalance());
    }

    public function test_accounts_index_and_show_renders_available_limit_for_credit_card_and_kewajiban_for_loans(): void
    {
        $creditCard = app(CreditService::class)->createCreditAccount($this->spaceA, [
            'name' => 'BCA Blibli Tiket',
            'type' => 'credit_card',
            'credit_limit' => 10000000,
            'billing_cycle_day' => 20,
            'due_day' => 10,
        ]);

        $loanAccount = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Pinjaman Bank Anam',
            'type' => 'loan',
            'opening_balance' => -200000,
            'opened_on' => '2026-01-01',
        ]);

        $this->assertTrue($creditCard->ledgerAccount->isCreditCard());
        $this->assertEquals(10000000, $creditCard->ledgerAccount->availableLimit());
        $this->assertEquals(0, $creditCard->ledgerAccount->liabilityAmount());

        $this->assertTrue($loanAccount->isLoan());
        $this->assertEquals(200000, $loanAccount->liabilityAmount());

        // Test accounts.index
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/accounts');

        $response->assertOk();
        $response->assertSee('Sisa Limit', false);
        $response->assertSee('Kewajiban', false);
        $response->assertSee('10.000.000', false);
        $response->assertSee('200.000', false);

        // Test accounts.show untuk credit card
        $ccShowResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.show', $creditCard->ledgerAccount));

        $ccShowResponse->assertOk();
        $ccShowResponse->assertSee('SISA LIMIT TERSEDIA', false);

        // Test accounts.show untuk loan
        $loanShowResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.show', $loanAccount));

        $loanShowResponse->assertOk();
        $loanShowResponse->assertSee('KEWAJIBAN / SISA HUTANG', false);
    }
}
