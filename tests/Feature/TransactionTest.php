<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialTransaction;
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
}
