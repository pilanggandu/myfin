<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\FinancialTransaction;
use App\Models\InstallmentPlan;
use App\Models\Member;
use App\Models\RecurringOccurrence;
use App\Models\RecurringSchedule;
use App\Models\Space;
use App\Models\TransactionRevision;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkTransactionCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

    private Account $bca;

    private Account $gopay;

    private Account $mandiri;

    private Account $accountB;

    private Category $incomeCat;

    private Category $incomeCatAlt;

    private Category $expenseCat;

    private Category $expenseCatAlt;

    private Category $expenseCatB;

    private Member $memberAli;

    private Member $memberBudi;

    private TransactionService $service;

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

        $this->gopay = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'GoPay',
            'type' => 'ewallet',
            'opening_balance' => 1000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->mandiri = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Bank Mandiri',
            'type' => 'bank',
            'opening_balance' => 5000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->accountB = Account::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Rekening B',
            'type' => 'cash',
            'opening_balance' => 500000,
            'opened_on' => '2026-01-01',
        ]);

        $this->incomeCat = Category::create(['space_id' => $this->spaceA->id, 'name' => 'Gaji', 'type' => 'income']);
        $this->incomeCatAlt = Category::create(['space_id' => $this->spaceA->id, 'name' => 'Bonus', 'type' => 'income']);
        $this->expenseCat = Category::create(['space_id' => $this->spaceA->id, 'name' => 'Makanan', 'type' => 'expense']);
        $this->expenseCatAlt = Category::create(['space_id' => $this->spaceA->id, 'name' => 'Transportasi', 'type' => 'expense']);
        $this->expenseCatB = Category::create(['space_id' => $this->spaceB->id, 'name' => 'Belanja B', 'type' => 'expense']);

        $this->memberAli = Member::create(['space_id' => $this->spaceA->id, 'name' => 'Ali', 'color' => '#0B4A8B']);
        $this->memberBudi = Member::create(['space_id' => $this->spaceA->id, 'name' => 'Budi', 'color' => '#15803D']);

        $this->service = app(TransactionService::class);
    }

    public function test_transactions_index_renders_bulk_controls_and_only_active_categories_in_bulk_modal(): void
    {
        $archivedCat = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Kategori Arsip Rahasia',
            'type' => 'expense',
        ]);
        $archivedCat->archive();

        $this->service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 50000,
            'transaction_date' => '2026-01-10',
            'description' => 'Sarapan Pagi',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('bulkActionBar', false);
        $response->assertSee('bulkCorrectModal', false);
        $response->assertSee('data-bulk-select-row', false);
        $response->assertSee('data-bulk-select-all', false);
        $response->assertSee(route('transactions.bulk-correct'), false);

        $html = $response->getContent();
        $modalStart = strpos($html, 'id="bulkCorrectModal"');
        $this->assertNotFalse($modalStart);
        $modalHtml = substr($html, $modalStart);

        $this->assertStringContainsString($this->expenseCat->name, $modalHtml);
        $this->assertStringContainsString($this->incomeCat->name, $modalHtml);
        $this->assertStringNotContainsString($archivedCat->name, $modalHtml);
        $this->assertStringNotContainsString('fetch(form.action', $html);
        $this->assertStringContainsString("getAttribute('action')", $html);
    }

    public function test_bulk_correct_updates_selected_fields_preserves_untouched_fields_and_writes_revisions(): void
    {
        $tx1 = $this->service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'member_id' => $this->memberAli->id,
            'amount' => 50000,
            'transaction_date' => '2026-01-02',
            'transaction_time' => '08:30:00',
            'description' => 'Kopi Pagi',
        ]);

        $tx2 = $this->service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->gopay->id,
            'category_id' => $this->expenseCat->id,
            'member_id' => $this->memberBudi->id,
            'amount' => 85000,
            'transaction_date' => '2026-01-05',
            'transaction_time' => '12:15:00',
            'description' => 'Makan Siang',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$tx1->id, $tx2->id],
                'versions' => [
                    $tx1->id => $tx1->version,
                    $tx2->id => $tx2->version,
                ],
                'apply_category' => true,
                'category_id' => $this->expenseCatAlt->id,
                'apply_member' => true,
                'member_id' => null,
                'apply_transaction_date' => true,
                'transaction_date' => '2026-01-12',
                'reason' => 'Rapikan kategori dan tanggal',
            ]);

        $response->assertOk()
            ->assertJson([
                'updated_count' => 2,
                'action' => 'update',
            ]);

        $tx1->refresh()->load('entries');
        $tx2->refresh()->load('entries');

        $this->assertSame($this->expenseCatAlt->id, $tx1->category_id);
        $this->assertNull($tx1->member_id);
        $this->assertSame('2026-01-12', $tx1->transaction_date->toDateString());
        $this->assertSame('08:30', $tx1->formattedTime());
        $this->assertSame('Kopi Pagi', $tx1->description);
        $this->assertEquals(50000.0, $tx1->totalAmount());
        $this->assertSame($this->bca->id, $tx1->entries->first()->account_id);
        $this->assertSame(2, $tx1->version);

        $this->assertSame($this->expenseCatAlt->id, $tx2->category_id);
        $this->assertNull($tx2->member_id);
        $this->assertSame('2026-01-12', $tx2->transaction_date->toDateString());
        $this->assertSame('12:15', $tx2->formattedTime());
        $this->assertSame('Makan Siang', $tx2->description);
        $this->assertEquals(85000.0, $tx2->totalAmount());
        $this->assertSame($this->gopay->id, $tx2->entries->first()->account_id);
        $this->assertSame(2, $tx2->version);

        $rev1 = TransactionRevision::where('transaction_id', $tx1->id)->latest('id')->first();
        $rev2 = TransactionRevision::where('transaction_id', $tx2->id)->latest('id')->first();
        $this->assertNotNull($rev1);
        $this->assertNotNull($rev2);
        $this->assertSame('Rapikan kategori dan tanggal', $rev1->reason);
        $this->assertSame($this->expenseCat->id, $rev1->before_data['category_id']);
        $this->assertSame($this->expenseCatAlt->id, $rev1->after_data['category_id']);
    }

    public function test_bulk_correct_updates_amount_account_time_and_description_even_if_untouched_category_is_archived(): void
    {
        $oldCategory = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Kategori Lama',
            'type' => 'expense',
        ]);

        $tx1 = $this->service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $oldCategory->id,
            'amount' => 100000,
            'transaction_date' => '2026-01-03',
            'transaction_time' => '09:00:00',
            'description' => 'Item 1',
        ]);
        $tx2 = $this->service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 200000,
            'transaction_date' => '2026-01-04',
            'transaction_time' => '10:00:00',
            'description' => 'Item 2',
        ]);

        $oldCategory->archive();

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$tx1->id, $tx2->id],
                'versions' => [
                    $tx1->id => $tx1->version,
                    $tx2->id => $tx2->version,
                ],
                'apply_account' => true,
                'account_id' => $this->gopay->id,
                'apply_amount' => true,
                'amount' => '75.000',
                'apply_transaction_time' => true,
                'transaction_time' => null,
                'apply_description' => true,
                'description' => 'Koreksi Pengeluaran Seragam',
            ]);

        $response->assertOk();

        $tx1->refresh()->load('entries');
        $tx2->refresh()->load('entries');

        $this->assertSame($oldCategory->id, $tx1->category_id);
        $this->assertSame($this->gopay->id, $tx1->entries->first()->account_id);
        $this->assertEquals(-75000.0, (float) $tx1->entries->first()->amount_signed);
        $this->assertNull($tx1->formattedTime());
        $this->assertSame('Koreksi Pengeluaran Seragam', $tx1->description);

        $this->assertSame($this->gopay->id, $tx2->entries->first()->account_id);
        $this->assertEquals(-75000.0, (float) $tx2->entries->first()->amount_signed);

        // Saldo BCA kembali ke 10.000.000, saldo GoPay berkurang 150.000 menjadi 850.000
        $this->assertEquals(10000000.0, $this->bca->fresh()->currentBalance());
        $this->assertEquals(850000.0, $this->gopay->fresh()->currentBalance());
    }

    public function test_bulk_correct_updates_uniform_transfer_accounts_and_fee(): void
    {
        $trf1 = $this->service->recordTransfer($this->spaceA, $this->userA, [
            'source_account_id' => $this->bca->id,
            'destination_account_id' => $this->gopay->id,
            'amount' => 300000,
            'fee_amount' => 2500,
            'transaction_date' => '2026-01-06',
            'description' => 'Topup 1',
        ]);

        $trf2 = $this->service->recordTransfer($this->spaceA, $this->userA, [
            'source_account_id' => $this->gopay->id,
            'destination_account_id' => $this->bca->id,
            'amount' => 150000,
            'fee_amount' => 0,
            'transaction_date' => '2026-01-07',
            'description' => 'Transfer Balik',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$trf1->id, $trf2->id],
                'apply_destination_account' => true,
                'destination_account_id' => $this->mandiri->id,
                'apply_fee_amount' => true,
                'fee_amount' => '6.500',
                'apply_fee_category' => true,
                'fee_category_id' => $this->expenseCatAlt->id,
            ]);

        $response->assertOk()->assertJson(['updated_count' => 2]);

        $trf1->refresh()->load('entries');
        $trf2->refresh()->load('entries');

        $this->assertSame($this->bca->id, $trf1->sourceAccount()->id);
        $this->assertSame($this->mandiri->id, $trf1->destinationAccount()->id);
        $this->assertEquals(6500.0, (float) $trf1->fee_amount);
        $this->assertSame($this->expenseCatAlt->id, $trf1->fee_category_id);
        $this->assertEquals(-306500.0, (float) $trf1->entries->firstWhere('account_id', $this->bca->id)->amount_signed);
        $this->assertEquals(300000.0, (float) $trf1->entries->firstWhere('account_id', $this->mandiri->id)->amount_signed);

        $this->assertSame($this->gopay->id, $trf2->sourceAccount()->id);
        $this->assertSame($this->mandiri->id, $trf2->destinationAccount()->id);
        $this->assertEquals(-156500.0, (float) $trf2->entries->firstWhere('account_id', $this->gopay->id)->amount_signed);
        $this->assertEquals(150000.0, (float) $trf2->entries->firstWhere('account_id', $this->mandiri->id)->amount_signed);
    }

    public function test_bulk_correct_handles_mixed_types_safely_for_shared_fields_and_blocks_incompatible_fields(): void
    {
        $expense = $this->service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 40000,
            'transaction_date' => '2026-01-01',
            'description' => 'Expense Awal',
        ]);

        $income = $this->service->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->incomeCat->id,
            'amount' => 500000,
            'transaction_date' => '2026-01-02',
            'description' => 'Income Awal',
        ]);

        $transfer = $this->service->recordTransfer($this->spaceA, $this->userA, [
            'source_account_id' => $this->bca->id,
            'destination_account_id' => $this->gopay->id,
            'amount' => 100000,
            'transaction_date' => '2026-01-03',
            'description' => 'Transfer Awal',
        ]);

        // 1. Kategori harus ditolak jika campuran
        $catResp = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$expense->id, $income->id],
                'apply_category' => true,
                'category_id' => $this->expenseCatAlt->id,
            ]);
        $catResp->assertStatus(422)->assertJsonValidationErrors('category_id');

        // 2. Rekening tunggal harus ditolak jika mengandung transfer
        $accResp = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$expense->id, $transfer->id],
                'apply_account' => true,
                'account_id' => $this->mandiri->id,
            ]);
        $accResp->assertStatus(422)->assertJsonValidationErrors('account_id');

        // 3. Field bersama (tanggal & member) berhasil di seluruh jenis campuran
        $sharedResp = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$expense->id, $income->id, $transfer->id],
                'apply_transaction_date' => true,
                'transaction_date' => '2026-01-20',
                'apply_member' => true,
                'member_id' => $this->memberAli->id,
            ]);
        $sharedResp->assertOk()->assertJson(['updated_count' => 3]);

        $this->assertSame('2026-01-20', $expense->fresh()->transaction_date->toDateString());
        $this->assertSame($this->memberAli->id, $income->fresh()->member_id);
        $this->assertSame('2026-01-20', $transfer->fresh()->transaction_date->toDateString());
    }

    public function test_bulk_correct_rolls_back_atomically_on_optimistic_lock_conflict(): void
    {
        $tx1 = $this->service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 30000,
            'transaction_date' => '2026-01-05',
            'description' => 'Awal 1',
        ]);

        $tx2 = $this->service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 45000,
            'transaction_date' => '2026-01-05',
            'description' => 'Awal 2',
        ]);

        $staleVersion2 = $tx2->version;

        // Simulasikan perubahan bersamaan pada tx2
        $this->service->correctTransaction($tx2, $this->userA, [
            'type' => 'expense',
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 45000,
            'transaction_date' => '2026-01-05',
            'description' => 'Diubah User Lain',
            'version' => $staleVersion2,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$tx1->id, $tx2->id],
                'versions' => [
                    $tx1->id => $tx1->version,
                    $tx2->id => $staleVersion2,
                ],
                'apply_description' => true,
                'description' => 'Timpa Massal',
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('version');

        // tx1 tidak boleh ikut berubah sama sekali (atomik rollback)
        $tx1->refresh();
        $this->assertSame('Awal 1', $tx1->description);
        $this->assertSame(1, $tx1->version);
    }

    public function test_bulk_correct_blocks_voided_credit_system_and_installment_structural_edits(): void
    {
        $voidedTx = $this->service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 25000,
            'transaction_date' => '2026-01-04',
            'description' => 'Sudah Void',
        ]);
        $this->service->voidTransaction($voidedTx, $this->userA);

        $creditSystemTx = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'credit_payment',
            'status' => 'posted',
            'transaction_date' => '2026-01-05',
            'description' => 'Bayar Kartu Kredit',
            'created_by' => $this->userA->id,
            'version' => 1,
        ]);

        $creditAcc = CreditAccount::create([
            'space_id' => $this->spaceA->id,
            'account_id' => $this->bca->id,
            'name' => 'Kartu BCA',
            'type' => 'credit_card',
            'credit_limit' => 10000000,
            'billing_cycle_day' => 20,
            'due_day' => 5,
        ]);

        $installmentTx = $this->service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 1200000,
            'transaction_date' => '2026-01-06',
            'description' => 'Beli Elektronik Cicilan',
        ]);

        InstallmentPlan::create([
            'space_id' => $this->spaceA->id,
            'credit_account_id' => $creditAcc->id,
            'financial_transaction_id' => $installmentTx->id,
            'category_id' => $this->expenseCat->id,
            'name' => 'Cicilan Laptop',
            'total_amount' => 1200000,
            'tenor' => 6,
            'interest_rate' => 0,
            'admin_fee' => 0,
            'start_date' => '2026-01-06',
            'status' => 'active',
        ]);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$voidedTx->id],
                'apply_description' => true,
                'description' => 'Tidak Boleh',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$creditSystemTx->id],
                'apply_description' => true,
                'description' => 'Tidak Boleh',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('transaction_ids');

        // Cicilan: nominal tidak boleh diubah
        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$installmentTx->id],
                'apply_amount' => true,
                'amount' => 900000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('transaction_ids');

        // Cicilan: kategori dan keterangan tetap boleh dikoreksi
        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$installmentTx->id],
                'apply_category' => true,
                'category_id' => $this->expenseCatAlt->id,
                'apply_description' => true,
                'description' => 'Cicilan Gadget',
            ])
            ->assertOk();

        $this->assertSame($this->expenseCatAlt->id, $installmentTx->fresh()->category_id);
    }

    public function test_bulk_void_voids_transactions_and_syncs_recurring_occurrences(): void
    {
        $tx1 = $this->service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 150000,
            'transaction_date' => '2026-01-10',
            'description' => 'Tagihan Internet',
        ]);

        $tx2 = $this->service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->gopay->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 50000,
            'transaction_date' => '2026-01-11',
            'description' => 'Parkir Bulanan',
        ]);

        $schedule = RecurringSchedule::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'name' => 'Internet',
            'category_id' => $this->expenseCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 10,
            'start_date' => '2026-01-10',
            'next_due_date' => '2026-02-10',
            'amount' => 150000,
            'status' => 'active',
            'created_by' => $this->userA->id,
        ]);

        $occurrence = RecurringOccurrence::create([
            'recurring_schedule_id' => $schedule->id,
            'space_id' => $this->spaceA->id,
            'due_date' => '2026-01-10',
            'transaction_id' => $tx1->id,
            'status' => 'confirmed',
            'amount' => 150000,
            'confirmed_at' => now(),
            'confirmed_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'void',
                'transaction_ids' => [$tx1->id, $tx2->id],
                'versions' => [
                    $tx1->id => $tx1->version,
                    $tx2->id => $tx2->version,
                ],
                'reason' => 'Input duplikat bulan ini',
            ]);

        $response->assertOk()->assertJson([
            'updated_count' => 2,
            'action' => 'void',
        ]);

        $this->assertTrue($tx1->fresh()->isVoid());
        $this->assertTrue($tx2->fresh()->isVoid());
        $this->assertEquals(10000000.0, $this->bca->fresh()->currentBalance());
        $this->assertEquals(1000000.0, $this->gopay->fresh()->currentBalance());

        $occurrence->refresh();
        $this->assertSame('pending', $occurrence->status);
        $this->assertNull($occurrence->transaction_id);

        $rev = TransactionRevision::where('transaction_id', $tx1->id)->latest('id')->first();
        $this->assertSame('Input duplikat bulan ini', $rev->reason);
        $this->assertSame('void', $rev->after_data['status']);
    }

    public function test_bulk_correct_enforces_validation_active_entities_space_isolation_and_redirect_flow(): void
    {
        $txA = $this->service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 20000,
            'transaction_date' => '2026-01-08',
            'description' => 'Transaksi A',
        ]);

        $txB = $this->service->recordExpense($this->spaceB, $this->userB, [
            'account_id' => $this->accountB->id,
            'category_id' => $this->expenseCatB->id,
            'amount' => 30000,
            'transaction_date' => '2026-01-08',
            'description' => 'Transaksi B',
        ]);

        // Unauthenticated
        $this->postJson(route('transactions.bulk-correct'), [
            'action' => 'update',
            'transaction_ids' => [$txA->id],
            'apply_description' => true,
            'description' => 'Anon',
        ])->assertStatus(401);

        // Empty apply_* flags in update mode
        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$txA->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('fields');

        // Cross-space transaction ID cannot be corrected
        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$txA->id, $txB->id],
                'apply_description' => true,
                'description' => 'Cross Tenant',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('transaction_ids');

        $this->assertSame('Transaksi B', $txB->fresh()->description);

        // Archived target category / account / member rejected
        $archivedCat = Category::create(['space_id' => $this->spaceA->id, 'name' => 'Cat Arsip', 'type' => 'expense']);
        $archivedCat->archive();
        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$txA->id],
                'apply_category' => true,
                'category_id' => $archivedCat->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('category_id');

        // Standard HTML POST redirect keeps flash message
        $this->actingAs($this->userA)
            ->from(route('transactions.index'))
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => [$txA->id],
                'apply_description' => '1',
                'description' => 'Transaksi A Terkoreksi',
            ])
            ->assertRedirect(route('transactions.index'))
            ->assertSessionHas('success', '1 transaksi berhasil dikoreksi.');

        $this->assertSame('Transaksi A Terkoreksi', $txA->fresh()->description);
    }

    public function test_bulk_correct_updates_category_only_for_six_expense_transactions_with_versions_and_null_reason(): void
    {
        $transactions = collect();
        $versions = [];

        for ($i = 1; $i <= 6; $i++) {
            $tx = $this->service->recordExpense($this->spaceA, $this->userA, [
                'account_id' => $i % 2 === 0 ? $this->gopay->id : $this->bca->id,
                'category_id' => $this->expenseCat->id,
                'member_id' => $i % 2 === 0 ? $this->memberBudi->id : $this->memberAli->id,
                'amount' => 15000 * $i,
                'transaction_date' => sprintf('2026-01-%02d', $i),
                'transaction_time' => sprintf('%02d:00:00', 8 + $i),
                'description' => "Pengeluaran #{$i}",
            ]);

            $transactions->push($tx);
            $versions[$tx->id] = $tx->version;
        }

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.bulk-correct'), [
                'action' => 'update',
                'transaction_ids' => $transactions->pluck('id')->all(),
                'versions' => $versions,
                'reason' => null,
                'apply_category' => true,
                'category_id' => $this->expenseCatAlt->id,
            ]);

        $response->assertOk()->assertJson([
            'message' => '6 transaksi berhasil dikoreksi.',
            'updated_count' => 6,
            'action' => 'update',
        ]);

        foreach ($transactions as $index => $tx) {
            $tx->refresh();
            $this->assertSame($this->expenseCatAlt->id, $tx->category_id);
            $this->assertSame(2, $tx->version);
            $this->assertSame('Pengeluaran #'.($index + 1), $tx->description);
            $this->assertSame(sprintf('2026-01-%02d', $index + 1), $tx->transaction_date->toDateString());
        }

        $this->assertSame(
            6,
            TransactionRevision::whereIn('transaction_id', $transactions->pluck('id'))->count()
        );
    }
}
