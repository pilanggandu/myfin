<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\Space;
use App\Models\User;
use App\Services\CreditService;
use App\Services\RecurringService;
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

    public function test_credit_index_and_statement_display_connected_recurring_schedules(): void
    {
        $recurringService = app(RecurringService::class);

        $paymentSchedule = $recurringService->createSchedule($this->space, $this->user, [
            'type' => 'credit_payment',
            'name' => 'Bayar Tagihan BCA Card',
            'credit_account_id' => $this->creditCard->id,
            'source_account_id' => $this->bankAccount->id,
            'frequency' => 'monthly',
            'start_date' => now()->addDays(5)->toDateString(),
            'is_variable_amount' => true,
        ]);

        $recurringService->createSchedule($this->space, $this->user, [
            'type' => 'expense',
            'name' => 'Langganan Netflix CC',
            'category_id' => $this->category->id,
            'source_account_id' => $this->creditCard->account_id,
            'frequency' => 'monthly',
            'start_date' => now()->addDays(3)->toDateString(),
            'amount' => 186000,
        ]);

        $archivedSchedule = $recurringService->createSchedule($this->space, $this->user, [
            'type' => 'credit_payment',
            'name' => 'Jadwal Lama Diarsipkan',
            'credit_account_id' => $this->creditCard->id,
            'source_account_id' => $this->bankAccount->id,
            'frequency' => 'monthly',
            'start_date' => now()->addDays(10)->toDateString(),
            'is_variable_amount' => true,
        ]);
        $archivedSchedule->archive();

        $unscheduledCard = $this->creditService->createCreditAccount($this->space, [
            'name' => 'Mandiri PayLater',
            'type' => 'paylater',
            'credit_limit' => 5000000,
        ]);

        $indexResp = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('credit.index', ['tab' => 'accounts']));

        $indexResp->assertOk();
        $indexResp->assertSee('Jadwal Bayar Rutin', false);
        $indexResp->assertSee('Bayar Tagihan BCA Card', false);
        $indexResp->assertSee('Terjadwal', false);
        $indexResp->assertSee('Fleksibel', false);
        $indexResp->assertSee('Langganan Netflix CC', false);
        $indexResp->assertSee(route('recurring.show', $paymentSchedule), false);
        $indexResp->assertSee('Lihat Jadwal', false);
        $indexResp->assertSee(route('recurring.create', ['type' => 'credit_payment', 'credit_account_id' => $unscheduledCard->id]));
        $indexResp->assertDontSee('Jadwal Lama Diarsipkan', false);

        $statementResp = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('credit.accounts.statement', $this->creditCard));

        $statementResp->assertOk();
        $statementResp->assertSee('Bayar Tagihan BCA Card', false);
        $statementResp->assertSee('Buka Jadwal', false);
        $statementResp->assertSee('Langganan Netflix CC', false);
        $statementResp->assertDontSee('Jadwal Lama Diarsipkan', false);
    }

    public function test_credit_index_displays_overdue_and_paused_recurring_schedule_badges(): void
    {
        $recurringService = app(RecurringService::class);

        $recurringService->createSchedule($this->space, $this->user, [
            'type' => 'credit_payment',
            'name' => 'Bayar BCA Terlambat',
            'credit_account_id' => $this->creditCard->id,
            'source_account_id' => $this->bankAccount->id,
            'frequency' => 'monthly',
            'start_date' => now()->subDays(2)->toDateString(),
            'amount' => 750000,
        ]);

        $pausedCard = $this->creditService->createCreditAccount($this->space, [
            'name' => 'Kartu Dijeda',
            'type' => 'credit_card',
            'credit_limit' => 8000000,
        ]);

        $pausedSchedule = $recurringService->createSchedule($this->space, $this->user, [
            'type' => 'credit_payment',
            'name' => 'Jadwal Bayar Dijeda',
            'credit_account_id' => $pausedCard->id,
            'source_account_id' => $this->bankAccount->id,
            'frequency' => 'monthly',
            'start_date' => now()->addDays(4)->toDateString(),
            'amount' => 500000,
        ]);
        $pausedSchedule->update(['status' => 'paused']);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('credit.index', ['tab' => 'accounts']));

        $response->assertOk();
        $response->assertSee('Bayar BCA Terlambat', false);
        $response->assertSee('Terlambat', false);
        $response->assertSee('Rp 750.000', false);
        $response->assertSee('Jadwal Bayar Dijeda', false);
        $response->assertSee('Dijeda', false);
    }
}
