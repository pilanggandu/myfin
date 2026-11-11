<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialTransaction;
use App\Models\Goal;
use App\Models\RecurringSchedule;
use App\Models\Space;
use App\Models\User;
use App\Services\CreditService;
use App\Services\RecurringService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Space $space;

    private Account $account;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->space = $this->user->ensurePersonalSpace();

        $this->account = Account::create([
            'space_id' => $this->space->id,
            'name' => 'Rekening Operasional',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
            'is_default' => true,
        ]);

        $this->category = $this->space->categories()->where('name', 'Belanja Harian')->firstOrFail();
    }

    public function test_recent_transactions_only_includes_posted_transactions_on_or_before_today(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $yesterday = Carbon::now('Asia/Jakarta')->subDay()->toDateString();
        $tomorrow = Carbon::now('Asia/Jakarta')->addDays(3)->toDateString();

        // 1. Transaksi kemarin (posted) - harus masuk
        $txPast = FinancialTransaction::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'transaction_date' => $yesterday,
            'transaction_time' => '10:00:00',
            'description' => 'Makan Kemarin',
            'category_id' => $this->category->id,
            'status' => 'posted',
            'created_by' => $this->user->id,
            'version' => 1,
        ]);
        $txPast->entries()->create(['account_id' => $this->account->id, 'amount_signed' => -50000]);

        // 2. Transaksi hari ini (posted) - harus masuk
        $txToday = FinancialTransaction::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'transaction_date' => $today,
            'transaction_time' => '12:00:00',
            'description' => 'Makan Hari Ini',
            'category_id' => $this->category->id,
            'status' => 'posted',
            'created_by' => $this->user->id,
            'version' => 1,
        ]);
        $txToday->entries()->create(['account_id' => $this->account->id, 'amount_signed' => -75000]);

        // 3. Transaksi masa depan (posted dengan tanggal mendatang) - TIDAK BOLEH masuk ke Transaksi Terkini
        $txFuture = FinancialTransaction::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'transaction_date' => $tomorrow,
            'transaction_time' => '09:00:00',
            'description' => 'Tagihan Masa Depan',
            'category_id' => $this->category->id,
            'status' => 'posted',
            'created_by' => $this->user->id,
            'version' => 1,
        ]);
        $txFuture->entries()->create(['account_id' => $this->account->id, 'amount_signed' => -200000]);

        // 4. Transaksi void (batal) - TIDAK BOLEH masuk ke Transaksi Terkini
        $txVoid = FinancialTransaction::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'transaction_date' => $today,
            'transaction_time' => '08:00:00',
            'description' => 'Transaksi Batal',
            'category_id' => $this->category->id,
            'status' => 'void',
            'created_by' => $this->user->id,
            'version' => 1,
        ]);
        $txVoid->entries()->create(['account_id' => $this->account->id, 'amount_signed' => -30000]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get('/dashboard');

        $response->assertOk();

        $recent = $response->viewData('recentTransactions');

        $this->assertTrue($recent->contains('id', $txToday->id), 'Transaksi hari ini harus muncul di transaksi terkini');
        $this->assertTrue($recent->contains('id', $txPast->id), 'Transaksi kemarin harus muncul di transaksi terkini');
        $this->assertFalse($recent->contains('id', $txFuture->id), 'Transaksi mendatang tidak boleh muncul di transaksi terkini');
        $this->assertFalse($recent->contains('id', $txVoid->id), 'Transaksi batal (void) tidak boleh muncul di transaksi terkini');
    }

    public function test_recurring_dashboard_summary_maps_due_date_amount_and_status_correctly(): void
    {
        $tz = 'Asia/Jakarta';
        $today = Carbon::now($tz);

        // Overdue schedule
        RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Langganan Hosting Overdue',
            'category_id' => $this->category->id,
            'source_account_id' => $this->account->id,
            'frequency' => 'monthly',
            'start_date' => $today->copy()->subMonths(2)->toDateString(),
            'next_due_date' => $today->copy()->subDays(2)->toDateString(),
            'amount' => 150000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // Due today schedule
        RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Tagihan Internet Hari Ini',
            'category_id' => $this->category->id,
            'source_account_id' => $this->account->id,
            'frequency' => 'monthly',
            'start_date' => $today->copy()->subMonths(1)->toDateString(),
            'next_due_date' => $today->toDateString(),
            'amount' => 350000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // Upcoming schedule in 3 days
        RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Langganan Spotify Mendatang',
            'category_id' => $this->category->id,
            'source_account_id' => $this->account->id,
            'frequency' => 'monthly',
            'start_date' => $today->toDateString(),
            'next_due_date' => $today->copy()->addDays(3)->toDateString(),
            'amount' => 55000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $summary = app(RecurringService::class)->getDashboardSummary($this->space);

        $this->assertEquals(1, $summary['overdue_count']);
        $this->assertEquals(1, $summary['due_today_count']);
        $this->assertEquals(1, $summary['upcoming_count']);
        $this->assertCount(3, $summary['items']);

        $statuses = array_column($summary['items'], 'status');
        $this->assertContains('overdue', $statuses);
        $this->assertContains('due_today', $statuses);
        $this->assertContains('upcoming', $statuses);

        foreach ($summary['items'] as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('due_date', $item);
            $this->assertArrayHasKey('amount', $item);
            $this->assertArrayHasKey('status', $item);
            $this->assertNotEmpty($item['due_date']);
        }
    }

    public function test_installment_amount_accessor_returns_total_amount(): void
    {
        $creditCard = app(CreditService::class)->createCreditAccount($this->space, [
            'name' => 'BCA Visa',
            'type' => 'credit_card',
            'credit_limit' => 10000000,
            'billing_cycle_day' => 15,
            'due_day' => 5,
        ]);

        $plan = app(CreditService::class)->recordCreditPurchase($this->space, $this->user, [
            'credit_account_id' => $creditCard->id,
            'category_id' => $this->category->id,
            'name' => 'Smartphone',
            'total_amount' => 3000000,
            'tenor' => 3,
            'start_date' => now()->toDateString(),
        ]);

        $inst = $plan->installments()->first();

        $this->assertNotNull($inst);
        $this->assertEquals($inst->total_amount, $inst->amount);
        $this->assertGreaterThan(0, $inst->amount);
    }

    public function test_dashboard_accounts_widget_displays_available_limit_for_credit_card_and_kewajiban_for_loans(): void
    {
        $creditCard = app(CreditService::class)->createCreditAccount($this->space, [
            'name' => 'BCA Blibli Tiket',
            'type' => 'credit_card',
            'credit_limit' => 5000000,
            'billing_cycle_day' => 20,
            'due_day' => 10,
        ]);

        $loanAccount = Account::create([
            'space_id' => $this->space->id,
            'name' => 'Erwin (Kewajiban)',
            'type' => 'loan',
            'opening_balance' => -1000000,
            'opened_on' => '2026-01-01',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Sisa Limit', false);
        $response->assertSee('5.000.000', false);
        $response->assertSee('Kewajiban', false);
        $response->assertSee('1.000.000', false);
        $response->assertSee('Sisa Hutang', false);
    }

    public function test_dashboard_renders_active_goals_with_progress_percentage(): void
    {
        Goal::create([
            'space_id' => $this->space->id,
            'account_id' => $this->account->id,
            'name' => 'Dana Darurat',
            'target_amount' => 10000000,
            'current_amount' => 2500000,
            'start_date' => '2026-11-01',
            'target_date' => '2027-06-01',
            'status' => 'active',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dana Darurat', false);
        $response->assertSee('2.500.000', false);
        $response->assertSee('10.000.000', false);
        $response->assertSee('width: 25%', false);
    }
}
