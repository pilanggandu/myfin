<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\RecurringOccurrence;
use App\Models\RecurringSchedule;
use App\Models\Space;
use App\Models\User;
use App\Services\CreditService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

    private Account $bca;

    private Account $gopay;

    private Category $incomeCat;

    private Category $foodCat;

    private Category $transportCat;

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
            'name' => 'Gaji Pokok',
            'type' => 'income',
        ]);

        $this->foodCat = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Makanan & Minuman',
            'type' => 'expense',
        ]);

        $this->transportCat = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Transportasi',
            'type' => 'expense',
        ]);
    }

    public function test_cash_flow_report_accurately_sums_income_and_expense(): void
    {
        $service = app(TransactionService::class);

        $service->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->incomeCat->id,
            'amount' => 5000000,
            'transaction_date' => '2026-05-01',
            'description' => 'Gaji Bulanan',
        ]);

        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCat->id,
            'amount' => 1200000,
            'transaction_date' => '2026-05-05',
            'description' => 'Belanja Makanan',
        ]);

        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->transportCat->id,
            'amount' => 800000,
            'transaction_date' => '2026-05-10',
            'description' => 'Bensin & Tol',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?year=2026&month=5');

        $response->assertStatus(200);
        $response->assertViewHas('totalIncome', 5000000);
        $response->assertViewHas('totalExpense', 2000000);
        $response->assertViewHas('netCashFlow', 3000000);
    }

    public function test_transfers_and_voids_are_strictly_excluded_from_cash_flow(): void
    {
        $service = app(TransactionService::class);

        $service->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->incomeCat->id,
            'amount' => 5000000,
            'transaction_date' => '2026-05-01',
            'description' => 'Gaji Pokok',
        ]);

        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCat->id,
            'amount' => 1000000,
            'transaction_date' => '2026-05-05',
            'description' => 'Makan',
        ]);

        $service->recordTransfer($this->spaceA, $this->userA, [
            'source_account_id' => $this->bca->id,
            'destination_account_id' => $this->gopay->id,
            'amount' => 1500000,
            'transaction_date' => '2026-05-08',
            'description' => 'Transfer Internal',
        ]);

        $voidTx = $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->transportCat->id,
            'amount' => 500000,
            'transaction_date' => '2026-05-12',
            'description' => 'Batal',
        ]);
        $service->voidTransaction($voidTx, $this->userA);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?year=2026&month=5');

        $response->assertStatus(200);
        $response->assertViewHas('totalIncome', 5000000);
        $response->assertViewHas('totalExpense', 1000000);
        $response->assertViewHas('netCashFlow', 4000000);
    }

    public function test_expense_by_category_breakdown_percentages_and_ordering(): void
    {
        $service = app(TransactionService::class);

        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCat->id,
            'amount' => 1500000,
            'transaction_date' => '2026-05-05',
            'description' => 'Makanan Utama',
        ]);

        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->transportCat->id,
            'amount' => 500000,
            'transaction_date' => '2026-05-10',
            'description' => 'Transport',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?year=2026&month=5');

        $response->assertStatus(200);

        $breakdown = $response->viewData('categoryBreakdown');
        $this->assertCount(2, $breakdown);

        $first = $breakdown->first();
        $this->assertEquals('Makanan & Minuman', $first->name);
        $this->assertEquals(1500000, $first->amount);
        $this->assertEquals(75.0, $first->percentage);

        $second = $breakdown->last();
        $this->assertEquals('Transportasi', $second->name);
        $this->assertEquals(500000, $second->amount);
        $this->assertEquals(25.0, $second->percentage);
    }

    public function test_reports_handle_empty_dataset_gracefully(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?year=2024&month=1');

        $response->assertStatus(200);
        $response->assertViewHas('totalIncome', 0);
        $response->assertViewHas('totalExpense', 0);
        $response->assertViewHas('netCashFlow', 0);
        $response->assertViewHas('categoryBreakdown', fn ($coll) => $coll->isEmpty());
    }

    public function test_space_isolation_report_only_includes_active_space_data(): void
    {
        $accountB = Account::create([
            'space_id' => $this->spaceB->id,
            'name' => 'BCA Privat B',
            'type' => 'bank',
            'opening_balance' => 1000000,
            'opened_on' => '2026-01-01',
        ]);

        $categoryB = Category::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Kategori Privat B',
            'type' => 'income',
        ]);

        $service = app(TransactionService::class);
        $service->recordIncome($this->spaceB, $this->userB, [
            'account_id' => $accountB->id,
            'category_id' => $categoryB->id,
            'amount' => 50000000,
            'transaction_date' => '2026-05-01',
            'description' => 'Gaji Privat B',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?year=2026&month=5');

        $response->assertStatus(200);
        $response->assertViewHas('totalIncome', 0);
    }

    public function test_six_month_trend_does_not_skip_or_duplicate_months_on_day_31(): void
    {
        Carbon::setTestNow('2026-03-31 15:30:00');

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports');

        $response->assertStatus(200);
        $response->assertViewHas('monthlyTrend', function ($trend) {
            $this->assertCount(6, $trend);
            $months = array_map(fn ($item) => (int) $item->month, $trend);
            $this->assertEquals([10, 11, 12, 1, 2, 3], $months);

            return true;
        });

        Carbon::setTestNow();
    }

    public function test_report_validates_and_gracefully_handles_swapped_custom_dates(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?period_type=custom&start_date=2026-05-31&end_date=2026-05-01');

        $response->assertStatus(200);
        $response->assertViewHas('startDate', '2026-05-01');
        $response->assertViewHas('endDate', '2026-05-31');
    }

    public function test_report_rejects_invalid_date_parameters(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?year=99999');

        $response->assertSessionHasErrors(['year']);
    }

    public function test_daily_cashflow_raw_data_groups_by_day_and_computes_running_balance(): void
    {
        $service = app(TransactionService::class);

        // Day 1: Inflow 5.000.000
        $service->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->incomeCat->id,
            'amount' => 5000000,
            'transaction_date' => '2026-05-02',
            'description' => 'Gaji 2 Mei',
        ]);

        // Day 2: Outflow 1.000.000
        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCat->id,
            'amount' => 1000000,
            'transaction_date' => '2026-05-05',
            'description' => 'Makan 5 Mei',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?tab=daily&year=2026&month=5&sort_order=asc');

        $response->assertStatus(200);
        $response->assertViewHas('dailyCashflows', function ($daily) {
            $this->assertCount(2, $daily);

            $day1 = $daily[0];
            $this->assertEquals('2026-05-02', $day1->date);
            $this->assertEquals(5000000, $day1->inflow);
            $this->assertEquals(0, $day1->outflow);
            $this->assertEquals(5000000, $day1->net);
            // Starting balance is 10.000.000 (opening balance of BCA) + 5.000.000 = 15.000.000
            $this->assertEquals(15000000, $day1->closing_balance);

            $day2 = $daily[1];
            $this->assertEquals('2026-05-05', $day2->date);
            $this->assertEquals(0, $day2->inflow);
            $this->assertEquals(1000000, $day2->outflow);
            $this->assertEquals(-1000000, $day2->net);
            $this->assertEquals(14000000, $day2->closing_balance);

            return true;
        });
    }

    public function test_daily_cashflow_can_be_exported_as_csv(): void
    {
        $service = app(TransactionService::class);

        $service->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->incomeCat->id,
            'amount' => 3500000,
            'transaction_date' => '2026-05-10',
            'description' => 'Freelance Desain',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports/export-csv?year=2026&month=5');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Tanggal,Hari,Waktu,Keterangan', $content);
        $this->assertStringContainsString('Freelance Desain', $content);
        $this->assertStringContainsString('3500000', $content);
    }

    public function test_daily_cashflow_calendar_view_renders_successfully(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?tab=daily&view=calendar&year=2026&month=5');

        $response->assertStatus(200);
        $response->assertViewIs('reports.index');
        $response->assertViewHas('viewMode', 'calendar');
        $response->assertViewHas('calendarWeeks');
        $response->assertSee('Kalender');
        $response->assertSee('calendarDayModal');
        $response->assertSee('Mei 2026');
    }

    public function test_daily_cashflow_calendar_view_contains_daily_totals_and_items(): void
    {
        $service = app(TransactionService::class);

        $service->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->incomeCat->id,
            'amount' => 5000000,
            'transaction_date' => '2026-05-15',
            'description' => 'Gaji Bulanan Mei',
        ]);

        $service->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCat->id,
            'amount' => 250000,
            'transaction_date' => '2026-05-15',
            'description' => 'Makan Siang Resto',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?tab=daily&view=calendar&year=2026&month=5');

        $response->assertStatus(200);
        $response->assertViewHas('calendarWeeks', function ($weeks) {
            $foundDay = null;
            foreach ($weeks as $week) {
                foreach ($week as $day) {
                    if ($day->date === '2026-05-15') {
                        $foundDay = $day;
                        break 2;
                    }
                }
            }

            $this->assertNotNull($foundDay);
            $this->assertEquals(5000000, $foundDay->inflow);
            $this->assertEquals(250000, $foundDay->outflow);
            $this->assertEquals(4750000, $foundDay->net);
            $this->assertEquals(2, $foundDay->count);
            $this->assertCount(2, $foundDay->modal_items);

            return true;
        });

        $response->assertSee('Gaji Bulanan Mei');
        $response->assertSee('Makan Siang Resto');
        $response->assertSee('calDayData-2026-05-15');
    }

    public function test_daily_cashflow_calendar_view_displays_bills_and_installments_without_distorting_cashflow(): void
    {
        RecurringSchedule::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'name' => 'Langganan Internet',
            'category_id' => $this->foodCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 20,
            'start_date' => '2026-01-01',
            'next_due_date' => '2026-05-20',
            'amount' => 450000,
            'status' => 'active',
            'created_by' => $this->userA->id,
        ]);

        $creditService = app(CreditService::class);
        $creditAccount = $creditService->createCreditAccount($this->spaceA, [
            'name' => 'BCA Card Platinum',
            'type' => 'credit_card',
            'credit_limit' => 20000000,
            'billing_cycle_day' => 5,
            'due_day' => 25,
        ]);

        $plan = InstallmentPlan::create([
            'space_id' => $this->spaceA->id,
            'credit_account_id' => $creditAccount->id,
            'category_id' => $this->foodCat->id,
            'name' => 'Cicilan iPhone',
            'total_amount' => 12000000,
            'tenor' => 12,
            'interest_rate' => 0,
            'admin_fee' => 0,
            'start_date' => '2026-05-01',
            'status' => 'active',
        ]);

        Installment::create([
            'space_id' => $this->spaceA->id,
            'installment_plan_id' => $plan->id,
            'installment_number' => 1,
            'due_date' => '2026-05-25',
            'principal_amount' => 1000000,
            'interest_amount' => 0,
            'fee_amount' => 0,
            'total_amount' => 1000000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?tab=daily&view=calendar&year=2026&month=5');

        $response->assertStatus(200);
        $response->assertSeeText('Tagihan & Cicilan');
        $response->assertSee('Langganan Internet');
        $response->assertSee('Cicilan iPhone');

        $response->assertViewHas('calendarWeeks', function ($weeks) {
            $recurringDay = null;
            $installmentDay = null;

            foreach ($weeks as $week) {
                foreach ($week as $day) {
                    if ($day->date === '2026-05-20') {
                        $recurringDay = $day;
                    }
                    if ($day->date === '2026-05-25') {
                        $installmentDay = $day;
                    }
                }
            }

            $this->assertNotNull($recurringDay);
            $this->assertEquals(1, $recurringDay->bills_count);
            $this->assertEquals(450000, $recurringDay->bills_total_amount);
            $this->assertEquals(0, $recurringDay->outflow);
            $this->assertEquals('Jadwal Rutin', $recurringDay->bills[0]['kind_label']);
            $this->assertStringContainsString('Bank BCA', $recurringDay->bills[0]['subtitle']);

            $this->assertNotNull($installmentDay);
            $this->assertEquals(1, $installmentDay->bills_count);
            $this->assertEquals(1000000, $installmentDay->bills_total_amount);
            $this->assertEquals(0, $installmentDay->outflow);
            $this->assertEquals('Kredit & Cicilan', $installmentDay->bills[0]['kind_label']);

            return true;
        });
    }

    public function test_calendar_view_excludes_cancelled_installments_and_voided_credit_purchases(): void
    {
        $creditAccount = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'BCA Blibli Tiket (Kewajiban)',
            'type' => 'credit_card',
            'opening_balance' => 0,
            'opened_on' => '2026-01-01',
        ]);

        $creditAcc = CreditAccount::create([
            'space_id' => $this->spaceA->id,
            'account_id' => $creditAccount->id,
            'name' => 'BCA BLIBLI TIKET',
            'type' => 'credit_card',
            'credit_limit' => 20000000,
        ]);

        // 1. Cicilan Aktif
        $activePlan = InstallmentPlan::create([
            'space_id' => $this->spaceA->id,
            'credit_account_id' => $creditAcc->id,
            'category_id' => $this->foodCat->id,
            'name' => 'Chatgpt Plus Aktif',
            'total_amount' => 349000,
            'tenor' => 1,
            'start_date' => '2026-05-01',
            'status' => 'active',
        ]);
        Installment::create([
            'space_id' => $this->spaceA->id,
            'installment_plan_id' => $activePlan->id,
            'installment_number' => 1,
            'due_date' => '2026-05-19',
            'principal_amount' => 349000,
            'total_amount' => 349000,
            'status' => 'pending',
        ]);

        // 2. Cicilan Dibatalkan (plan status = cancelled, installment status = cancelled)
        $cancelledPlan = InstallmentPlan::create([
            'space_id' => $this->spaceA->id,
            'credit_account_id' => $creditAcc->id,
            'category_id' => $this->foodCat->id,
            'name' => 'Langganan Chatgpt Dibatalkan',
            'total_amount' => 349000,
            'tenor' => 1,
            'start_date' => '2026-05-01',
            'status' => 'cancelled',
        ]);
        Installment::create([
            'space_id' => $this->spaceA->id,
            'installment_plan_id' => $cancelledPlan->id,
            'installment_number' => 1,
            'due_date' => '2026-05-19',
            'principal_amount' => 349000,
            'total_amount' => 349000,
            'status' => 'cancelled',
        ]);

        // 3. Cicilan dengan Transaksi Pembelian Void
        $voidTx = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'transaction_date' => '2026-05-01',
            'transaction_time' => '10:00:00',
            'description' => 'Pembelian Void',
            'category_id' => $this->foodCat->id,
            'status' => 'void',
            'created_by' => $this->userA->id,
            'version' => 1,
        ]);
        $voidPlan = InstallmentPlan::create([
            'space_id' => $this->spaceA->id,
            'credit_account_id' => $creditAcc->id,
            'financial_transaction_id' => $voidTx->id,
            'category_id' => $this->foodCat->id,
            'name' => 'Pembelian Dengan Transaksi Void',
            'total_amount' => 100000,
            'tenor' => 1,
            'start_date' => '2026-05-01',
            'status' => 'active',
        ]);
        Installment::create([
            'space_id' => $this->spaceA->id,
            'installment_plan_id' => $voidPlan->id,
            'installment_number' => 1,
            'due_date' => '2026-05-19',
            'principal_amount' => 100000,
            'total_amount' => 100000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?tab=daily&view=calendar&year=2026&month=5');

        $response->assertStatus(200);
        $response->assertSee('Chatgpt Plus Aktif');
        $response->assertDontSee('Langganan Chatgpt Dibatalkan');
        $response->assertDontSee('Pembelian Dengan Transaksi Void');

        $response->assertViewHas('calendarWeeks', function ($weeks) {
            $targetDay = null;
            foreach ($weeks as $week) {
                foreach ($week as $day) {
                    if ($day->date === '2026-05-19') {
                        $targetDay = $day;
                    }
                }
            }

            $this->assertNotNull($targetDay);
            // Hanya 1 cicilan aktif yang masuk
            $this->assertEquals(1, $targetDay->bills_count);
            $this->assertEquals(349000, $targetDay->bills_total_amount);
            $this->assertEquals('Chatgpt Plus Aktif', $targetDay->bills[0]['title']);

            return true;
        });
    }

    public function test_calendar_view_excludes_paused_cancelled_archived_completed_recurring_schedules_and_voided_occurrences(): void
    {
        $base = [
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'category_id' => $this->foodCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 7,
            'start_date' => '2026-01-07',
            'next_due_date' => '2026-05-07',
            'amount' => 349000,
            'created_by' => $this->userA->id,
        ];

        // 1. Active - harus muncul
        RecurringSchedule::create(array_merge($base, [
            'name' => 'Langganan Aktif',
            'status' => 'active',
        ]));

        // 2. Paused - TIDAK boleh muncul
        RecurringSchedule::create(array_merge($base, [
            'name' => 'Langganan Chatgpt Dijeda',
            'status' => 'paused',
        ]));

        // 3. Cancelled - TIDAK boleh muncul
        RecurringSchedule::create(array_merge($base, [
            'name' => 'Langganan Chatgpt Batal',
            'status' => 'cancelled',
        ]));

        // 4. Completed - TIDAK boleh muncul
        RecurringSchedule::create(array_merge($base, [
            'name' => 'Langganan Selesai',
            'status' => 'completed',
        ]));

        // 5. Archived - TIDAK boleh muncul
        RecurringSchedule::create(array_merge($base, [
            'name' => 'Langganan Diarsipkan',
            'status' => 'archived',
            'archived_at' => now(),
        ]));

        // 6. Active schedule dengan occurrence transaksi void - TIDAK boleh muncul
        $schVoidOcc = RecurringSchedule::create(array_merge($base, [
            'name' => 'Langganan Void Occurrence',
            'status' => 'active',
        ]));
        $voidTx = FinancialTransaction::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'transaction_date' => '2026-05-07',
            'transaction_time' => '10:00:00',
            'description' => 'Langganan Void Occurrence',
            'category_id' => $this->foodCat->id,
            'status' => 'void',
            'created_by' => $this->userA->id,
            'version' => 1,
        ]);
        RecurringOccurrence::create([
            'recurring_schedule_id' => $schVoidOcc->id,
            'space_id' => $this->spaceA->id,
            'due_date' => '2026-05-07',
            'transaction_id' => $voidTx->id,
            'status' => 'confirmed',
            'amount' => 349000,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?tab=daily&view=calendar&year=2026&month=5');

        $response->assertStatus(200);
        $response->assertSee('Langganan Aktif');
        $response->assertDontSee('Langganan Chatgpt Dijeda');
        $response->assertDontSee('Langganan Chatgpt Batal');
        $response->assertDontSee('Langganan Selesai');
        $response->assertDontSee('Langganan Diarsipkan');
        $response->assertDontSee('Langganan Void Occurrence');

        $response->assertViewHas('calendarWeeks', function ($weeks) {
            $targetDay = null;
            foreach ($weeks as $week) {
                foreach ($week as $day) {
                    if ($day->date === '2026-05-07') {
                        $targetDay = $day;
                    }
                }
            }

            $this->assertNotNull($targetDay);
            $this->assertEquals(1, $targetDay->bills_count);
            $this->assertEquals('Langganan Aktif', $targetDay->bills[0]['title']);

            return true;
        });
    }

    public function test_cash_flow_report_weekly_view_renders_successfully_with_weekly_aggregation(): void
    {
        $txService = app(TransactionService::class);

        // Transaksi Pekan 1: 3 Nov 2026 (Expense 100.000)
        $txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCat->id,
            'amount' => 100000,
            'transaction_date' => '2026-11-03',
            'description' => 'Makan Siang Pekan Satu',
        ]);

        // Transaksi Pekan 2: 10 Nov 2026 (Income 500.000)
        $txService->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $this->bca->id,
            'category_id' => $this->incomeCat->id,
            'amount' => 500000,
            'transaction_date' => '2026-11-10',
            'description' => 'Bonus Freelance Pekan Dua',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?tab=daily&view=weekly&year=2026&month=11');

        $response->assertStatus(200);
        $response->assertSee('Mingguan');
        $response->assertSee('Makan Siang Pekan Satu');
        $response->assertSee('Bonus Freelance Pekan Dua');
        $response->assertSee('Rata-Rata Keluar / Pekan');
        $response->assertSee('Pengeluaran Tertinggi');

        $response->assertViewHas('weeklyData', function ($weeklyData) {
            $this->assertNotEmpty($weeklyData['weeks']);
            $this->assertEquals(100000, $weeklyData['highestExpenseWeek']?->outflow);
            $this->assertEquals(500000, $weeklyData['highestNetWeek']?->inflow);

            return true;
        });
    }

    public function test_cash_flow_report_weekly_view_supports_sort_order(): void
    {
        $responseDesc = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?tab=daily&view=weekly&year=2026&month=11&sort_order=desc');

        $responseDesc->assertStatus(200);
        $responseDesc->assertViewHas('weeklyData', function ($weeklyData) {
            $weeks = $weeklyData['weeks'];
            $this->assertGreaterThan(1, count($weeks));
            $this->assertGreaterThan($weeks[count($weeks) - 1]->week_number, $weeks[0]->week_number);

            return true;
        });

        $responseAsc = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get('/reports?tab=daily&view=weekly&year=2026&month=11&sort_order=asc');

        $responseAsc->assertStatus(200);
        $responseAsc->assertViewHas('weeklyData', function ($weeklyData) {
            $weeks = $weeklyData['weeks'];
            $this->assertGreaterThan(1, count($weeks));
            $this->assertEquals(1, $weeks[0]->week_number);

            return true;
        });
    }
}
