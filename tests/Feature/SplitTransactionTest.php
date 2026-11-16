<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\Space;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private Space $space;

    private Space $otherSpace;

    private Account $bankAccount;

    private Category $generalCategory;

    private Category $foodCategory;

    private Category $personalCategory;

    private Member $memberMe;

    private Member $memberFamily;

    private TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Gilan Tester',
            'email' => 'gilan@example.com',
        ]);

        $this->space = Space::create([
            'name' => 'Keluarga Gilan',
            'type' => 'family',
            'owner_id' => $this->user->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->space->members()->attach($this->user->id, ['role' => 'owner', 'status' => 'active']);

        $this->otherUser = User::factory()->create([
            'name' => 'Stranger',
            'email' => 'stranger@example.com',
        ]);
        $this->otherSpace = Space::create([
            'name' => 'Ruang Lain',
            'type' => 'personal',
            'owner_id' => $this->otherUser->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->otherSpace->members()->attach($this->otherUser->id, ['role' => 'owner', 'status' => 'active']);

        $this->bankAccount = Account::create([
            'space_id' => $this->space->id,
            'name' => 'BCA Utama',
            'type' => 'bank',
            'currency' => 'IDR',
            'opening_balance' => 5000000,
            'opened_on' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->generalCategory = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Belanja Supermarket',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->foodCategory = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Bahan Makanan & Dapur',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->personalCategory = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Kebutuhan Pribadi',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->memberMe = Member::create([
            'space_id' => $this->space->id,
            'name' => 'Gilan',
            'role' => 'me',
            'color' => '#0f4c3a',
            'is_active' => true,
        ]);

        $this->memberFamily = Member::create([
            'space_id' => $this->space->id,
            'name' => 'Keluarga',
            'role' => 'family',
            'color' => '#d97706',
            'is_active' => true,
        ]);

        $this->transactionService = app(TransactionService::class);
    }

    public function test_it_can_split_an_existing_expense_transaction_into_multiple_categories_and_members(): void
    {
        $transaction = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bankAccount->id,
            'category_id' => $this->generalCategory->id,
            'amount' => 300000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Struk Belanja Supermarket Bulanan',
        ]);

        $this->assertFalse($transaction->isSplit());
        $this->assertCount(0, $transaction->splits);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('transactions.split', $transaction), [
                'splits' => [
                    [
                        'category_id' => $this->foodCategory->id,
                        'member_id' => $this->memberFamily->id,
                        'amount' => 200000,
                        'notes' => 'Bahan masakan & sembako rumah',
                    ],
                    [
                        'category_id' => $this->personalCategory->id,
                        'member_id' => $this->memberMe->id,
                        'amount' => 100000,
                        'notes' => 'Snack dan sabun cuci muka',
                    ],
                ],
            ]);

        $response->assertRedirect(route('transactions.show', $transaction));
        $response->assertSessionHas('success');

        $transaction->refresh();
        $this->assertTrue($transaction->isSplit());
        $this->assertCount(2, $transaction->splits);
        $this->assertEquals(300000.0, $transaction->splitTotal());
        $this->assertTrue($transaction->isSplitBalanced());

        $split1 = $transaction->splits->firstWhere('category_id', $this->foodCategory->id);
        $this->assertNotNull($split1);
        $this->assertEquals(200000.0, (float) $split1->amount);
        $this->assertEquals($this->memberFamily->id, $split1->member_id);
        $this->assertEquals('Bahan masakan & sembako rumah', $split1->notes);

        $split2 = $transaction->splits->firstWhere('category_id', $this->personalCategory->id);
        $this->assertNotNull($split2);
        $this->assertEquals(100000.0, (float) $split2->amount);
        $this->assertEquals($this->memberMe->id, $split2->member_id);
    }

    public function test_it_fails_validation_when_split_allocations_sum_does_not_equal_transaction_total(): void
    {
        $transaction = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bankAccount->id,
            'category_id' => $this->generalCategory->id,
            'amount' => 300000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Belanja Supermarket',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->from(route('transactions.show', $transaction))
            ->post(route('transactions.split', $transaction), [
                'splits' => [
                    [
                        'category_id' => $this->foodCategory->id,
                        'amount' => 200000,
                    ],
                    [
                        'category_id' => $this->personalCategory->id,
                        'amount' => 50000, // Total 250.000 vs 300.000 -> Kurang 50.000
                    ],
                ],
            ]);

        $response->assertRedirect(route('transactions.show', $transaction));
        $response->assertSessionHasErrors(['splits']);

        $transaction->refresh();
        $this->assertFalse($transaction->isSplit());
        $this->assertCount(0, $transaction->splits);
    }

    public function test_it_fails_validation_with_less_than_two_split_rows(): void
    {
        $transaction = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bankAccount->id,
            'category_id' => $this->generalCategory->id,
            'amount' => 150000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Belanja Minimarket',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('transactions.split', $transaction), [
                'splits' => [
                    [
                        'category_id' => $this->foodCategory->id,
                        'amount' => 150000,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors(['splits']);
        $transaction->refresh();
        $this->assertFalse($transaction->isSplit());
    }

    public function test_it_can_unsplit_a_split_transaction_and_restore_single_category(): void
    {
        $transaction = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bankAccount->id,
            'category_id' => $this->generalCategory->id,
            'amount' => 100000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Makan & Snack',
        ]);

        $this->transactionService->splitTransaction($transaction, $this->user, [
            ['category_id' => $this->foodCategory->id, 'amount' => 60000],
            ['category_id' => $this->personalCategory->id, 'amount' => 40000],
        ]);

        $this->assertTrue($transaction->fresh()->isSplit());

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('transactions.unsplit', $transaction), [
                'fallback_category_id' => $this->foodCategory->id,
            ]);

        $response->assertRedirect(route('transactions.show', $transaction));
        $response->assertSessionHas('success');

        $transaction->refresh();
        $this->assertFalse($transaction->isSplit());
        $this->assertCount(0, $transaction->splits);
        $this->assertEquals($this->foodCategory->id, $transaction->category_id);
    }

    public function test_it_correctly_impacts_category_budgets_without_double_counting(): void
    {
        $year = (int) now()->format('Y');
        $month = (int) now()->format('n');

        $foodBudget = Budget::create([
            'space_id' => $this->space->id,
            'category_id' => $this->foodCategory->id,
            'year' => $year,
            'month' => $month,
            'limit_amount' => 500000,
        ]);

        $personalBudget = Budget::create([
            'space_id' => $this->space->id,
            'category_id' => $this->personalCategory->id,
            'year' => $year,
            'month' => $month,
            'limit_amount' => 300000,
        ]);

        // Awalnya spent = 0
        $this->assertEquals(0, $foodBudget->spentAmount());
        $this->assertEquals(0, $personalBudget->spentAmount());

        // Catat satu transaksi struk Rp 250.000 dengan kategori umum (bukan food dan bukan personal)
        $tx = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bankAccount->id,
            'category_id' => $this->generalCategory->id,
            'amount' => 250000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Belanja Campuran',
        ]);

        // Belum di-split, foodBudget dan personalBudget masih 0
        $this->assertEquals(0, $foodBudget->spentAmount());
        $this->assertEquals(0, $personalBudget->spentAmount());

        // Sekarang kita split: Food = 175.000, Personal = 75.000
        $this->transactionService->splitTransaction($tx, $this->user, [
            ['category_id' => $this->foodCategory->id, 'amount' => 175000],
            ['category_id' => $this->personalCategory->id, 'amount' => 75000],
        ]);

        // Verifikasi spent budget masing-masing kategori
        $this->assertEquals(175000, $foodBudget->spentAmount());
        $this->assertEquals(75000, $personalBudget->spentAmount());
        $this->assertEquals(325000, $foodBudget->remainingAmount());
        $this->assertEquals(225000, $personalBudget->remainingAmount());
    }

    public function test_it_can_record_a_new_transaction_with_splits_directly_on_create(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'account_id' => $this->bankAccount->id,
                'amount' => 200000,
                'transaction_date' => now()->toDateString(),
                'description' => 'Belanja Langsung Split',
                'is_split' => 1,
                'splits' => [
                    [
                        'category_id' => $this->foodCategory->id,
                        'amount' => 120000,
                        'member_id' => $this->memberFamily->id,
                        'notes' => 'Kebutuhan beras',
                    ],
                    [
                        'category_id' => $this->personalCategory->id,
                        'amount' => 80000,
                        'member_id' => $this->memberMe->id,
                        'notes' => 'Kopi bubuk',
                    ],
                ],
            ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success');

        $tx = FinancialTransaction::where('space_id', $this->space->id)
            ->where('description', 'Belanja Langsung Split')
            ->first();

        $this->assertNotNull($tx);
        $this->assertTrue($tx->isSplit());
        $this->assertCount(2, $tx->splits);
        $this->assertEquals(200000.0, $tx->splitTotal());
    }

    public function test_it_forbids_unauthorized_user_from_splitting_transaction(): void
    {
        $transaction = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bankAccount->id,
            'category_id' => $this->generalCategory->id,
            'amount' => 100000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Makan Siang',
        ]);

        $response = $this->actingAs($this->otherUser)
            ->withSession(['active_space_id' => $this->otherSpace->id])
            ->post(route('transactions.split', $transaction), [
                'splits' => [
                    ['category_id' => $this->foodCategory->id, 'amount' => 50000],
                    ['category_id' => $this->personalCategory->id, 'amount' => 50000],
                ],
            ]);

        $response->assertForbidden();
    }

    public function test_it_displays_split_badges_in_index_and_split_table_in_show_page(): void
    {
        $tx = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bankAccount->id,
            'category_id' => $this->generalCategory->id,
            'amount' => 150000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Belanja Superindo',
        ]);

        $this->transactionService->splitTransaction($tx, $this->user, [
            ['category_id' => $this->foodCategory->id, 'amount' => 90000, 'notes' => 'Sayuran'],
            ['category_id' => $this->personalCategory->id, 'amount' => 60000, 'notes' => 'Sabun'],
        ]);

        $indexResponse = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('transactions.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Pisah (2)');

        $showResponse = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('transactions.show', $tx));

        $showResponse->assertOk();
        $showResponse->assertSee('RINCIAN PEMISAHAN TRANSAKSI');
        $showResponse->assertSee('Bahan Makanan &amp; Dapur', false);
        $showResponse->assertSee('Kebutuhan Pribadi');
        $showResponse->assertSee('Sayuran');
        $showResponse->assertSee('Sabun');
        $showResponse->assertSee('Kelola Alokasi');
        $showResponse->assertSee('Batalkan Split');
    }
}
