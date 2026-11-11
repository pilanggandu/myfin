<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\Space;
use App\Models\TransactionRevision;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

    private Account $bca;

    private Account $gopay;

    private Category $incomeCat;

    private Category $expenseCat;

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
            'type' => 'e-wallet',
            'opening_balance' => 0,
            'opened_on' => '2026-01-01',
        ]);

        $this->incomeCat = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Gaji',
            'type' => 'income',
        ]);

        $this->expenseCat = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Makan',
            'type' => 'expense',
        ]);
    }

    public function test_dokumen_03_scenario_mathematical_correctness(): void
    {
        $this->assertEquals(10000000, $this->bca->currentBalance());

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/transactions', [
                'type' => 'income',
                'account_id' => $this->bca->id,
                'category_id' => $this->incomeCat->id,
                'amount' => 5000000,
                'transaction_date' => '2026-01-05',
                'description' => 'Gaji Bulanan',
            ])
            ->assertRedirect();

        $this->assertEquals(15000000, $this->bca->fresh()->currentBalance());

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/transactions', [
                'type' => 'expense',
                'account_id' => $this->bca->id,
                'category_id' => $this->expenseCat->id,
                'amount' => 200000,
                'transaction_date' => '2026-01-06',
                'description' => 'Makan Siang Resto',
            ])
            ->assertRedirect();

        $this->assertEquals(14800000, $this->bca->fresh()->currentBalance());

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/transactions', [
                'type' => 'transfer',
                'source_account_id' => $this->bca->id,
                'destination_account_id' => $this->gopay->id,
                'amount' => 300000,
                'transaction_date' => '2026-01-07',
                'description' => 'Top Up GoPay dari BCA',
            ])
            ->assertRedirect();

        $this->assertEquals(14500000, $this->bca->fresh()->currentBalance());
        $this->assertEquals(300000, $this->gopay->fresh()->currentBalance());
        $this->assertEquals(14800000, $this->spaceA->totalBalance());
    }

    public function test_transfer_cash_flow_neutrality(): void
    {
        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/transactions', [
                'type' => 'transfer',
                'source_account_id' => $this->bca->id,
                'destination_account_id' => $this->gopay->id,
                'amount' => 500000,
                'transaction_date' => '2026-01-08',
                'description' => 'Transfer Internal',
            ]);

        $transfer = FinancialTransaction::where('type', 'transfer')->first();
        $this->assertNotNull($transfer);

        $entries = $transfer->entries;
        $this->assertCount(2, $entries);
        $this->assertEquals(0, $entries->sum('amount_signed'));

        $sourceEntry = $entries->where('account_id', $this->bca->id)->first();
        $destEntry = $entries->where('account_id', $this->gopay->id)->first();

        $this->assertEquals(-500000, $sourceEntry->amount_signed);
        $this->assertEquals(500000, $destEntry->amount_signed);

        $this->assertNull($transfer->category_id);
    }

    public function test_transfer_with_admin_fee_deducts_fee_from_source_account(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/transactions', [
                'type' => 'transfer',
                'source_account_id' => $this->bca->id,
                'destination_account_id' => $this->gopay->id,
                'amount' => '100.000',
                'fee_amount' => '2.500',
                'fee_category_id' => $this->expenseCat->id,
                'transaction_date' => '2026-01-10',
                'description' => 'Transfer Antar Bank BI-FAST',
            ]);

        $response->assertRedirect('/transactions');

        $transfer = FinancialTransaction::where('type', 'transfer')->latest('id')->first();
        $this->assertNotNull($transfer);
        $this->assertEquals(100000, $transfer->totalAmount());
        $this->assertEquals(2500, (float) $transfer->fee_amount);
        $this->assertEquals($this->expenseCat->id, $transfer->fee_category_id);
        $this->assertEquals(102500, $transfer->totalDeductedFromSource());

        $sourceEntry = $transfer->entries->firstWhere('account_id', $this->bca->id);
        $destEntry = $transfer->entries->firstWhere('account_id', $this->gopay->id);

        $this->assertNotNull($sourceEntry);
        $this->assertNotNull($destEntry);
        $this->assertEquals(-102500, (float) $sourceEntry->amount_signed);
        $this->assertEquals(100000, (float) $destEntry->amount_signed);
    }

    public function test_correcting_transfer_updates_admin_fee_and_entries_atomically(): void
    {
        $service = app(TransactionService::class);
        $transfer = $service->recordTransfer($this->spaceA, $this->userA, [
            'source_account_id' => $this->bca->id,
            'destination_account_id' => $this->gopay->id,
            'amount' => 100000,
            'fee_amount' => 2500,
            'fee_category_id' => $this->expenseCat->id,
            'transaction_date' => '2026-01-10',
            'description' => 'Transfer BI-FAST',
        ]);

        $corrected = $service->correctTransaction($transfer, $this->userA, [
            'type' => 'transfer',
            'source_account_id' => $this->bca->id,
            'destination_account_id' => $this->gopay->id,
            'amount' => 200000,
            'fee_amount' => 6500,
            'fee_category_id' => $this->expenseCat->id,
            'version' => $transfer->version,
            'description' => 'Transfer Online Realtime',
            'reason' => 'Ubah ke transfer online',
        ]);

        $this->assertEquals(2, $corrected->version);
        $this->assertEquals(200000, $corrected->totalAmount());
        $this->assertEquals(6500, (float) $corrected->fee_amount);
        $this->assertEquals(206500, $corrected->totalDeductedFromSource());

        $sourceEntry = $corrected->entries->firstWhere('account_id', $this->bca->id);
        $destEntry = $corrected->entries->firstWhere('account_id', $this->gopay->id);
        $this->assertEquals(-206500, (float) $sourceEntry->amount_signed);
        $this->assertEquals(200000, (float) $destEntry->amount_signed);

        $revision = $corrected->revisions()->first();
        $this->assertNotNull($revision);
        $this->assertEquals(2500, $revision->before_data['fee_amount']);
        $this->assertEquals(6500, $revision->after_data['fee_amount']);
    }

    public function test_transfer_fee_validation_rejects_negative_or_cross_space_category(): void
    {
        $catB = Category::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Expense B',
            'type' => 'expense',
        ]);

        $responseNegative = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/transactions', [
                'type' => 'transfer',
                'source_account_id' => $this->bca->id,
                'destination_account_id' => $this->gopay->id,
                'amount' => 100000,
                'fee_amount' => -500,
                'transaction_date' => '2026-01-10',
                'description' => 'Transfer Invalid Fee',
            ]);

        $responseNegative->assertSessionHasErrors(['fee_amount']);

        $responseCrossSpace = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/transactions', [
                'type' => 'transfer',
                'source_account_id' => $this->bca->id,
                'destination_account_id' => $this->gopay->id,
                'amount' => 100000,
                'fee_amount' => 2500,
                'fee_category_id' => $catB->id,
                'transaction_date' => '2026-01-10',
                'description' => 'Transfer Cross Space Cat',
            ]);

        $responseCrossSpace->assertSessionHasErrors(['fee_category_id']);
    }

    public function test_idempotency_via_request_key(): void
    {
        $service = app(TransactionService::class);
        $key = 'req-unique-12345';

        $tx1 = $service->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->incomeCat->id,
            'amount' => 1000000,
            'transaction_date' => '2026-01-05',
            'description' => 'Pembayaran Proyek',
            'request_key' => $key,
        ]);

        $tx2 = $service->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->incomeCat->id,
            'amount' => 1000000,
            'transaction_date' => '2026-01-05',
            'description' => 'Pembayaran Proyek Duplikat',
            'request_key' => $key,
        ]);

        $this->assertEquals($tx1->id, $tx2->id);
        $this->assertEquals(1, FinancialTransaction::where('request_key', $key)->count());
        $this->assertEquals(11000000, $this->bca->fresh()->currentBalance());
    }

    public function test_transaction_correction_increments_version_and_records_audit_revision(): void
    {
        $service = app(TransactionService::class);
        $tx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 200000,
            'transaction_date' => '2026-01-05',
            'description' => 'Beli Alat Tulis',
        ]);

        $this->assertEquals(1, $tx->version);
        $this->assertEquals(9800000, $this->bca->fresh()->currentBalance());

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put("/transactions/{$tx->id}", [
                'type' => 'expense',
                'account_id' => $this->bca->id,
                'category_id' => $this->expenseCat->id,
                'amount' => 350000,
                'transaction_date' => '2026-01-05',
                'description' => 'Beli Alat Tulis dan Kertas',
                'version' => 1,
                'reason' => 'Perbaikan nota fisik',
            ])
            ->assertRedirect();

        $updated = $tx->fresh();
        $this->assertEquals(2, $updated->version);
        $this->assertEquals(9650000, $this->bca->fresh()->currentBalance());

        $revision = TransactionRevision::where('transaction_id', $tx->id)->first();
        $this->assertNotNull($revision);
        $this->assertEquals($this->userA->id, $revision->actor_id);
        $this->assertEquals('Perbaikan nota fisik', $revision->reason);
        $this->assertEquals(200000, $revision->before_data['amount']);
        $this->assertEquals(350000, $revision->after_data['amount']);
    }

    public function test_correct_transaction_switches_account_balances(): void
    {
        $service = app(TransactionService::class);
        $bcaBefore = $this->bca->currentBalance();
        $gopayBefore = $this->gopay->currentBalance();

        $tx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 5900,
            'transaction_date' => '2026-11-04',
            'description' => 'Le Minerale',
        ]);

        $this->assertEquals($bcaBefore - 5900, $this->bca->fresh()->currentBalance());
        $this->assertEquals($gopayBefore, $this->gopay->fresh()->currentBalance());

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put("/transactions/{$tx->id}", [
                'type' => 'expense',
                'account_id' => $this->gopay->id,
                'category_id' => $this->expenseCat->id,
                'amount' => 5900,
                'transaction_date' => '2026-11-04',
                'description' => 'Le Minerale',
                'version' => $tx->version,
                'reason' => 'Salah pilih rekening',
            ])
            ->assertRedirect();

        $this->assertEquals($bcaBefore, $this->bca->fresh()->currentBalance());
        $this->assertEquals($gopayBefore - 5900, $this->gopay->fresh()->currentBalance());
    }

    public function test_optimistic_locking_prevents_stale_update(): void
    {
        $service = app(TransactionService::class);
        $tx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 200000,
            'transaction_date' => '2026-01-05',
            'description' => 'Beli Alat Tulis',
        ]);

        $this->expectException(ValidationException::class);

        $service->correctTransaction($tx, $this->userA, [
            'type' => 'expense',
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 300000,
            'transaction_date' => '2026-01-05',
            'description' => 'Update Konflik',
            'version' => 999,
        ]);
    }

    public function test_void_transaction_preserves_records_but_excludes_from_balance(): void
    {
        $service = app(TransactionService::class);
        $tx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 500000,
            'transaction_date' => '2026-01-05',
            'description' => 'Salah Catat',
        ]);

        $this->assertEquals(9500000, $this->bca->fresh()->currentBalance());

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post("/transactions/{$tx->id}/void", [
                'reason' => 'Transaksi salah input',
            ])
            ->assertRedirect();

        $txFresh = $tx->fresh();
        $this->assertEquals('void', $txFresh->status);
        $this->assertEquals(2, $txFresh->version);

        $this->assertDatabaseHas('financial_transactions', ['id' => $tx->id, 'status' => 'void']);
        $this->assertDatabaseHas('transaction_entries', ['transaction_id' => $tx->id]);

        $this->assertEquals(10000000, $this->bca->fresh()->currentBalance());

        $revision = TransactionRevision::where('transaction_id', $tx->id)->first();
        $this->assertNotNull($revision);
        $this->assertEquals('void', $revision->after_data['status']);
        $this->assertEquals('Transaksi salah input', $revision->reason);
    }

    public function test_space_isolation_cannot_transact_with_another_space_account(): void
    {
        $accountB = Account::create([
            'space_id' => $this->spaceB->id,
            'name' => 'BCA Milik B',
            'type' => 'bank',
            'opening_balance' => 5000000,
            'opened_on' => '2026-01-01',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/transactions', [
                'type' => 'income',
                'account_id' => $accountB->id,
                'category_id' => $this->incomeCat->id,
                'amount' => 500000,
                'transaction_date' => '2026-01-05',
                'description' => 'Manipulasi Lintas Ruang',
            ]);

        $response->assertSessionHasErrors('account_id');
        $this->assertEquals(5000000, $accountB->fresh()->currentBalance());
    }

    public function test_space_isolation_cannot_use_another_space_category(): void
    {
        $categoryB = Category::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Kategori B',
            'type' => 'income',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/transactions', [
                'type' => 'income',
                'account_id' => $this->bca->id,
                'category_id' => $categoryB->id,
                'amount' => 500000,
                'transaction_date' => '2026-01-05',
                'description' => 'Pakai Kategori Orang Lain',
            ]);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_space_isolation_cannot_view_or_void_another_space_transaction(): void
    {
        $accountB = Account::create([
            'space_id' => $this->spaceB->id,
            'name' => 'BCA Milik B',
            'type' => 'bank',
            'opening_balance' => 5000000,
            'opened_on' => '2026-01-01',
        ]);

        $categoryB = Category::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Gaji B',
            'type' => 'income',
        ]);

        $service = app(TransactionService::class);
        $txB = $service->recordIncome($this->spaceB, $this->userB, [
            'account_id' => $accountB->id,
            'category_id' => $categoryB->id,
            'amount' => 1000000,
            'transaction_date' => '2026-01-05',
            'description' => 'Transaksi Privat B',
        ]);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get("/transactions/{$txB->id}")
            ->assertStatus(403);

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post("/transactions/{$txB->id}/void")
            ->assertStatus(403);
    }

    public function test_validation_rejects_negative_or_zero_amount(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/transactions', [
                'type' => 'income',
                'account_id' => $this->bca->id,
                'category_id' => $this->incomeCat->id,
                'amount' => 0,
                'transaction_date' => '2026-01-05',
                'description' => 'Nominal Nol',
            ]);

        $response->assertSessionHasErrors('amount');

        $responseNeg = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/transactions', [
                'type' => 'income',
                'account_id' => $this->bca->id,
                'category_id' => $this->incomeCat->id,
                'amount' => -50000,
                'transaction_date' => '2026-01-05',
                'description' => 'Nominal Negatif',
            ]);

        $responseNeg->assertSessionHasErrors('amount');
    }

    public function test_transfer_source_and_destination_must_be_different(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/transactions', [
                'type' => 'transfer',
                'source_account_id' => $this->bca->id,
                'destination_account_id' => $this->bca->id,
                'amount' => 100000,
                'transaction_date' => '2026-01-05',
                'description' => 'Transfer Ke Diri Sendiri',
            ]);

        $response->assertSessionHasErrors('destination_account_id');
    }

    public function test_transfer_correction_source_and_destination_must_be_different(): void
    {
        $service = app(TransactionService::class);
        $tx = $service->recordTransfer($this->spaceA, $this->userA, [
            'source_account_id' => $this->bca->id,
            'destination_account_id' => $this->gopay->id,
            'amount' => 150000,
            'transaction_date' => '2026-01-05',
            'description' => 'Transfer Awal',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put("/transactions/{$tx->id}", [
                'type' => 'transfer',
                'source_account_id' => $this->bca->id,
                'destination_account_id' => $this->bca->id,
                'amount' => 150000,
                'transaction_date' => '2026-01-05',
                'description' => 'Koreksi Transfer Ke Diri Sendiri',
                'version' => $tx->version,
            ]);

        $response->assertSessionHasErrors('destination_account_id');
    }

    public function test_quick_modal_ajax_transfer_same_account_returns_validation_error(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson('/transactions', [
                'type' => 'transfer',
                'source_account_id' => $this->bca->id,
                'destination_account_id' => $this->bca->id,
                'amount' => 75000,
                'transaction_date' => '2026-01-05',
                'description' => 'Transfer Cepat Ke Diri Sendiri',
                'action' => 'save_and_again',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['destination_account_id'])
            ->assertJsonPath('errors.destination_account_id.0', 'Rekening tujuan transfer harus berbeda dengan rekening asal.');
    }

    public function test_cannot_transact_on_archived_account(): void
    {
        $this->bca->archive();

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/transactions', [
                'type' => 'income',
                'account_id' => $this->bca->id,
                'category_id' => $this->incomeCat->id,
                'amount' => 100000,
                'transaction_date' => '2026-01-05',
                'description' => 'Rekening Arsip',
            ]);

        $response->assertSessionHasErrors('account_id');
    }

    public function test_cannot_record_transaction_with_archived_category(): void
    {
        $this->incomeCat->archive();

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/transactions', [
                'type' => 'income',
                'account_id' => $this->bca->id,
                'category_id' => $this->incomeCat->id,
                'amount' => 100000,
                'transaction_date' => '2026-01-05',
                'description' => 'Kategori Arsip',
            ]);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_cannot_correct_transaction_with_archived_account(): void
    {
        $service = app(TransactionService::class);
        $tx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 100000,
            'transaction_date' => '2026-01-05',
            'description' => 'Transaksi Asli',
        ]);

        $this->gopay->archive();

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put("/transactions/{$tx->id}", [
                'type' => 'expense',
                'account_id' => $this->gopay->id,
                'category_id' => $this->expenseCat->id,
                'amount' => 100000,
                'transaction_date' => '2026-01-05',
                'description' => 'Koreksi Ke Rekening Arsip',
                'version' => $tx->version,
            ]);

        $response->assertSessionHasErrors('account_id');
    }

    public function test_cannot_correct_transaction_with_archived_category(): void
    {
        $service = app(TransactionService::class);
        $tx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 100000,
            'transaction_date' => '2026-01-05',
            'description' => 'Transaksi Asli',
        ]);

        $this->expenseCat->archive();

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put("/transactions/{$tx->id}", [
                'type' => 'expense',
                'account_id' => $this->bca->id,
                'category_id' => $this->expenseCat->id,
                'amount' => 100000,
                'transaction_date' => '2026-01-05',
                'description' => 'Koreksi Ke Kategori Arsip',
                'version' => $tx->version,
            ]);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_cannot_correct_transaction_with_cross_space_account_or_category(): void
    {
        $service = app(TransactionService::class);
        $tx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 100000,
            'transaction_date' => '2026-01-05',
            'description' => 'Transaksi Asli',
        ]);

        $accountB = Account::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Rekening B',
            'type' => 'bank',
            'opening_balance' => 1000000,
            'opened_on' => '2026-01-01',
        ]);

        $responseAcc = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put("/transactions/{$tx->id}", [
                'type' => 'expense',
                'account_id' => $accountB->id,
                'category_id' => $this->expenseCat->id,
                'amount' => 100000,
                'transaction_date' => '2026-01-05',
                'description' => 'Koreksi Ke Rekening Ruang Lain',
                'version' => $tx->version,
            ]);

        $responseAcc->assertSessionHasErrors('account_id');

        $categoryB = Category::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Kategori B',
            'type' => 'expense',
        ]);

        $responseCat = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->put("/transactions/{$tx->id}", [
                'type' => 'expense',
                'account_id' => $this->bca->id,
                'category_id' => $categoryB->id,
                'amount' => 100000,
                'transaction_date' => '2026-01-05',
                'description' => 'Koreksi Ke Kategori Ruang Lain',
                'version' => $tx->version,
            ]);

        $responseCat->assertSessionHasErrors('category_id');
    }

    public function test_voided_transaction_cannot_be_corrected(): void
    {
        $service = app(TransactionService::class);
        $tx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 100000,
            'transaction_date' => '2026-01-05',
            'description' => 'Transaksi Asli',
        ]);

        $service->voidTransaction($tx, $this->userA);

        $this->expectException(ValidationException::class);

        $service->correctTransaction($tx, $this->userA, [
            'type' => 'expense',
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 200000,
            'transaction_date' => '2026-01-05',
            'description' => 'Koreksi Transaksi Void',
            'version' => $tx->version,
        ]);
    }

    public function test_transaction_create_screen_renders_successfully(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.create'));

        $response->assertStatus(200);
        $response->assertSee('Catat Transaksi Keuangan');
        $response->assertSee('Pengeluaran');
        $response->assertSee('Pemasukan');
        $response->assertSee('Transfer');
    }

    public function test_transaction_edit_screen_renders_successfully(): void
    {
        $service = app(TransactionService::class);
        $tx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 150000,
            'transaction_date' => '2026-01-05',
            'description' => 'Makan Siang',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.edit', $tx));

        $response->assertStatus(200);
        $response->assertSee('Koreksi Transaksi #'.$tx->id);
        $response->assertSee('Alasan Koreksi');
    }

    public function test_transfer_transaction_edit_screen_renders_transfer_fields_and_fee_presets(): void
    {
        $service = app(TransactionService::class);
        $transfer = $service->recordTransfer($this->spaceA, $this->userA, [
            'source_account_id' => $this->bca->id,
            'destination_account_id' => $this->gopay->id,
            'amount' => 250000,
            'fee_amount' => 2500,
            'fee_category_id' => $this->expenseCat->id,
            'transaction_date' => '2026-01-06',
            'description' => 'Top Up GoPay',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.edit', $transfer));

        $response->assertOk();
        $response->assertSee('Jalur Transfer Rekening');
        $response->assertSee('Biaya Admin Transfer');
        $response->assertSee('Pilih Cepat Biaya Admin');
        $response->assertSee('value="2.500"', false);
        $response->assertSee('data-fee-preset="2500"', false);
    }

    public function test_transaction_records_and_updates_transaction_time_cleanly(): void
    {
        $service = app(TransactionService::class);
        $tx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 75000,
            'transaction_date' => '2026-01-05',
            'transaction_time' => '14:30:00',
            'description' => 'Makan Siang Enak',
        ]);

        $this->assertEquals('14:30', $tx->formattedTime());

        $corrected = $service->correctTransaction($tx, $this->userA, [
            'amount' => 80000,
            'transaction_date' => '2026-01-05',
            'transaction_time' => '15:45:00',
            'description' => 'Makan Siang Revisi',
            'reason' => 'Perbaikan jam dan nominal',
            'version' => $tx->version,
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
        ]);

        $this->assertEquals('15:45', $corrected->formattedTime());
    }

    public function test_transaction_stores_via_http_with_time(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'account_id' => $this->bca->id,
                'category_id' => $this->expenseCat->id,
                'amount' => 45000,
                'transaction_date' => '2026-01-05',
                'transaction_time' => '09:15',
                'description' => 'Kopi Pagi',
                'request_key' => 'req-test-time-001',
            ]);

        $response->assertRedirect(route('transactions.index'));

        $tx = FinancialTransaction::where('request_key', 'req-test-time-001')->firstOrFail();
        $this->assertEquals('09:15', $tx->formattedTime());
    }

    public function test_transaction_stores_and_redirects_to_create_when_save_and_again_requested(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'account_id' => $this->bca->id,
                'category_id' => $this->expenseCat->id,
                'amount' => 60000,
                'transaction_date' => '2026-01-05',
                'transaction_time' => '10:00',
                'description' => 'Makan Siang Cepat',
                'action' => 'save_and_again',
            ]);

        $response->assertRedirect(route('transactions.create', [
            'type' => 'expense',
            'date' => '2026-01-05',
            'time' => '10:00',
            'account_id' => $this->bca->id,
        ]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('financial_transactions', [
            'description' => 'Makan Siang Cepat',
        ]);
    }

    public function test_transaction_stores_via_json_request_for_quick_continuous_entry(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.store'), [
                'type' => 'expense',
                'account_id' => $this->bca->id,
                'category_id' => $this->expenseCat->id,
                'amount' => 25000,
                'transaction_date' => '2026-01-05',
                'description' => 'Kopi Sore Cepat',
                'action' => 'save_and_again',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Transaksi berhasil dicatat.',
        ]);

        $this->assertDatabaseHas('financial_transactions', [
            'description' => 'Kopi Sore Cepat',
        ]);
    }

    public function test_transaction_stores_with_decimal_amount_comma_notation(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'account_id' => $this->bca->id,
                'category_id' => $this->expenseCat->id,
                'amount' => '25.000,50',
                'transaction_date' => '2026-01-05',
                'description' => 'Kopi Desimal',
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transaction_entries', [
            'account_id' => $this->bca->id,
            'amount_signed' => -25000.50,
        ]);
    }

    public function test_transactions_are_ordered_by_date_and_time_descending_by_default(): void
    {
        $trxOld = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'transaction_date' => '2026-11-01',
            'transaction_time' => '10:00:00',
            'description' => 'Transaksi Lama',
            'category_id' => $this->expenseCat->id,
            'created_by' => $this->userA->id,
            'status' => 'posted',
            'version' => 1,
        ]);

        $trxNewMorning = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'transaction_date' => '2026-11-04',
            'transaction_time' => '08:30:00',
            'description' => 'Transaksi Baru Pagi',
            'category_id' => $this->expenseCat->id,
            'created_by' => $this->userA->id,
            'status' => 'posted',
            'version' => 1,
        ]);

        $trxNewAfternoon = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'transaction_date' => '2026-11-04',
            'transaction_time' => '15:45:00',
            'description' => 'Transaksi Baru Sore',
            'category_id' => $this->expenseCat->id,
            'created_by' => $this->userA->id,
            'status' => 'posted',
            'version' => 1,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.index'));

        $response->assertOk();
        $transactions = $response->viewData('transactions');

        // Urutan default harus: 2026-11-04 15:45, lalu 2026-11-04 08:30, lalu 2026-11-01 10:00
        $this->assertEquals($trxNewAfternoon->id, $transactions->items()[0]->id);
        $this->assertEquals($trxNewMorning->id, $transactions->items()[1]->id);
        $this->assertEquals($trxOld->id, $transactions->items()[2]->id);
    }

    public function test_transactions_can_be_ordered_by_date_ascending(): void
    {
        $trxOld = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'transaction_date' => '2026-11-01',
            'transaction_time' => '10:00:00',
            'description' => 'Transaksi Lama',
            'category_id' => $this->expenseCat->id,
            'created_by' => $this->userA->id,
            'status' => 'posted',
            'version' => 1,
        ]);

        $trxNewMorning = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'transaction_date' => '2026-11-04',
            'transaction_time' => '08:30:00',
            'description' => 'Transaksi Baru Pagi',
            'category_id' => $this->expenseCat->id,
            'created_by' => $this->userA->id,
            'status' => 'posted',
            'version' => 1,
        ]);

        $trxNewAfternoon = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'transaction_date' => '2026-11-04',
            'transaction_time' => '15:45:00',
            'description' => 'Transaksi Baru Sore',
            'category_id' => $this->expenseCat->id,
            'created_by' => $this->userA->id,
            'status' => 'posted',
            'version' => 1,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.index', ['sort' => 'date_asc']));

        $response->assertOk();
        $transactions = $response->viewData('transactions');

        // Urutan ASC harus: 2026-11-01 10:00, lalu 2026-11-04 08:30, lalu 2026-11-04 15:45
        $this->assertEquals($trxOld->id, $transactions->items()[0]->id);
        $this->assertEquals($trxNewMorning->id, $transactions->items()[1]->id);
        $this->assertEquals($trxNewAfternoon->id, $transactions->items()[2]->id);
    }

    public function test_transactions_index_defaults_to_posted_status_excluding_void(): void
    {
        $postedTrx = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'transaction_date' => '2026-11-05',
            'transaction_time' => '10:00:00',
            'description' => 'Transaksi Aktif',
            'category_id' => $this->expenseCat->id,
            'created_by' => $this->userA->id,
            'status' => 'posted',
            'version' => 1,
        ]);

        $voidTrx = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'transaction_date' => '2026-11-05',
            'transaction_time' => '11:00:00',
            'description' => 'Transaksi Batal',
            'category_id' => $this->expenseCat->id,
            'created_by' => $this->userA->id,
            'status' => 'void',
            'version' => 1,
        ]);

        // Default: hanya posted
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.index'));

        $response->assertOk();
        $transactions = $response->viewData('transactions');
        $this->assertTrue($transactions->contains('id', $postedTrx->id));
        $this->assertFalse($transactions->contains('id', $voidTrx->id));

        // Filter status=void: hanya void
        $responseVoid = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.index', ['status' => 'void']));

        $responseVoid->assertOk();
        $txVoidData = $responseVoid->viewData('transactions');
        $this->assertFalse($txVoidData->contains('id', $postedTrx->id));
        $this->assertTrue($txVoidData->contains('id', $voidTrx->id));

        // Filter status=all: keduanya muncul
        $responseAll = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.index', ['status' => 'all']));

        $responseAll->assertOk();
        $txAllData = $responseAll->viewData('transactions');
        $this->assertTrue($txAllData->contains('id', $postedTrx->id));
        $this->assertTrue($txAllData->contains('id', $voidTrx->id));
    }

    public function test_transactions_index_defaults_to_past_and_today_hiding_future_transactions(): void
    {
        $today = $this->spaceA->now()->toDateString();
        $futureDate = $this->spaceA->now()->addMonth()->toDateString();

        $todayTrx = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'transaction_date' => $today,
            'transaction_time' => '10:00:00',
            'description' => 'Makan Siang Hari Ini',
            'category_id' => $this->expenseCat->id,
            'created_by' => $this->userA->id,
            'status' => 'posted',
            'version' => 1,
        ]);
        $todayTrx->entries()->create(['account_id' => $this->bca->id, 'amount_signed' => -50000]);

        $futureTrx = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'transaction_date' => $futureDate,
            'transaction_time' => '12:00:00',
            'description' => 'Tagihan Kredit Bulan Depan',
            'category_id' => $this->expenseCat->id,
            'created_by' => $this->userA->id,
            'status' => 'posted',
            'version' => 1,
        ]);
        $futureTrx->entries()->create(['account_id' => $this->bca->id, 'amount_signed' => -200000]);

        // Default: transaksi mendatang disembunyikan
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.index'));

        $response->assertOk();
        $txList = $response->viewData('transactions');
        $this->assertTrue($txList->contains('id', $todayTrx->id));
        $this->assertFalse($txList->contains('id', $futureTrx->id));
        $this->assertEquals(1, $response->viewData('upcomingCount'));
        $response->assertSee('Terdapat 1 transaksi mendatang disembunyikan', false);
        $response->assertSee('Tampilkan Transaksi Mendatang', false);

        // Dengan parameter include_future=1: transaksi mendatang ikut muncul
        $responseFuture = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.index', ['include_future' => 1]));

        $responseFuture->assertOk();
        $txFutureList = $responseFuture->viewData('transactions');
        $this->assertTrue($txFutureList->contains('id', $todayTrx->id));
        $this->assertTrue($txFutureList->contains('id', $futureTrx->id));
        $responseFuture->assertSee('Menampilkan transaksi termasuk transaksi mendatang', false);
        $responseFuture->assertSee('Sembunyikan Transaksi Mendatang', false);
    }

    public function test_recently_used_categories_reflect_latest_posted_usage_and_exclude_void_archived_and_other_spaces(): void
    {
        $catTransport = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Transportasi',
            'type' => 'expense',
        ]);
        $catBelanja = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Belanja Harian',
            'type' => 'expense',
        ]);
        $catVoidOnly = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Kategori Void',
            'type' => 'expense',
        ]);
        $catArchived = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Kategori Arsip',
            'type' => 'expense',
        ]);
        $catSpaceB = Category::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Kategori Ruang B',
            'type' => 'expense',
        ]);
        $accountB = Account::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Dompet B',
            'type' => 'cash',
            'opening_balance' => 500000,
            'opened_on' => '2026-01-01',
        ]);

        $service = app(TransactionService::class);

        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 25000,
            'transaction_date' => '2026-11-05',
            'description' => 'Makan Pagi',
        ]);
        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $catTransport->id,
            'amount' => 30000,
            'transaction_date' => '2026-11-06',
            'description' => 'Bensin',
        ]);
        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $catArchived->id,
            'amount' => 40000,
            'transaction_date' => '2026-11-07',
            'description' => 'Transaksi Arsip',
        ]);
        $catArchived->archive();

        $voidTx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $catVoidOnly->id,
            'amount' => 50000,
            'transaction_date' => '2026-11-08',
            'description' => 'Salah Input',
        ]);
        $service->voidTransaction($voidTx, $this->userA, 'Batal');

        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $catBelanja->id,
            'amount' => 75000,
            'transaction_date' => '2026-11-09',
            'description' => 'Belanja Mingguan',
        ]);
        // Re-use initial expense category so it becomes #1 most recently used.
        $lastTx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 15000,
            'transaction_date' => '2026-11-01',
            'description' => 'Kopi Susu',
        ]);
        $service->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->incomeCat->id,
            'amount' => 1000000,
            'transaction_date' => '2026-11-10',
            'description' => 'Gaji',
        ]);
        $service->recordExpense($this->spaceB, $this->userB, [
            'account_id' => $accountB->id,
            'category_id' => $catSpaceB->id,
            'amount' => 60000,
            'transaction_date' => '2026-11-10',
            'description' => 'Belanja B',
        ]);

        $expectedExpenseRecent = [$this->expenseCat->id, $catBelanja->id, $catTransport->id];
        $this->assertSame($expectedExpenseRecent, $this->spaceA->recentCategoryIds('expense', 5));
        $this->assertSame([$this->incomeCat->id], $this->spaceA->recentCategoryIds('income', 5));

        $createResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.create'));

        $createResponse->assertOk();
        $this->assertSame($expectedExpenseRecent, $createResponse->viewData('recentExpenseCategoryIds'));
        $this->assertSame([$this->incomeCat->id], $createResponse->viewData('recentIncomeCategoryIds'));
        $createResponse->assertSee('data-category-picker="true"', false);
        $createResponse->assertSee('data-show-recent-chips="true"', false);

        $editResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.edit', $lastTx));

        $editResponse->assertOk();
        $this->assertSame($expectedExpenseRecent, $editResponse->viewData('recentExpenseCategoryIds'));
        $editResponse->assertSee('data-category-picker="true"', false);

        $indexResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Kategori terakhir:', false);
        $this->assertContains($this->incomeCat->id, $indexResponse->viewData('recentCategoryIds'));
        $this->assertNotContains($catSpaceB->id, $indexResponse->viewData('recentCategoryIds'));
        $this->assertNotContains($catVoidOnly->id, $indexResponse->viewData('recentCategoryIds'));
    }

    public function test_transaction_store_json_returns_updated_recent_category_ids(): void
    {
        $catKopi = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Kopi & Camilan',
            'type' => 'expense',
        ]);

        $service = app(TransactionService::class);
        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 30000,
            'transaction_date' => '2026-11-05',
            'description' => 'Nasi Goreng',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->postJson(route('transactions.store'), [
                'type' => 'expense',
                'account_id' => $this->gopay->id,
                'category_id' => $catKopi->id,
                'amount' => 22000,
                'transaction_date' => '2026-11-06',
                'description' => 'Es Kopi Susu',
                'action' => 'save_and_again',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('transaction.category_id', $catKopi->id)
            ->assertJsonPath('recent_category_ids.expense', [$catKopi->id, $this->expenseCat->id])
            ->assertJsonPath('recent_account_ids', [$this->gopay->id, $this->bca->id]);
    }

    public function test_transactions_index_search_matches_description_category_account_member_and_amount(): void
    {
        $catCafe = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Kafe & Minuman',
            'type' => 'expense',
        ]);
        $catUtility = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Utilitas Rumah',
            'type' => 'expense',
        ]);
        $catAdminFee = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Biaya Layanan Bank',
            'type' => 'expense',
        ]);
        $memberBudi = Member::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Budi Santoso',
            'color' => '#0B4A8B',
        ]);

        $service = app(TransactionService::class);
        $txCafe = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->gopay->id,
            'category_id' => $catCafe->id,
            'member_id' => $memberBudi->id,
            'amount' => 45000,
            'transaction_date' => '2026-11-01',
            'description' => 'Beli Matcha Latte',
        ]);
        $txUtility = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $catUtility->id,
            'amount' => 350000,
            'transaction_date' => '2026-11-02',
            'description' => 'Token Listrik',
        ]);
        $txTransfer = $service->recordTransfer($this->spaceA, $this->userA, [
            'source_account_id' => $this->bca->id,
            'destination_account_id' => $this->gopay->id,
            'amount' => 100000,
            'fee_amount' => 2500,
            'fee_category_id' => $catAdminFee->id,
            'transaction_date' => '2026-11-03',
            'description' => 'Isi Saldo Dompet',
        ]);

        // Different space transaction containing matching keyword must remain isolated.
        $accountB = Account::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Bank B',
            'type' => 'bank',
            'opening_balance' => 1000000,
            'opened_on' => '2026-01-01',
        ]);
        $catB = Category::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Kafe B',
            'type' => 'expense',
        ]);
        $txSpaceB = $service->recordExpense($this->spaceB, $this->userB, [
            'account_id' => $accountB->id,
            'category_id' => $catB->id,
            'amount' => 45000,
            'transaction_date' => '2026-11-03',
            'description' => 'Beli Matcha Latte Ruang B',
        ]);

        $search = function (string $term) {
            return $this->actingAs($this->userA)
                ->withSession(['active_space_id' => $this->spaceA->id])
                ->get(route('transactions.index', ['search' => $term]));
        };

        // 1. Description match (isolated to active space)
        $byDesc = $search('Matcha')->assertOk()->viewData('transactions');
        $this->assertTrue($byDesc->contains('id', $txCafe->id));
        $this->assertFalse($byDesc->contains('id', $txSpaceB->id));

        // 2. Category name match
        $byCat = $search('Kafe')->assertOk()->viewData('transactions');
        $this->assertSame([$txCafe->id], $byCat->pluck('id')->all());

        // 3. Fee category name match on transfer
        $byFeeCat = $search('Layanan Bank')->assertOk()->viewData('transactions');
        $this->assertSame([$txTransfer->id], $byFeeCat->pluck('id')->all());

        // 4. Account name match
        $byAccount = $search('Bank BCA')->assertOk()->viewData('transactions');
        $this->assertTrue($byAccount->contains('id', $txUtility->id));
        $this->assertTrue($byAccount->contains('id', $txTransfer->id));
        $this->assertFalse($byAccount->contains('id', $txCafe->id));

        // 5. Member name match
        $byMember = $search('Santoso')->assertOk()->viewData('transactions');
        $this->assertSame([$txCafe->id], $byMember->pluck('id')->all());

        // 6. Plain & formatted Rupiah amount match + admin fee amount match
        $byPlainAmount = $search('350000')->assertOk()->viewData('transactions');
        $this->assertSame([$txUtility->id], $byPlainAmount->pluck('id')->all());

        $byFormattedAmount = $search('Rp 350.000')->assertOk()->viewData('transactions');
        $this->assertSame([$txUtility->id], $byFormattedAmount->pluck('id')->all());

        $byFeeAmount = $search('2.500')->assertOk()->viewData('transactions');
        $this->assertSame([$txTransfer->id], $byFeeAmount->pluck('id')->all());

        // 7. Empty search result state
        $emptyRes = $search('KeywordTidakAdaSamaSekali')->assertOk();
        $emptyRes->assertSee('Tidak Ada Transaksi yang Cocok', false);
        $emptyRes->assertSee('Reset Pencarian &amp; Filter', false);
    }

    public function test_transactions_index_upcoming_count_respects_expanded_search(): void
    {
        $futureDate = $this->spaceA->now()->addDays(10)->toDateString();
        $catSubscription = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Langganan Digital',
            'type' => 'expense',
        ]);

        $service = app(TransactionService::class);
        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $catSubscription->id,
            'amount' => 189000,
            'transaction_date' => $futureDate,
            'description' => 'Tagihan Cloud Mendatang',
        ]);

        $matchingRes = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.index', ['search' => 'Langganan']));

        $matchingRes->assertOk();
        $this->assertSame(1, $matchingRes->viewData('upcomingCount'));

        $nonMatchingRes = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.index', ['search' => 'Bensin']));

        $nonMatchingRes->assertOk();
        $this->assertSame(0, $nonMatchingRes->viewData('upcomingCount'));
    }

    public function test_recent_accounts_order_limit_isolation_and_picker_attributes(): void
    {
        $cashAcc = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Dompet Tunai',
            'type' => 'cash',
            'opening_balance' => 500000,
            'opened_on' => '2026-01-01',
        ]);
        $archivedAcc = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Rekening Lama',
            'type' => 'bank',
            'opening_balance' => 100000,
            'opened_on' => '2026-01-01',
        ]);
        $voidOnlyAcc = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'OVO Batal',
            'type' => 'e-wallet',
            'opening_balance' => 100000,
            'opened_on' => '2026-01-01',
        ]);
        $accountB = Account::create([
            'space_id' => $this->spaceB->id,
            'name' => 'BCA Ruang B',
            'type' => 'bank',
            'opening_balance' => 1000000,
            'opened_on' => '2026-01-01',
        ]);
        $catSpaceB = Category::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Makan Ruang B',
            'type' => 'expense',
        ]);

        $service = app(TransactionService::class);
        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 50000,
            'transaction_date' => '2026-11-01',
            'description' => 'Sarapan',
        ]);
        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $archivedAcc->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 25000,
            'transaction_date' => '2026-11-02',
            'description' => 'Sebelum arsip',
        ]);
        $archivedAcc->archive();

        $voidTx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $voidOnlyAcc->id,
            'category_id' => $this->expenseCat->id,
            'amount' => 30000,
            'transaction_date' => '2026-11-03',
            'description' => 'Salah rekening',
        ]);
        $service->voidTransaction($voidTx, $this->userA, 'Batal');

        $service->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $cashAcc->id,
            'category_id' => $this->incomeCat->id,
            'amount' => 150000,
            'transaction_date' => '2026-11-04',
            'description' => 'Uang saku',
        ]);
        // Transfer touches both gopay (source) and bca (destination).
        $lastTx = $service->recordTransfer($this->spaceA, $this->userA, [
            'source_account_id' => $this->gopay->id,
            'destination_account_id' => $this->bca->id,
            'amount' => 40000,
            'transaction_date' => '2026-11-05',
            'description' => 'Balik ke BCA',
        ]);
        $service->recordExpense($this->spaceB, $this->userB, [
            'account_id' => $accountB->id,
            'category_id' => $catSpaceB->id,
            'amount' => 80000,
            'transaction_date' => '2026-11-06',
            'description' => 'Belanja B',
        ]);

        $expectedRecentAccounts = [$this->bca->id, $this->gopay->id, $cashAcc->id];
        $this->assertSame($expectedRecentAccounts, $this->spaceA->recentAccountIds(limit: 5));
        $this->assertSame([$this->bca->id, $this->gopay->id], $this->spaceA->recentAccountIds(limit: 2));

        $createResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.create'));

        $createResponse->assertOk();
        $this->assertSame($expectedRecentAccounts, $createResponse->viewData('recentAccountIds'));
        $createResponse->assertSee('data-searchable-picker="true"', false);
        $createResponse->assertSee('data-picker-kind="account"', false);

        $editResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.edit', $lastTx));

        $editResponse->assertOk();
        $this->assertSame($expectedRecentAccounts, $editResponse->viewData('recentAccountIds'));
        $editResponse->assertSee('data-picker-kind="account"', false);

        $indexResponse = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.index'));

        $indexResponse->assertOk();
        $this->assertSame($expectedRecentAccounts, $indexResponse->viewData('recentAccountIds'));
        $indexResponse->assertSee('id="filterAccountSelect"', false);
        $indexResponse->assertSee('data-picker-kind="account"', false);
    }
}
