<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Space;
use App\Models\User;
use App\Services\CreditService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreditInstallmentTest extends TestCase
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
            'opening_balance' => 5000000,
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
            'name' => 'BCA Everyday Card',
            'type' => 'credit_card',
            'credit_limit' => 10000000,
            'billing_cycle_day' => 15,
            'due_day' => 5,
        ]);
    }

    public function test_example_1_2_million_purchase_in_three_installments(): void
    {
        $initialCashBalance = $this->space->totalBalance();
        $this->assertEquals(5000000, $initialCashBalance);

        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Laptop Kerja',
            'total_amount' => 1200000,
            'tenor' => 3,
            'start_date' => '2026-11-03',
        ]);

        $this->assertInstanceOf(InstallmentPlan::class, $plan);
        $this->assertEquals(1200000, $plan->total_amount);
        $this->assertEquals(3, $plan->tenor);
        $this->assertEquals('active', $plan->status);

        $this->assertDatabaseHas('financial_transactions', [
            'id' => $plan->financial_transaction_id,
            'type' => 'expense',
            'category_id' => $this->category->id,
            'status' => 'posted',
        ]);

        $purchaseTx = FinancialTransaction::find($plan->financial_transaction_id);
        $this->assertEquals(1200000, $purchaseTx->totalAmount());

        $this->assertEquals(5000000, $this->space->totalBalance());

        $this->assertEquals(1200000, $this->creditCard->currentLiability());
        $this->assertEquals(8800000, $this->creditCard->availableLimit());

        $installments = $plan->installments()->get();
        $this->assertCount(3, $installments);

        foreach ($installments as $inst) {
            $this->assertEquals(400000, $inst->principal_amount);
            $this->assertEquals('pending', $inst->status);
        }

        $inst1 = $installments[0];
        $payment1 = $this->creditService->payInstallment($this->space, $this->user, $inst1, [
            'source_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-12-05',
        ]);

        $this->assertNotNull($payment1);
        $inst1->refresh();
        $this->assertTrue($inst1->isPaid());
        $this->assertNotNull($inst1->paid_at);

        $this->assertEquals(4600000, $this->bankAccount->currentBalance());
        $this->assertEquals(4600000, $this->space->totalBalance());

        $this->assertEquals(800000, $this->creditCard->currentLiability());
        $this->assertEquals(9200000, $this->creditCard->availableLimit());

        $expenseCount = FinancialTransaction::where('space_id', $this->space->id)
            ->where('type', 'expense')
            ->count();
        $this->assertEquals(1, $expenseCount);

        $inst2 = $installments[1];
        $this->creditService->payInstallment($this->space, $this->user, $inst2, [
            'source_account_id' => $this->bankAccount->id,
            'payment_date' => '2027-01-05',
        ]);

        $this->assertEquals(4200000, $this->space->totalBalance());
        $this->assertEquals(400000, $this->creditCard->currentLiability());
        $this->assertEquals(9600000, $this->creditCard->availableLimit());

        $inst3 = $installments[2];
        $this->creditService->payInstallment($this->space, $this->user, $inst3, [
            'source_account_id' => $this->bankAccount->id,
            'payment_date' => '2027-02-05',
        ]);

        $this->assertEquals(3800000, $this->space->totalBalance());
        $this->assertEquals(0, $this->creditCard->currentLiability());
        $this->assertEquals(10000000, $this->creditCard->availableLimit());

        $plan->refresh();
        $this->assertEquals('completed', $plan->status);
    }

    public function test_overpayment_is_rejected(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Meja Kerja',
            'total_amount' => 600000,
            'tenor' => 2,
            'start_date' => '2026-11-03',
        ]);

        $firstInstallment = $plan->installments()->first();

        $this->expectException(ValidationException::class);

        $this->creditService->payInstallment($this->space, $this->user, $firstInstallment, [
            'source_account_id' => $this->bankAccount->id,
            'amount' => 500000,
            'payment_date' => '2026-12-05',
        ]);
    }

    public function test_duplicate_payment_is_rejected(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Kursi Ergonomis',
            'total_amount' => 1000000,
            'tenor' => 2,
            'start_date' => '2026-11-03',
        ]);

        $inst = $plan->installments()->first();

        $this->creditService->payInstallment($this->space, $this->user, $inst, [
            'source_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-12-05',
        ]);

        $this->expectException(ValidationException::class);

        $this->creditService->payInstallment($this->space, $this->user, $inst, [
            'source_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-12-06',
        ]);
    }

    public function test_purchase_exceeding_credit_limit_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Motor Baru',
            'total_amount' => 25000000,
            'tenor' => 12,
            'start_date' => '2026-11-03',
        ]);
    }

    public function test_cross_space_isolation_and_authorization(): void
    {
        $otherUser = User::factory()->create();
        $otherSpace = Space::create([
            'name' => 'Ruang Orang Lain',
            'type' => 'personal',
            'owner_id' => $otherUser->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $otherSpace->spaceMembers()->create([
            'user_id' => $otherUser->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $otherAccount = Account::create([
            'space_id' => $otherSpace->id,
            'name' => 'BCA Orang Lain',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
        ]);

        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli TV Ruang Keluarga',
            'total_amount' => 3000000,
            'tenor' => 3,
            'start_date' => '2026-11-03',
        ]);

        $firstInst = $plan->installments()->first();

        $this->expectException(ValidationException::class);

        $this->creditService->payInstallment($this->space, $this->user, $firstInst, [
            'source_account_id' => $otherAccount->id,
            'payment_date' => '2026-12-05',
        ]);
    }

    public function test_voiding_credit_purchase_cancels_plan_and_voids_transaction(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Headphone Wireless',
            'total_amount' => 1500000,
            'tenor' => 3,
            'start_date' => '2026-11-03',
        ]);

        $this->assertEquals(1500000, $this->creditCard->currentLiability());

        $this->creditService->voidCreditPurchase($this->space, $this->user, $plan);

        $plan->refresh();
        $this->assertEquals('cancelled', $plan->status);
        $this->assertEquals('void', $plan->purchaseTransaction->status);

        $this->assertEquals(0, $this->creditCard->currentLiability());
        $this->assertEquals(10000000, $this->creditCard->availableLimit());
    }

    public function test_voiding_financial_transaction_cancels_associated_installment_plan_and_installments(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Langganan Chatgpt',
            'total_amount' => 349000,
            'tenor' => 1,
            'start_date' => '2026-11-03',
        ]);

        $this->assertEquals('active', $plan->status);
        $this->assertEquals('pending', $plan->installments()->first()->status);

        // Void the financial transaction directly (e.g. from /transactions)
        $tx = $plan->purchaseTransaction;
        app(TransactionService::class)->voidTransaction($tx, $this->user, 'Dibatalkan oleh pengguna');

        $plan->refresh();
        $this->assertEquals('cancelled', $plan->status);
        $this->assertEquals('cancelled', $plan->installments()->first()->status);
        $this->assertEquals(0, Installment::active()->where('installment_plan_id', $plan->id)->count());
    }

    public function test_cannot_void_plan_with_existing_payment(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Smartwatch',
            'total_amount' => 1000000,
            'tenor' => 2,
            'start_date' => '2026-11-03',
        ]);

        $inst = $plan->installments()->first();
        $this->creditService->payInstallment($this->space, $this->user, $inst, [
            'source_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-12-05',
        ]);

        $this->expectException(ValidationException::class);
        $this->creditService->voidCreditPurchase($this->space, $this->user, $plan);
    }

    public function test_http_routes_and_views(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('credit.index'));

        $response->assertStatus(200);
        $response->assertSee('BCA Everyday Card');
        $response->assertSee('Kredit & Cicilan');

        $responseCreate = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('credit.purchases.create'));

        $responseCreate->assertStatus(200);
        $responseCreate->assertSee('Catat Belanja Cicilan');

        $storeResponse = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.purchases.store'), [
                'credit_account_id' => $this->creditCard->id,
                'category_id' => $this->category->id,
                'name' => 'Beli Sepeda Lipat',
                'total_amount' => 2400000,
                'tenor' => 6,
                'start_date' => '2026-11-03',
            ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('installment_plans', [
            'name' => 'Beli Sepeda Lipat',
            'total_amount' => 2400000,
            'tenor' => 6,
        ]);
    }

    public function test_credit_purchase_with_interest_and_admin_fee(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Gadget dengan Bunga dan Admin',
            'total_amount' => 1200000,
            'tenor' => 3,
            'interest_rate' => 2.0, // 2% per bulan = 24.000 / bln
            'admin_fee' => 15000, // 15.000 total = 5.000 / bln
            'start_date' => '2026-11-03',
        ]);

        $this->assertEquals(1200000, $plan->total_amount);
        $this->assertEquals(2.0, $plan->interest_rate);
        $this->assertEquals(15000, $plan->admin_fee);
        $this->assertTrue($plan->hasInterestOrFees());
        $this->assertEquals(72000, $plan->totalInterest()); // 24.000 * 3
        $this->assertEquals(15000, $plan->totalFees());
        $this->assertEquals(1287000, $plan->grandTotalAmount()); // 1.200.000 + 72.000 + 15.000

        $firstInst = $plan->installments()->first();
        $this->assertEquals(400000, $firstInst->principal_amount);
        $this->assertEquals(24000, $firstInst->interest_amount);
        $this->assertEquals(5000, $firstInst->fee_amount);
        $this->assertEquals(429000, $firstInst->total_amount);
    }

    public function test_credit_purchase_supports_up_to_five_decimal_places_interest_rate(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Gadget Bunga 5 Digit Koma',
            'total_amount' => 1000000,
            'tenor' => 3,
            'interest_rate' => 2.45125, // 5 digit di belakang koma
            'start_date' => '2026-11-03',
        ]);

        $this->assertEquals('2.45125', (string) $plan->interest_rate);
        $this->assertEquals('2,45125%', $plan->formatted_interest_rate);
        $this->assertEquals(24512.50, $plan->installments()->first()->interest_amount);
        $this->assertEquals(73537.50, $plan->totalInterest());

        // Test via HTTP POST with Indonesian comma format and 3-5 decimals
        $responseDot = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.purchases.store'), [
                'credit_account_id' => $this->creditCard->id,
                'category_id' => $this->category->id,
                'name' => 'Beli Barang Bunga 2.451',
                'total_amount' => 124753,
                'tenor' => 12,
                'interest_rate' => '2.451',
                'start_date' => '2026-11-03',
            ]);

        $responseDot->assertRedirect();
        $this->assertDatabaseHas('installment_plans', [
            'name' => 'Beli Barang Bunga 2.451',
            'interest_rate' => 2.451,
        ]);

        $responseComma = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.purchases.store'), [
                'credit_account_id' => $this->creditCard->id,
                'category_id' => $this->category->id,
                'name' => 'Beli Barang Bunga 2,45125',
                'total_amount' => 2000000,
                'tenor' => 6,
                'interest_rate' => '2,45125',
                'start_date' => '2026-11-03',
            ]);

        $responseComma->assertRedirect();
        $this->assertDatabaseHas('installment_plans', [
            'name' => 'Beli Barang Bunga 2,45125',
            'interest_rate' => 2.45125,
        ]);
    }

    public function test_early_payoff_settles_all_installments_and_completes_plan(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Tablet Pelunasan Dipercepat',
            'total_amount' => 1500000,
            'tenor' => 3,
            'start_date' => '2026-11-03',
        ]);

        $this->assertEquals(3, $plan->pendingInstallments()->count());
        $this->assertEquals(1500000, $this->creditCard->currentLiability());

        $payoffResult = $this->creditService->payoffPlan($this->space, $this->user, $plan, [
            'source_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-12-05',
        ]);

        $this->assertEquals(3, $payoffResult['settled_count']);
        $this->assertEquals(1500000, $payoffResult['total_paid']);

        $plan->refresh();
        $this->assertTrue($plan->isCompleted());
        $this->assertEquals(0, $plan->pendingInstallments()->count());
        $this->assertEquals(3, $plan->paidInstallments()->count());
        $this->assertEquals(0, $this->creditCard->currentLiability());
        $this->assertEquals(3500000, $this->bankAccount->currentBalance()); // 5jt - 1.5jt
    }

    public function test_direct_bill_payment_reduces_credit_liability(): void
    {
        $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Monitor 4K',
            'total_amount' => 2000000,
            'tenor' => 4,
            'start_date' => '2026-11-03',
        ]);

        $this->assertEquals(2000000, $this->creditCard->currentLiability());

        // Bayar tagihan kartu 1.000.000 (menutup 2 cicilan @500.000)
        $payment = $this->creditService->payCreditAccountBill($this->space, $this->user, $this->creditCard, [
            'source_account_id' => $this->bankAccount->id,
            'amount' => 1000000,
            'payment_date' => '2026-12-05',
            'notes' => 'Bayar tagihan parsial kartu kredit',
        ]);

        $this->assertNotNull($payment);
        $this->assertEquals(1000000, $this->creditCard->currentLiability());
        $this->assertEquals(4000000, $this->bankAccount->currentBalance());

        // Sisa 2 cicilan pending
        $remainingPending = Installment::where('space_id', $this->space->id)
            ->where('status', 'pending')
            ->count();
        $this->assertEquals(2, $remainingPending);
    }

    public function test_installment_due_status_and_helpers(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Keyboard Mekanikal',
            'total_amount' => 900000,
            'tenor' => 3,
            'start_date' => '2026-11-03',
        ]);

        $inst = $plan->installments()->first();
        $this->assertFalse($inst->isPaid());
        $this->assertTrue($inst->isPending());

        // Test Overdue logic
        $inst->update(['due_date' => now()->subDays(5)->toDateString()]);
        $this->assertTrue($inst->isOverdue());
        $this->assertEquals('overdue', $inst->dueStatus());
        $this->assertStringContainsString('Terlambat 5 hari', $inst->dueStatusLabel());
        $this->assertStringContainsString('TERLAMBAT', $inst->whatsAppReminderMessage());

        // Test WhatsApp URL generator
        $url = $inst->whatsAppReminderUrl('08123456789');
        $this->assertStringContainsString('628123456789', $url);

        // Test Utilization Rate on CreditAccount
        $this->assertEquals(9.0, $this->creditCard->utilizationRate()); // 900.000 / 10.000.000 = 9%
        $this->assertEquals('safe', $this->creditCard->utilizationStatus());
    }

    public function test_http_bill_pay_and_payoff_routes(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Speaker Bluetooth',
            'total_amount' => 600000,
            'tenor' => 2,
            'start_date' => '2026-11-03',
        ]);

        // Test Payoff HTTP Route
        $payoffResponse = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.plans.payoff', $plan), [
                'source_account_id' => $this->bankAccount->id,
                'payment_date' => '2026-12-05',
            ]);

        $payoffResponse->assertRedirect(route('credit.plans.show', $plan));
        $plan->refresh();
        $this->assertTrue($plan->isCompleted());

        // Test Pay Bill HTTP Route on new purchase
        $plan2 = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Mouse Wireless',
            'total_amount' => 400000,
            'tenor' => 2,
            'start_date' => '2026-11-03',
        ]);

        $billPayResponse = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.accounts.payBill', $this->creditCard), [
                'source_account_id' => $this->bankAccount->id,
                'amount' => 400000,
                'payment_date' => '2026-12-05',
            ]);

        $billPayResponse->assertRedirect(route('credit.index', ['tab' => 'accounts']));
        $this->assertEquals(0, $this->creditCard->currentLiability());
    }

    public function test_paying_bill_exceeding_liability_is_rejected(): void
    {
        $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Powerbank',
            'total_amount' => 300000,
            'tenor' => 2,
            'start_date' => '2026-11-03',
        ]);

        $this->expectException(ValidationException::class);

        $this->creditService->payCreditAccountBill($this->space, $this->user, $this->creditCard, [
            'source_account_id' => $this->bankAccount->id,
            'amount' => 500000, // melebihi 300.000
            'payment_date' => '2026-12-05',
        ]);
    }

    public function test_payoff_completed_plan_is_rejected(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Kabel Type-C',
            'total_amount' => 100000,
            'tenor' => 1,
            'start_date' => '2026-11-03',
        ]);

        $this->creditService->payoffPlan($this->space, $this->user, $plan, [
            'source_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-12-05',
        ]);

        $this->expectException(ValidationException::class);

        // Pelunasan kedua pada plan yang sudah completed
        $this->creditService->payoffPlan($this->space, $this->user, $plan, [
            'source_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-12-06',
        ]);
    }

    public function test_mark_installment_paid_without_cash_does_not_deduct_bank_balance(): void
    {
        $initialBankBalance = $this->bankAccount->currentBalance();

        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Blender Portable',
            'total_amount' => 500000,
            'tenor' => 5,
            'start_date' => '2026-06-01',
        ]);

        $this->assertEquals(500000, $this->creditCard->currentLiability());

        $firstInst = $plan->installments()->first();
        $this->assertTrue($firstInst->isPending());

        $this->creditService->markInstallmentPaidWithoutCash($this->space, $this->user, $firstInst, 'Lunas sebelum pakai ARUS');

        $firstInst->refresh();
        $this->assertTrue($firstInst->isPaid());
        $this->assertTrue($firstInst->isPaidWithoutCash());

        // Saldo bank TIDAK boleh berkurang sama sekali
        $this->assertEquals($initialBankBalance, $this->bankAccount->currentBalance());

        // Sisa kewajiban kartu kredit berkurang sebesar 100.000
        $this->assertEquals(400000, $this->creditCard->currentLiability());
        $this->assertEquals(9600000, $this->creditCard->availableLimit());
    }

    public function test_mark_overdue_installments_paid_without_cash_bulk(): void
    {
        $initialBankBalance = $this->bankAccount->currentBalance();

        // Buat pembelian cicilan mundur 4 bulan lalu
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Meja Belajar',
            'total_amount' => 1000000,
            'tenor' => 5,
            'start_date' => now()->subMonths(3)->toDateString(),
            'first_due_date' => now()->subMonths(2)->toDateString(),
        ]);

        $count = $this->creditService->markOverdueInstallmentsPaidWithoutCash($this->space, $this->user, $plan);

        $this->assertGreaterThan(0, $count);
        $this->assertEquals($initialBankBalance, $this->bankAccount->currentBalance());

        // Cicilan lampau berstatus lunas
        $paidCount = $plan->installments()->where('status', 'paid')->count();
        $this->assertEquals($count, $paidCount);
    }

    public function test_reset_unmark_paid_installment_back_to_pending(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Kursi Ergonomis',
            'total_amount' => 600000,
            'tenor' => 3,
            'start_date' => '2026-08-01',
        ]);

        $inst = $plan->installments()->first();
        $this->creditService->markInstallmentPaidWithoutCash($this->space, $this->user, $inst);

        $inst->refresh();
        $this->assertTrue($inst->isPaid());

        // Reset kembali ke pending
        $this->creditService->resetInstallmentToPending($this->space, $this->user, $inst);

        $inst->refresh();
        $this->assertTrue($inst->isPending());
        $this->assertNull($inst->paid_at);
        $this->assertNull($inst->payment_transaction_id);
    }

    public function test_http_endpoints_for_mark_paid_without_cash(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Lampu Meja',
            'total_amount' => 300000,
            'tenor' => 3,
            'start_date' => '2026-07-01',
        ]);

        $inst = $plan->installments()->first();

        // 1. Mark single paid without cash
        $responseSingle = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.installments.markPaidWithoutCash', $inst), [
                'notes' => 'Pra-pencatatan m-banking',
            ]);

        $responseSingle->assertRedirect(route('credit.plans.show', $plan));
        $inst->refresh();
        $this->assertTrue($inst->isPaid());

        // 2. Reset back to pending
        $responseReset = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.installments.resetToPending', $inst));

        $responseReset->assertRedirect(route('credit.plans.show', $plan));
        $inst->refresh();
        $this->assertTrue($inst->isPending());

        // 3. Mark overdue paid bulk
        $responseBulk = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.plans.markOverduePaidWithoutCash', $plan));

        $responseBulk->assertRedirect(route('credit.plans.show', $plan));

        // 4. View show plan and check ordering
        $showResponse = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('credit.plans.show', $plan));

        $showResponse->assertStatus(200);
        $showResponse->assertSee('Cicilan yang Sudah Lunas');
    }

    public function test_flexible_payment_absorbs_rounding_difference_as_credit_note(): void
    {
        // Pembelian 1.802.000 tenor 12: cicilan #1 = 150.174, cicilan #2..#12 = 150.166
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Handphone Baru',
            'total_amount' => 1802000,
            'tenor' => 12,
            'start_date' => '2026-11-01',
        ]);

        $firstInst = $plan->installments()->where('installment_number', 1)->first();
        $this->assertEquals(150174, $firstInst->principal_amount);

        $initialBankBalance = $this->bankAccount->currentBalance();

        // Bayar cicilan #1 sesuai nominal ril di tagihan m-banking (150.166 alih-alih 150.174, selisih 8)
        $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.installments.pay', $firstInst), [
                'source_account_id' => $this->bankAccount->id,
                'amount' => 150166,
                'payment_date' => '2026-11-04',
            ])
            ->assertRedirect(route('credit.plans.show', $plan));

        $firstInst->refresh();
        $this->assertTrue($firstInst->isPaid());
        $this->assertEquals(150166, $firstInst->principal_amount);

        // Saldo bank terpotong persis nominal ril
        $this->assertEquals($initialBankBalance - 150166, $this->bankAccount->currentBalance());

        // Terbentuk nota kredit selisih pembulatan Rp 8
        $this->assertDatabaseHas('credit_debit_notes', [
            'space_id' => $this->space->id,
            'installment_id' => $firstInst->id,
            'type' => 'credit_note',
            'amount' => 8,
        ]);

        $plan->refresh();
        $this->assertEquals(1801992, $plan->total_amount);
    }

    public function test_manual_credit_and_debit_notes_endpoint(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli AC Kantor',
            'total_amount' => 1000000,
            'tenor' => 2,
            'start_date' => '2026-11-01',
        ]);

        $inst = $plan->installments()->first();
        $this->assertEquals(500000, $inst->principal_amount);

        // 1. Simpan Nota Kredit Rp 10.000 pada cicilan #1
        $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.notes.store'), [
                'credit_account_id' => $this->creditCard->id,
                'installment_plan_id' => $plan->id,
                'installment_id' => $inst->id,
                'type' => 'credit_note',
                'amount' => 10000,
                'note_date' => '2026-11-04',
                'description' => 'Diskon promo merchant cicilan #1',
            ])
            ->assertRedirect(route('credit.plans.show', $plan));

        $inst->refresh();
        $this->assertEquals(490000, $inst->principal_amount);

        $this->assertDatabaseHas('credit_debit_notes', [
            'type' => 'credit_note',
            'amount' => 10000,
            'installment_id' => $inst->id,
        ]);

        // 2. Simpan Nota Debit Rp 5.000 pada cicilan #1 (misal biaya penyesuaian)
        $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.notes.store'), [
                'credit_account_id' => $this->creditCard->id,
                'installment_plan_id' => $plan->id,
                'installment_id' => $inst->id,
                'type' => 'debit_note',
                'amount' => 5000,
                'note_date' => '2026-11-04',
                'description' => 'Biaya materai / administrasi tambahan',
            ])
            ->assertRedirect(route('credit.plans.show', $plan));

        $inst->refresh();
        $this->assertEquals(495000, $inst->principal_amount);

        $this->assertDatabaseHas('credit_debit_notes', [
            'type' => 'debit_note',
            'amount' => 5000,
            'installment_id' => $inst->id,
        ]);
    }

    public function test_decimal_input_behind_the_comma_is_normalized_and_processed(): void
    {
        // 1. Simpan pembelian dengan format desimal berkoma Indonesia "1200000,50"
        $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.purchases.store'), [
                'credit_account_id' => $this->creditCard->id,
                'category_id' => $this->category->id,
                'name' => 'Belanja Desimal',
                'total_amount' => '1.200.000,50',
                'tenor' => 2,
                'start_date' => '2026-11-01',
                'admin_fee' => '10.000,50',
            ])
            ->assertRedirect();

        $plan = InstallmentPlan::where('name', 'Belanja Desimal')->first();
        $this->assertNotNull($plan);
        $this->assertEquals(1200000.50, $plan->total_amount);
        $this->assertEquals(10000.50, $plan->admin_fee);

        $inst = $plan->installments()->where('installment_number', 1)->first();
        $this->assertNotNull($inst);

        // 2. Bayar cicilan dengan nominal koma Indonesia "600000,25"
        $initialBalance = $this->bankAccount->currentBalance();
        $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.installments.pay', $inst), [
                'source_account_id' => $this->bankAccount->id,
                'amount' => '600.000,25',
                'payment_date' => '2026-11-04',
            ])
            ->assertRedirect(route('credit.plans.show', $plan));

        $inst->refresh();
        $this->assertTrue($inst->isPaid());
        $this->assertEquals(600000.25, $inst->principal_amount);
        $this->assertEquals($initialBalance - 600000.25, $this->bankAccount->currentBalance());

        // 3. Simpan nota kredit dengan nilai desimal "7,33"
        $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('credit.notes.store'), [
                'credit_account_id' => $this->creditCard->id,
                'installment_plan_id' => $plan->id,
                'installment_id' => $plan->installments()->where('installment_number', 2)->first()->id,
                'type' => 'credit_note',
                'amount' => '7,33',
                'note_date' => '2026-11-04',
                'description' => 'Diskon pembulatan sen',
            ])
            ->assertRedirect(route('credit.plans.show', $plan));

        $this->assertDatabaseHas('credit_debit_notes', [
            'type' => 'credit_note',
            'amount' => 7.33,
        ]);
    }

    public function test_credit_account_liability_and_available_limit_include_non_installment_regular_transactions(): void
    {
        // 1. Credit card dengan limit 5.000.000
        $this->creditCard->update(['credit_limit' => 5000000]);

        // 2. Belanja cicilan 2.174.000 (tenor 1 bulan)
        $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Cicilan HP',
            'total_amount' => 2174000,
            'tenor' => 1,
            'start_date' => '2026-11-01',
        ]);

        $this->creditCard->refresh();
        $this->assertEquals(2174000, $this->creditCard->currentLiability());
        $this->assertEquals(2174000, $this->creditCard->installmentLiability());
        $this->assertEquals(0, $this->creditCard->nonInstallmentLiability());
        $this->assertEquals(2826000, $this->creditCard->availableLimit());

        // 3. Catat transaksi reguler (gesek biasa non-cicilan) Rp 47.292 langsung ke rekening kartu kredit
        $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'account_id' => $this->creditCard->account_id,
                'category_id' => $this->category->id,
                'amount' => 47292,
                'transaction_date' => '2026-11-02',
                'description' => 'Beli Bensin / Non-Cicilan',
            ])
            ->assertRedirect();

        // 4. Verifikasi sisa limit terpotong sesuai yang diharapkan user (2.778.708)
        $this->creditCard->refresh();
        $this->assertEquals(2221292, $this->creditCard->currentLiability());
        $this->assertEquals(2174000, $this->creditCard->installmentLiability());
        $this->assertEquals(47292, $this->creditCard->nonInstallmentLiability());
        $this->assertEquals(2778708, $this->creditCard->availableLimit());
        $this->assertEquals('Rp 2.778.708', $this->creditCard->formatted_available_limit);
    }

    public function test_credit_account_liability_and_available_limit_include_installment_interest_and_fee(): void
    {
        // 1. Credit card dengan limit 5.000.000
        $this->creditCard->update(['credit_limit' => 5000000]);

        // 2. Belanja cicilan pokok 2.174.000 dengan biaya/bunga 47.292
        $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Cicilan HP dengan Bunga/Biaya',
            'total_amount' => 2174000,
            'tenor' => 1,
            'admin_fee' => 47292,
            'start_date' => '2026-11-01',
        ]);

        $this->creditCard->refresh();

        // 3. Verifikasi kewajiban terpakai yang memotong limit adalah POKOK cicilan (2.174.000), sesuai regulasi perbankan
        $this->assertEquals(2174000, $this->creditCard->currentLiability());
        $this->assertEquals(2174000, $this->creditCard->installmentLiability());
        $this->assertEquals(2174000, $this->creditCard->installmentPrincipalLiability());
        $this->assertEquals(2221292, $this->creditCard->installmentTotalLiability());
        $this->assertEquals(47292, $this->creditCard->installmentInterestAndFeeLiability());
        $this->assertEquals(0, $this->creditCard->nonInstallmentLiability());

        // 4. Verifikasi sisa limit yang dapat digunakan belanja tidak dipotong bunga masa depan
        $this->assertEquals(2826000, $this->creditCard->availableLimit());
        $this->assertEquals('Rp 2.826.000', $this->creditCard->formatted_available_limit);
    }

    public function test_credit_purchase_due_date_calculates_from_billing_cycle_and_due_day(): void
    {
        $this->creditCard->update([
            'billing_cycle_day' => 20,
            'due_day' => 10,
        ]);

        $planBeforeCutoff = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Belanja Sebelum Cutoff',
            'total_amount' => 600000,
            'tenor' => 3,
            'start_date' => '2026-11-09',
        ]);

        $installments = $planBeforeCutoff->installments()->orderBy('installment_number')->get();
        $this->assertEquals('2026-12-10', $installments[0]->due_date->toDateString());
        $this->assertEquals('2027-01-10', $installments[1]->due_date->toDateString());
        $this->assertEquals('2027-02-10', $installments[2]->due_date->toDateString());

        $planAfterCutoff = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Belanja Setelah Cutoff',
            'total_amount' => 200000,
            'tenor' => 1,
            'start_date' => '2026-11-25',
        ]);

        $instAfter = $planAfterCutoff->installments()->first();
        $this->assertEquals('2027-01-10', $instAfter->due_date->toDateString());
    }

    public function test_credit_purchase_honors_explicit_first_due_date(): void
    {
        $this->creditCard->update([
            'billing_cycle_day' => 20,
            'due_day' => 10,
        ]);

        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Belanja Custom Due Date',
            'total_amount' => 500000,
            'tenor' => 2,
            'start_date' => '2026-11-09',
            'first_due_date' => '2026-12-25',
        ]);

        $installments = $plan->installments()->orderBy('installment_number')->get();
        $this->assertEquals('2026-12-25', $installments[0]->due_date->toDateString());
        $this->assertEquals('2027-01-10', $installments[1]->due_date->toDateString());
    }

    public function test_user_can_update_installment_plan_start_date(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Handphone',
            'total_amount' => 1200000,
            'tenor' => 3,
            'start_date' => '2026-11-03',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->put(route('credit.plans.updateDates', $plan), [
                'start_date' => '2026-11-10',
            ]);

        $response->assertRedirect(route('credit.plans.show', $plan));
        $response->assertSessionHas('status', 'Tanggal rencana cicilan berhasil diperbarui.');

        $this->assertEquals('2026-11-10', $plan->fresh()->start_date->toDateString());
        $this->assertEquals('2026-11-10', $plan->purchaseTransaction->fresh()->transaction_date->toDateString());
    }

    public function test_user_can_shift_due_day_for_pending_installments(): void
    {
        $this->creditCard->update(['due_day' => 25]);

        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Meja Kerja',
            'total_amount' => 600000,
            'tenor' => 3,
            'start_date' => '2026-11-01',
            'first_due_date' => '2026-11-25',
        ]);

        $installments = $plan->installments()->orderBy('installment_number')->get();
        $this->assertEquals('2026-11-25', $installments[0]->due_date->toDateString());
        $this->assertEquals('2026-12-25', $installments[1]->due_date->toDateString());
        $this->assertEquals('2027-01-25', $installments[2]->due_date->toDateString());

        // Mark installment #1 as paid without cash
        $this->creditService->markInstallmentPaidWithoutCash($this->space, $this->user, $installments[0]);

        // Shift due day to 10 for pending installments
        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->put(route('credit.plans.updateDates', $plan), [
                'due_day' => 10,
            ]);

        $response->assertRedirect(route('credit.plans.show', $plan));

        $refreshed = $plan->installments()->orderBy('installment_number')->get();
        // Cicilan #1 yang sudah lunas tidak terpengaruh
        $this->assertEquals('2026-11-25', $refreshed[0]->due_date->toDateString());
        // Cicilan #2 dan #3 yang pending bergeser ke tanggal 10
        $this->assertEquals('2026-12-10', $refreshed[1]->due_date->toDateString());
        $this->assertEquals('2027-01-10', $refreshed[2]->due_date->toDateString());
    }

    public function test_user_can_recalculate_due_dates_with_first_due_date(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Kursi Gaming',
            'total_amount' => 600000,
            'tenor' => 3,
            'start_date' => '2026-11-01',
            'first_due_date' => '2026-11-25',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->put(route('credit.plans.updateDates', $plan), [
                'first_due_date' => '2026-12-10',
            ]);

        $response->assertRedirect(route('credit.plans.show', $plan));

        $refreshed = $plan->installments()->orderBy('installment_number')->get();
        $this->assertEquals('2026-12-10', $refreshed[0]->due_date->toDateString());
        $this->assertEquals('2027-01-10', $refreshed[1]->due_date->toDateString());
        $this->assertEquals('2027-02-10', $refreshed[2]->due_date->toDateString());
    }

    public function test_user_can_update_single_installment_due_date(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Monitor',
            'total_amount' => 400000,
            'tenor' => 2,
            'start_date' => '2026-11-01',
            'first_due_date' => '2026-12-05',
        ]);

        $inst2 = $plan->installments()->where('installment_number', 2)->first();

        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->put(route('credit.installments.updateDueDate', $inst2), [
                'due_date' => '2027-01-20',
            ]);

        $response->assertRedirect(route('credit.plans.show', $plan));
        $response->assertSessionHas('status', 'Tanggal jatuh tempo cicilan ke-2 berhasil diperbarui.');

        $this->assertEquals('2027-01-20', $inst2->fresh()->due_date->toDateString());
    }

    public function test_cannot_update_due_date_for_paid_installment(): void
    {
        $plan = $this->creditService->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $this->creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Beli Keyboard',
            'total_amount' => 400000,
            'tenor' => 2,
            'start_date' => '2026-11-01',
            'first_due_date' => '2026-12-05',
        ]);

        $inst1 = $plan->installments()->where('installment_number', 1)->first();
        $this->creditService->markInstallmentPaidWithoutCash($this->space, $this->user, $inst1);

        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->put(route('credit.installments.updateDueDate', $inst1), [
                'due_date' => '2026-12-20',
            ]);

        $response->assertSessionHasErrors('due_date');
    }
}
