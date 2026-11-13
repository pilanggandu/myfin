<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Space;
use App\Models\User;
use App\Services\CreditService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
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

    public function test_accounts_index_renders_list_mode_by_default_with_card_view_and_switcher(): void
    {
        $bankAccount = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'BCA Operasional',
            'type' => 'bank',
            'opening_balance' => 3500000,
            'opened_on' => '2026-01-01',
            'is_default' => true,
        ]);

        $archivedAccount = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Kas Lama',
            'type' => 'cash',
            'opening_balance' => 250000,
            'opened_on' => '2025-06-01',
            'archived_at' => now(),
        ]);

        $otherSpaceAccount = Account::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Rahasia Ruang B',
            'type' => 'bank',
            'opening_balance' => 9999999,
            'opened_on' => '2026-01-01',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.index'));

        $response->assertOk();
        $response->assertViewHas('viewMode', 'list');
        $response->assertViewHas('hasExplicitView', false);
        $response->assertSee('id="accountsViewListBtn"', false);
        $response->assertSee('id="accountsViewCardBtn"', false);
        $response->assertSee('id="accountsListView" class=""', false);
        $response->assertSee('data-account-card="'.$bankAccount->id.'"', false);
        $response->assertSee('data-account-card="'.$archivedAccount->id.'"', false);
        $response->assertDontSee($otherSpaceAccount->name);
    }

    public function test_accounts_index_supports_card_mode_via_query_parameter(): void
    {
        $account = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Mandiri Tabungan',
            'type' => 'bank',
            'opening_balance' => 1250000,
            'opened_on' => '2026-02-01',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.index', ['view' => 'card']));

        $response->assertOk();
        $response->assertViewHas('viewMode', 'card');
        $response->assertViewHas('hasExplicitView', true);
        $response->assertSee('id="accountsListView" class="hidden"', false);
        $response->assertSee('id="accountsCardView"', false);
        $response->assertSee('data-account-card="'.$account->id.'"', false);
        $response->assertSee('Mandiri Tabungan');
    }

    public function test_accounts_index_falls_back_to_list_mode_for_invalid_view_parameter(): void
    {
        Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'GoPay Harian',
            'type' => 'e-wallet',
            'opening_balance' => 150000,
            'opened_on' => '2026-03-01',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.index', ['view' => 'grid-invalid']));

        $response->assertOk();
        $response->assertViewHas('viewMode', 'list');
        $response->assertViewHas('hasExplicitView', false);
        $response->assertSee('id="accountsListView" class=""', false);
    }

    public function test_accounts_index_hides_view_switcher_when_no_accounts_exist(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.index'));

        $response->assertOk();
        $response->assertSee('Belum Ada Rekening atau Wallet');
        $response->assertDontSee('id="accountsViewListBtn"', false);
        $response->assertDontSee('id="accountsViewCardBtn"', false);
    }

    public function test_account_type_label_maps_known_and_custom_types(): void
    {
        $expected = [
            'bank' => 'Bank',
            'e-wallet' => 'E-Wallet',
            'cash' => 'Kas Tunai',
            'investment' => 'Investasi / RDN',
            'credit_card' => 'Kartu Kredit',
            'paylater' => 'PayLater',
            'loan' => 'Pinjaman',
            'custom_vault' => 'custom_vault',
        ];

        foreach ($expected as $type => $label) {
            $account = new Account(['type' => $type]);
            $this->assertSame($label, $account->typeLabel());
        }
    }

    public function test_accounts_index_can_sort_by_account_type_ascending_and_descending(): void
    {
        Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'A-Loan Pinjaman',
            'type' => 'loan',
            'opening_balance' => -500000,
            'opened_on' => '2026-01-01',
        ]);
        Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Bibit Investasi',
            'type' => 'investment',
            'opening_balance' => 3000000,
            'opened_on' => '2026-01-01',
        ]);
        Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Dompet Tunai',
            'type' => 'cash',
            'opening_balance' => 250000,
            'opened_on' => '2026-01-01',
        ]);
        Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'OVO Wallet',
            'type' => 'e-wallet',
            'opening_balance' => 450000,
            'opened_on' => '2026-01-01',
        ]);
        Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Zebra Bank Mandiri',
            'type' => 'bank',
            'opening_balance' => 4000000,
            'opened_on' => '2026-01-01',
        ]);
        Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Alpha Bank BCA',
            'type' => 'bank',
            'opening_balance' => 7000000,
            'opened_on' => '2026-01-01',
        ]);

        $ascResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.index', ['sort' => 'type_asc']));

        $ascResponse->assertOk();
        $ascResponse->assertViewHas('sort', 'type_asc');
        $ascAccounts = $ascResponse->viewData('accounts');
        $this->assertSame(
            ['bank', 'bank', 'e-wallet', 'cash', 'investment', 'loan'],
            $ascAccounts->pluck('type')->all()
        );
        $this->assertSame(
            ['Alpha Bank BCA', 'Zebra Bank Mandiri'],
            $ascAccounts->where('type', 'bank')->pluck('name')->values()->all()
        );

        $descResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.index', ['sort' => 'type_desc']));

        $descResponse->assertOk();
        $descResponse->assertViewHas('sort', 'type_desc');
        $descAccounts = $descResponse->viewData('accounts');
        $this->assertSame(
            ['loan', 'investment', 'cash', 'e-wallet', 'bank', 'bank'],
            $descAccounts->pluck('type')->all()
        );
    }

    public function test_accounts_index_supports_balance_desc_name_desc_and_invalid_sort_fallback(): void
    {
        Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Alpha Kecil',
            'type' => 'cash',
            'opening_balance' => 100000,
            'opened_on' => '2026-01-01',
        ]);
        Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Beta Besar',
            'type' => 'bank',
            'opening_balance' => 9000000,
            'opened_on' => '2026-01-01',
        ]);
        Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Zeta Menengah',
            'type' => 'e-wallet',
            'opening_balance' => 1500000,
            'opened_on' => '2026-01-01',
        ]);

        $balResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.index', ['sort' => 'balance_desc']));

        $balResponse->assertOk();
        $this->assertSame(
            ['Beta Besar', 'Zeta Menengah', 'Alpha Kecil'],
            $balResponse->viewData('accounts')->pluck('name')->all()
        );

        $nameDescResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.index', ['sort' => 'name_desc']));

        $nameDescResponse->assertOk();
        $this->assertSame(
            ['Zeta Menengah', 'Beta Besar', 'Alpha Kecil'],
            $nameDescResponse->viewData('accounts')->pluck('name')->all()
        );

        $fallbackResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.index', ['sort' => 'unknown_sort']));

        $fallbackResponse->assertOk();
        $fallbackResponse->assertViewHas('sort', 'name_asc');
        $this->assertSame(
            ['Alpha Kecil', 'Beta Besar', 'Zeta Menengah'],
            $fallbackResponse->viewData('accounts')->pluck('name')->all()
        );
    }

    public function test_accounts_index_can_group_accounts_by_type_across_list_and_card_views(): void
    {
        $bca = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Bank BCA',
            'type' => 'bank',
            'opening_balance' => 2000000,
            'opened_on' => '2026-01-01',
        ]);
        Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Bank Mandiri Lama',
            'type' => 'bank',
            'opening_balance' => 500000,
            'opened_on' => '2025-01-01',
            'archived_at' => now(),
        ]);
        Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'GoPay',
            'type' => 'e-wallet',
            'opening_balance' => 300000,
            'opened_on' => '2026-01-01',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.index', ['group' => 'type', 'sort' => 'type_asc', 'view' => 'card']));

        $response->assertOk();
        $response->assertViewHas('groupByType', true);
        $response->assertViewHas('viewMode', 'card');

        $groups = $response->viewData('accountGroups');
        $this->assertSame(['bank', 'e-wallet'], $groups->pluck('type')->all());

        $bankGroup = $groups->firstWhere('type', 'bank');
        $this->assertNotNull($bankGroup);
        $this->assertSame('Bank', $bankGroup['label']);
        $this->assertSame(1, $bankGroup['active_count']);
        $this->assertSame(1, $bankGroup['archived_count']);
        $this->assertEquals(2500000.0, $bankGroup['total_amount']);
        $this->assertSame('Total Saldo', $bankGroup['summary_label']);
        // Aktif muncul sebelum arsip di dalam grup yang sama
        $this->assertSame($bca->id, $bankGroup['accounts']->first()->id);

        $response->assertSee('data-account-group="bank"', false);
        $response->assertSee('data-account-group="e-wallet"', false);
        $response->assertSee('data-account-card-group="bank"', false);
        $response->assertSee('data-account-card-group="e-wallet"', false);
        $response->assertSee('id="accountsSortSelect"', false);
        $response->assertSee('id="accountsGroupToggleBtn"', false);

        // Reverse group order saat sort=type_desc
        $reverseResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.index', ['group' => 'type', 'sort' => 'type_desc']));

        $reverseResponse->assertOk();
        $this->assertSame(
            ['e-wallet', 'bank'],
            $reverseResponse->viewData('accountGroups')->pluck('type')->all()
        );
    }

    public function test_dark_metric_card_renders_high_contrast_classes_and_css_rule_in_light_mode(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('accounts.index'));

        $response->assertOk();
        $response->assertSee('stat-folder-card dark', false);
        $response->assertSee('TOTAL SALDO TERSEDIA');
        $response->assertSee('border border-white/25 text-white hover:bg-white/15 bg-white/10', false);

        $renderedWithProgress = Blade::render(
            '<x-metric-card label="Target" value="Rp 100" :dark="true" :progress="50" :toggle="true" />'
        );

        $this->assertStringContainsString('stat-folder-card dark', $renderedWithProgress);
        $this->assertStringContainsString('text-[11px] font-semibold text-emerald-100', $renderedWithProgress);
        $this->assertStringContainsString('text-xs font-bold text-white font-mono', $renderedWithProgress);
        $this->assertStringNotContainsString('text-forest dark:text-blue-400', $renderedWithProgress);

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertNotFalse($css);
        $this->assertMatchesRegularExpression('/\.stat-folder-card\.dark\s*\{[^}]*background:\s*#0B4A8B/s', $css);
        $this->assertMatchesRegularExpression('/\.dark\s+\.stat-folder-card\.dark\s*\{[^}]*background:\s*#1E334A/s', $css);
    }
}
