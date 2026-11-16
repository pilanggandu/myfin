<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialTransaction;
use App\Models\Space;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private Space $space;

    private Space $otherSpace;

    private Account $bcaAccount;

    private Account $cashAccount;

    private Account $gopayAccount;

    private Account $otherSpaceAccount;

    private Category $foodCategory;

    private Category $shoppingCategory;

    private TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Gilan Permadi',
            'email' => 'gilan@example.com',
        ]);

        $this->space = Space::create([
            'name' => 'Ruang Keuangan Gilan',
            'type' => 'personal',
            'owner_id' => $this->user->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->space->members()->attach($this->user->id, ['role' => 'owner', 'status' => 'active']);

        $this->otherUser = User::factory()->create([
            'name' => 'Stranger',
            'email' => 'stranger@example.com',
        ]);

        $this->otherSpace = Space::create([
            'name' => 'Ruang Orang Lain',
            'type' => 'personal',
            'owner_id' => $this->otherUser->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->otherSpace->members()->attach($this->otherUser->id, ['role' => 'owner', 'status' => 'active']);

        $this->bcaAccount = Account::create([
            'space_id' => $this->space->id,
            'name' => 'Bank BCA',
            'type' => 'bank',
            'currency' => 'IDR',
            'opening_balance' => 5000000,
            'opened_on' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->cashAccount = Account::create([
            'space_id' => $this->space->id,
            'name' => 'Dompet Kas Tunai',
            'type' => 'cash',
            'currency' => 'IDR',
            'opening_balance' => 1000000,
            'opened_on' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->gopayAccount = Account::create([
            'space_id' => $this->space->id,
            'name' => 'GoPay',
            'type' => 'e-wallet',
            'currency' => 'IDR',
            'opening_balance' => 500000,
            'opened_on' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->otherSpaceAccount = Account::create([
            'space_id' => $this->otherSpace->id,
            'name' => 'Bank Mandiri Orang Lain',
            'type' => 'bank',
            'currency' => 'IDR',
            'opening_balance' => 2000000,
            'opened_on' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->foodCategory = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Makanan & Minuman',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->shoppingCategory = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Belanja Kebutuhan',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->transactionService = app(TransactionService::class);
    }

    public function test_it_can_record_expense_with_split_payments_across_multiple_accounts(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'transaction_date' => '2026-11-16',
                'description' => 'Makan Bareng & Belanja (Split Akun)',
                'amount' => 500000,
                'category_id' => $this->foodCategory->id,
                'is_split_payment' => 1,
                'payments' => [
                    [
                        'account_id' => $this->bcaAccount->id,
                        'amount' => 350000,
                    ],
                    [
                        'account_id' => $this->cashAccount->id,
                        'amount' => 150000,
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('transactions.index'));

        $tx = FinancialTransaction::query()
            ->where('description', 'Makan Bareng & Belanja (Split Akun)')
            ->first();

        $this->assertNotNull($tx);
        $this->assertTrue($tx->isSplitPayment());
        $this->assertEquals(500000, $tx->totalAmount());
        $this->assertCount(2, $tx->entries);
        $this->assertEquals(500000.0, $tx->splitPaymentTotal());
        $this->assertTrue($tx->isSplitPaymentBalanced());

        $bcaEntry = $tx->entries->firstWhere('account_id', $this->bcaAccount->id);
        $cashEntry = $tx->entries->firstWhere('account_id', $this->cashAccount->id);

        $this->assertNotNull($bcaEntry);
        $this->assertEquals(-350000, $bcaEntry->amount_signed);

        $this->assertNotNull($cashEntry);
        $this->assertEquals(-150000, $cashEntry->amount_signed);

        $this->assertEquals(5000000 - 350000, $this->bcaAccount->fresh()->currentBalance());
        $this->assertEquals(1000000 - 150000, $this->cashAccount->fresh()->currentBalance());
    }

    public function test_it_fails_validation_when_payment_sum_does_not_match_transaction_amount(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'transaction_date' => '2026-11-16',
                'description' => 'Belanja Selisih Nominal',
                'amount' => 500000,
                'category_id' => $this->foodCategory->id,
                'is_split_payment' => 1,
                'payments' => [
                    [
                        'account_id' => $this->bcaAccount->id,
                        'amount' => 300000,
                    ],
                    [
                        'account_id' => $this->cashAccount->id,
                        'amount' => 100000, // Total 400.000 != 500.000
                    ],
                ],
            ]);

        $response->assertSessionHasErrors(['payments']);
        $this->assertDatabaseMissing('financial_transactions', [
            'description' => 'Belanja Selisih Nominal',
        ]);
    }

    public function test_it_fails_validation_with_less_than_two_payment_accounts(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'transaction_date' => '2026-11-16',
                'description' => 'Split Payment 1 Akun',
                'amount' => 200000,
                'category_id' => $this->foodCategory->id,
                'is_split_payment' => 1,
                'payments' => [
                    [
                        'account_id' => $this->bcaAccount->id,
                        'amount' => 200000,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors(['payments']);
    }

    public function test_it_fails_validation_when_duplicate_account_is_selected(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'transaction_date' => '2026-11-16',
                'description' => 'Akun Duplikat',
                'amount' => 400000,
                'category_id' => $this->foodCategory->id,
                'is_split_payment' => 1,
                'payments' => [
                    [
                        'account_id' => $this->bcaAccount->id,
                        'amount' => 200000,
                    ],
                    [
                        'account_id' => $this->bcaAccount->id, // Duplikat BCA
                        'amount' => 200000,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors();
    }

    public function test_it_fails_validation_when_account_belongs_to_another_space(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'transaction_date' => '2026-11-16',
                'description' => 'Akun Beda Space',
                'amount' => 300000,
                'category_id' => $this->foodCategory->id,
                'is_split_payment' => 1,
                'payments' => [
                    [
                        'account_id' => $this->bcaAccount->id,
                        'amount' => 150000,
                    ],
                    [
                        'account_id' => $this->otherSpaceAccount->id,
                        'amount' => 150000,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors();
    }

    public function test_it_can_split_payment_on_existing_transaction(): void
    {
        // 1. Buat transaksi pengeluaran tunggal normal (Rp 600.000 dari BCA)
        $tx = $this->transactionService->recordExpense($this->space, $this->user, [
            'transaction_date' => '2026-11-16',
            'description' => 'Belanja Perlengkapan Kantor',
            'amount' => 600000,
            'account_id' => $this->bcaAccount->id,
            'category_id' => $this->shoppingCategory->id,
        ]);

        $this->assertFalse($tx->isSplitPayment());
        $this->assertEquals(5000000 - 600000, $this->bcaAccount->fresh()->currentBalance());

        // 2. Sekarang kita split pembayarannya: BCA Rp 400.000 + GoPay Rp 200.000
        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->post(route('transactions.split-payment', $tx), [
                'payments' => [
                    [
                        'account_id' => $this->bcaAccount->id,
                        'amount' => 400000,
                    ],
                    [
                        'account_id' => $this->gopayAccount->id,
                        'amount' => 200000,
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('transactions.show', $tx));

        $tx->refresh();
        $this->assertTrue($tx->isSplitPayment());
        $this->assertCount(2, $tx->entries);
        $this->assertEquals(600000, $tx->totalAmount());

        // Saldo BCA seharusnya berkurang 400.000 (bukan 600.000 lagi)
        $this->assertEquals(5000000 - 400000, $this->bcaAccount->fresh()->currentBalance());
        // Saldo GoPay seharusnya berkurang 200.000
        $this->assertEquals(500000 - 200000, $this->gopayAccount->fresh()->currentBalance());
    }

    public function test_it_can_unsplit_payment_back_to_single_account(): void
    {
        // Buat transaksi split payment
        $tx = $this->transactionService->recordExpense($this->space, $this->user, [
            'transaction_date' => '2026-11-16',
            'description' => 'Makan Malam Split Payment',
            'amount' => 300000,
            'category_id' => $this->foodCategory->id,
            'is_split_payment' => true,
            'payments' => [
                [
                    'account_id' => $this->bcaAccount->id,
                    'amount' => 200000,
                ],
                [
                    'account_id' => $this->cashAccount->id,
                    'amount' => 100000,
                ],
            ],
        ]);

        $this->assertTrue($tx->fresh()->isSplitPayment());

        // Batalkan split payment dan alihkan seluruh pembayaran ke BCA
        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->post(route('transactions.unsplit-payment', $tx), [
                'target_account_id' => $this->bcaAccount->id,
            ]);

        $response->assertSessionHasNoErrors();

        $tx->refresh();
        $this->assertFalse($tx->isSplitPayment());
        $this->assertCount(1, $tx->entries);
        $this->assertEquals($this->bcaAccount->id, $tx->entries->first()->account_id);
        $this->assertEquals(-300000, $tx->entries->first()->amount_signed);

        // Saldo BCA dipotong 300.000, saldo Cash kembali utuh (1.000.000)
        $this->assertEquals(5000000 - 300000, $this->bcaAccount->fresh()->currentBalance());
        $this->assertEquals(1000000, $this->cashAccount->fresh()->currentBalance());
    }

    public function test_it_supports_both_split_payment_and_split_category_simultaneously(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'transaction_date' => '2026-11-16',
                'description' => 'Belanja Bulanan Gabungan Multi-Kategori & Multi-Rekening',
                'amount' => 700000,
                'is_split' => 1,
                'splits' => [
                    [
                        'category_id' => $this->foodCategory->id,
                        'amount' => 450000,
                        'notes' => 'Bahan pokok',
                    ],
                    [
                        'category_id' => $this->shoppingCategory->id,
                        'amount' => 250000,
                        'notes' => 'Alat rumah tangga',
                    ],
                ],
                'is_split_payment' => 1,
                'payments' => [
                    [
                        'account_id' => $this->bcaAccount->id,
                        'amount' => 500000,
                    ],
                    [
                        'account_id' => $this->cashAccount->id,
                        'amount' => 200000,
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();

        $tx = FinancialTransaction::query()
            ->where('description', 'Belanja Bulanan Gabungan Multi-Kategori & Multi-Rekening')
            ->first();

        $this->assertNotNull($tx);
        $this->assertTrue($tx->isSplit());
        $this->assertTrue($tx->isSplitPayment());
        $this->assertCount(2, $tx->splits);
        $this->assertCount(2, $tx->entries);
        $this->assertEquals(700000, $tx->totalAmount());
        $this->assertEquals(700000.0, $tx->splitTotal());
        $this->assertEquals(700000.0, $tx->splitPaymentTotal());
    }

    public function test_voiding_split_payment_transaction_restores_all_account_balances(): void
    {
        $tx = $this->transactionService->recordExpense($this->space, $this->user, [
            'transaction_date' => '2026-11-16',
            'description' => 'Batal Beli Barang',
            'amount' => 400000,
            'category_id' => $this->shoppingCategory->id,
            'is_split_payment' => true,
            'payments' => [
                [
                    'account_id' => $this->bcaAccount->id,
                    'amount' => 250000,
                ],
                [
                    'account_id' => $this->gopayAccount->id,
                    'amount' => 150000,
                ],
            ],
        ]);

        $this->assertEquals(5000000 - 250000, $this->bcaAccount->fresh()->currentBalance());
        $this->assertEquals(500000 - 150000, $this->gopayAccount->fresh()->currentBalance());

        // Lakukan void transaksi
        $this->transactionService->voidTransaction($tx, $this->user, 'Salah input transaksi');

        $this->assertTrue($tx->fresh()->isVoid());
        // Saldo kedua akun kembali utuh
        $this->assertEquals(5000000, $this->bcaAccount->fresh()->currentBalance());
        $this->assertEquals(500000, $this->gopayAccount->fresh()->currentBalance());
    }

    public function test_it_can_update_transaction_with_split_payments_via_edit_route(): void
    {
        $tx = $this->transactionService->recordExpense($this->space, $this->user, [
            'transaction_date' => '2026-11-16',
            'description' => 'Makan Bersama Tim Awal',
            'amount' => 500000,
            'category_id' => $this->foodCategory->id,
            'account_id' => $this->bcaAccount->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->put(route('transactions.update', $tx), [
                'type' => 'expense',
                'version' => $tx->version,
                'transaction_date' => '2026-11-16',
                'description' => 'Makan Bersama Tim (Revisi Split)',
                'amount' => 500000,
                'category_id' => $this->foodCategory->id,
                'is_split_payment' => 1,
                'payments' => [
                    [
                        'account_id' => $this->bcaAccount->id,
                        'amount' => 300000,
                    ],
                    [
                        'account_id' => $this->cashAccount->id,
                        'amount' => 200000,
                    ],
                ],
                'reason' => 'Bagi pembayaran dengan uang tunai',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('transactions.show', $tx));

        $tx->refresh();
        $this->assertTrue($tx->isSplitPayment());
        $this->assertCount(2, $tx->entries);
        $this->assertEquals(5000000 - 300000, $this->bcaAccount->fresh()->currentBalance());
        $this->assertEquals(1000000 - 200000, $this->cashAccount->fresh()->currentBalance());
    }

    public function test_it_can_switch_split_payment_back_to_single_account_via_edit_route(): void
    {
        $tx = $this->transactionService->recordExpense($this->space, $this->user, [
            'transaction_date' => '2026-11-16',
            'description' => 'Belanja Supermarket Split',
            'amount' => 400000,
            'category_id' => $this->shoppingCategory->id,
            'is_split_payment' => true,
            'payments' => [
                [
                    'account_id' => $this->bcaAccount->id,
                    'amount' => 250000,
                ],
                [
                    'account_id' => $this->cashAccount->id,
                    'amount' => 150000,
                ],
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->put(route('transactions.update', $tx), [
                'type' => 'expense',
                'version' => $tx->version,
                'transaction_date' => '2026-11-16',
                'description' => 'Belanja Supermarket (Diubah Tunggal ke BCA)',
                'amount' => 400000,
                'category_id' => $this->shoppingCategory->id,
                'account_id' => $this->bcaAccount->id,
                'is_split_payment' => 0,
                'reason' => 'Dibayar penuh pakai debit BCA',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('transactions.show', $tx));

        $tx->refresh();
        $this->assertFalse($tx->isSplitPayment());
        $this->assertCount(1, $tx->entries);
        $this->assertEquals($this->bcaAccount->id, $tx->entries->first()->account_id);
        $this->assertEquals(5000000 - 400000, $this->bcaAccount->fresh()->currentBalance());
        $this->assertEquals(1000000, $this->cashAccount->fresh()->currentBalance());
    }
}
