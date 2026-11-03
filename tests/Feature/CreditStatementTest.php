<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\Space;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditStatementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Space $space;

    protected Account $bankAccount;

    protected Category $category;

    protected CreditAccount $creditCard;

    protected CreditService $creditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->space = Space::create([
            'name' => 'Ruang Pribadi',
            'type' => 'personal',
            'owner_id' => $this->user->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->space->spaceMembers()->create([
            'user_id' => $this->user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->bankAccount = Account::create([
            'space_id' => $this->space->id,
            'name' => 'Bank Mandiri',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
            'is_default' => true,
        ]);

        $this->category = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Elektronik & Gadget',
            'type' => 'expense',
        ]);

        $this->creditService = app(CreditService::class);

        $this->creditCard = $this->creditService->createCreditAccount($this->space, [
            'name' => 'BCA Card Visa',
            'type' => 'credit_card',
            'credit_limit' => 20000000,
            'billing_cycle_day' => 15,
            'due_day' => 5,
        ]);
    }

    public function test_statement_bill_consolidates_multiple_installments_in_current_month(): void
    {
        $currentMonth = now()->format('Y-m');

        // Belanja 1: Laptop Rp 6.000.000 tenor 6 bulan (cicilan 1.000.000/bln)
        $plan1 = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Laptop',
            'total_amount' => 6000000,
            'tenor' => 6,
            'start_date' => now()->toDateString(),
            'first_due_date' => now()->toDateString(),
        ]);

        // Belanja 2: HP Rp 1.802.000 tenor 12 bulan (#1 = 150.174, #2..#12 = 150.166)
        $plan2 = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli HP',
            'total_amount' => 1802000,
            'tenor' => 12,
            'start_date' => now()->toDateString(),
            'first_due_date' => now()->toDateString(),
        ]);

        // Ambil statement bulan ini
        $statement = $this->creditCard->getStatementBill($currentMonth);

        $this->assertEquals(2, $statement['item_count']);
        $this->assertEquals(2, $statement['pending_count']);
        // 1.000.000 + 150.174 = 1.150.174
        $this->assertEquals(1150174, $statement['total_billed']);
        $this->assertEquals(1150174, $statement['total_pending']);
        $this->assertFalse($statement['is_fully_paid']);
    }

    public function test_pay_monthly_statement_in_single_transaction(): void
    {
        $currentMonth = now()->format('Y-m');

        // 2 Belanja
        $plan1 = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Monitor',
            'total_amount' => 2000000,
            'tenor' => 4, // 500.000 / bln
            'start_date' => now()->toDateString(),
            'first_due_date' => now()->toDateString(),
        ]);

        $plan2 = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Smartwatch',
            'total_amount' => 600000,
            'tenor' => 3, // 200.000 / bln
            'start_date' => now()->toDateString(),
            'first_due_date' => now()->toDateString(),
        ]);

        $statement = $this->creditCard->getStatementBill($currentMonth);
        $this->assertEquals(700000, $statement['total_pending']);

        $pendingIds = $statement['installments']->pluck('id')->all();
        $this->assertCount(2, $pendingIds);

        $initialBankBalance = $this->bankAccount->currentBalance();

        // Bayar lembar tagihan sekaligus via HTTP route
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.accounts.payBill', $this->creditCard), [
                'source_account_id' => $this->bankAccount->id,
                'amount' => 700000,
                'payment_date' => now()->toDateString(),
                'installment_ids' => $pendingIds,
                'redirect_to' => 'statement',
                'month' => $currentMonth,
            ]);

        $response->assertRedirect(route('credit.accounts.statement', [
            'creditAccount' => $this->creditCard,
            'month' => $currentMonth,
        ]));

        // Saldo bank terpotong persis 1 kali
        $this->assertEquals($initialBankBalance - 700000, $this->bankAccount->currentBalance());

        // Kedua cicilan sekarang berstatus PAID
        $inst1 = $plan1->installments()->first();
        $inst2 = $plan2->installments()->first();
        $this->assertTrue($inst1->isPaid());
        $this->assertTrue($inst2->isPaid());

        // Tagihan bulan ini sekarang berstatus LUNAS
        $statementAfter = $this->creditCard->getStatementBill($currentMonth);
        $this->assertEquals(0, $statementAfter['total_pending']);
        $this->assertEquals(700000, $statementAfter['total_paid']);
        $this->assertTrue($statementAfter['is_fully_paid']);
    }

    public function test_paying_statement_with_rounding_difference_creates_credit_note(): void
    {
        $currentMonth = now()->format('Y-m');

        // Belanja dengan selisih pembulatan: 1.802.000 / 12 = 150.174 di cicilan pertama
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Tablet',
            'total_amount' => 1802000,
            'tenor' => 12,
            'start_date' => now()->toDateString(),
            'first_due_date' => now()->toDateString(),
        ]);

        $statement = $this->creditCard->getStatementBill($currentMonth);
        $this->assertEquals(150174, $statement['total_pending']);

        // Bayar sesuai m-banking ril: 150.166 (ada selisih 8 rupiah)
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.accounts.payBill', $this->creditCard), [
                'source_account_id' => $this->bankAccount->id,
                'amount' => 150166,
                'payment_date' => now()->toDateString(),
                'installment_ids' => $statement['installments']->pluck('id')->all(),
            ]);

        $response->assertRedirect(route('credit.index', ['tab' => 'accounts']));

        // Cicilan lunas
        $firstInst = $plan->installments()->first();
        $this->assertTrue($firstInst->isPaid());

        // Terbentuk nota kredit sebesar selisih 8
        $this->assertDatabaseHas('credit_debit_notes', [
            'credit_account_id' => $this->creditCard->id,
            'type' => 'credit_note',
            'amount' => 8,
        ]);
    }

    public function test_statement_view_http_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('credit.accounts.statement', $this->creditCard));

        $response->assertStatus(200);
        $response->assertSee('Lembar Tagihan');
        $response->assertSee('BCA Card Visa');
    }
}
