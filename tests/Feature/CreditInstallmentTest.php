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
}
