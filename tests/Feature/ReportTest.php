<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Space;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        \Illuminate\Support\Carbon::setTestNow('2026-03-31 15:30:00');

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

        \Illuminate\Support\Carbon::setTestNow();
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
}
