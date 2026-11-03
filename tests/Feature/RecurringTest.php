<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\RecurringOccurrence;
use App\Models\RecurringSchedule;
use App\Models\Space;
use App\Models\User;
use App\Services\RecurringService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecurringTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private Space $spaceA;
    private Space $spaceB;
    private Account $bca;
    private Account $gopay;
    private Category $internetCat;
    private Member $memberMe;

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
            'opening_balance' => 2000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->internetCat = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Internet & TV',
            'type' => 'expense',
        ]);

        $this->memberMe = Member::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Me',
            'color' => '#0f4c3a',
        ]);
    }

    public function test_can_create_recurring_payment(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/recurring', [
                'type' => 'expense',
                'name' => 'Tagihan IndiHome',
                'member_id' => $this->memberMe->id,
                'category_id' => $this->internetCat->id,
                'source_account_id' => $this->bca->id,
                'frequency' => 'monthly',
                'start_date' => '2026-01-15',
                'amount' => 450000,
            ]);

        $schedule = RecurringSchedule::where('name', 'Tagihan IndiHome')->first();
        $this->assertNotNull($schedule);
        $this->assertEquals('expense', $schedule->type);
        $this->assertEquals(450000, $schedule->amount);
        $this->assertEquals(15, $schedule->anchor_day);
        $this->assertEquals('2026-01-15', $schedule->next_due_date->toDateString());

        $this->assertDatabaseHas('recurring_occurrences', [
            'recurring_schedule_id' => $schedule->id,
            'due_date' => '2026-01-15',
            'status' => 'pending',
        ]);

        $response->assertRedirect(route('recurring.show', $schedule));
    }

    public function test_can_create_recurring_transfer(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/recurring', [
                'type' => 'transfer',
                'name' => 'Top Up GoPay Rutin',
                'member_id' => $this->memberMe->id,
                'source_account_id' => $this->bca->id,
                'destination_account_id' => $this->gopay->id,
                'frequency' => 'weekly',
                'start_date' => '2026-01-05',
                'amount' => 200000,
            ]);

        $schedule = RecurringSchedule::where('name', 'Top Up GoPay Rutin')->first();
        $this->assertNotNull($schedule);
        $this->assertEquals('transfer', $schedule->type);
        $this->assertEquals($this->bca->id, $schedule->source_account_id);
        $this->assertEquals($this->gopay->id, $schedule->destination_account_id);
        $this->assertNull($schedule->category_id);

        $response->assertRedirect(route('recurring.show', $schedule));
    }

    public function test_cannot_create_recurring_transfer_with_same_source_and_destination(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post('/recurring', [
                'type' => 'transfer',
                'name' => 'Transfer Gagal',
                'source_account_id' => $this->bca->id,
                'destination_account_id' => $this->bca->id,
                'frequency' => 'monthly',
                'start_date' => '2026-01-05',
                'amount' => 100000,
            ]);

        $response->assertSessionHasErrors('destination_account_id');
    }

    public function test_anchor_day_calculation_for_month_end_without_drift(): void
    {
        $schedule = RecurringSchedule::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'name' => 'Sewa Kantor Akhir Bulan',
            'category_id' => $this->internetCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 31,
            'start_date' => '2026-01-31',
            'next_due_date' => '2026-01-31',
            'amount' => 5000000,
            'status' => 'active',
        ]);

        $nextFeb = $schedule->calculateNextDueDate(Carbon::parse('2026-01-31'));
        $this->assertEquals('2026-02-28', $nextFeb->toDateString());

        $nextMar = $schedule->calculateNextDueDate($nextFeb);
        $this->assertEquals('2026-03-31', $nextMar->toDateString());

        $nextApr = $schedule->calculateNextDueDate($nextMar);
        $this->assertEquals('2026-04-30', $nextApr->toDateString());

        $nextMay = $schedule->calculateNextDueDate($nextApr);
        $this->assertEquals('2026-05-31', $nextMay->toDateString());
    }

    public function test_leap_year_yearly_frequency_calculation(): void
    {
        $schedule = RecurringSchedule::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'name' => 'Langganan Kabisat',
            'category_id' => $this->internetCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'yearly',
            'anchor_day' => 29,
            'start_date' => '2024-02-29',
            'next_due_date' => '2024-02-29',
            'amount' => 1000000,
            'status' => 'active',
        ]);

        $next2025 = $schedule->calculateNextDueDate(Carbon::parse('2024-02-29'));
        $this->assertEquals('2025-02-28', $next2025->toDateString());

        $next2026 = $schedule->calculateNextDueDate(Carbon::parse('2025-02-28'));
        $this->assertEquals('2026-02-28', $next2026->toDateString());

        $next2028 = $schedule->calculateNextDueDate(Carbon::parse('2027-02-28'));
        $this->assertEquals('2028-02-29', $next2028->toDateString());
    }

    public function test_due_status_respects_space_timezone(): void
    {
        $todayStr = Carbon::now('Asia/Jakarta')->toDateString();
        $yesterdayStr = Carbon::now('Asia/Jakarta')->subDay()->toDateString();
        $tomorrowStr = Carbon::now('Asia/Jakarta')->addDay()->toDateString();

        $scheduleOverdue = RecurringSchedule::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'name' => 'Overdue Test',
            'category_id' => $this->internetCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 1,
            'start_date' => $yesterdayStr,
            'next_due_date' => $yesterdayStr,
            'amount' => 100000,
            'status' => 'active',
        ]);

        $scheduleToday = RecurringSchedule::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'name' => 'Today Test',
            'category_id' => $this->internetCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 1,
            'start_date' => $todayStr,
            'next_due_date' => $todayStr,
            'amount' => 100000,
            'status' => 'active',
        ]);

        $scheduleUpcoming = RecurringSchedule::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'name' => 'Upcoming Test',
            'category_id' => $this->internetCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 1,
            'start_date' => $tomorrowStr,
            'next_due_date' => $tomorrowStr,
            'amount' => 100000,
            'status' => 'active',
        ]);

        $this->assertEquals('overdue', $scheduleOverdue->dueStatus('Asia/Jakarta'));
        $this->assertEquals('due', $scheduleToday->dueStatus('Asia/Jakarta'));
        $this->assertEquals('upcoming', $scheduleUpcoming->dueStatus('Asia/Jakarta'));
    }

    public function test_confirm_occurrence_records_financial_transaction_and_advances_schedule(): void
    {
        $service = app(RecurringService::class);
        $schedule = $service->createSchedule($this->spaceA, $this->userA, [
            'type' => 'expense',
            'name' => 'Sewa Bulanan',
            'member_id' => $this->memberMe->id,
            'category_id' => $this->internetCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'start_date' => '2026-01-10',
            'amount' => 1200000,
        ]);

        $bcaInitial = $this->bca->currentBalance();

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->post("/recurring/{$schedule->id}/confirm", [
                'due_date' => '2026-01-10',
                'amount' => 1200000,
                'transaction_date' => '2026-01-10',
            ]);

        $response->assertRedirect();

        $this->bca->refresh();
        $this->assertEquals($bcaInitial - 1200000, $this->bca->currentBalance());

        $occurrence = RecurringOccurrence::where('recurring_schedule_id', $schedule->id)
            ->where('due_date', '2026-01-10')
            ->first();

        $this->assertNotNull($occurrence);
        $this->assertTrue($occurrence->isConfirmed());
        $this->assertNotNull($occurrence->transaction_id);

        $tx = $occurrence->transaction;
        $this->assertEquals('expense', $tx->type);
        $this->assertEquals(1200000, $tx->totalAmount());
        $this->assertEquals($this->memberMe->id, $tx->member_id);

        $schedule->refresh();
        $this->assertEquals('2026-02-10', $schedule->next_due_date->toDateString());

        $nextOccurrence = RecurringOccurrence::where('recurring_schedule_id', $schedule->id)
            ->where('due_date', '2026-02-10')
            ->first();

        $this->assertNotNull($nextOccurrence);
        $this->assertTrue($nextOccurrence->isPending());
    }

    public function test_cannot_confirm_same_occurrence_twice(): void
    {
        $service = app(RecurringService::class);
        $schedule = $service->createSchedule($this->spaceA, $this->userA, [
            'type' => 'expense',
            'name' => 'Tagihan Air PDAM',
            'category_id' => $this->internetCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'start_date' => '2026-01-10',
            'amount' => 150000,
        ]);

        $service->confirmOccurrence($schedule, '2026-01-10', $this->userA);

        $this->expectException(ValidationException::class);
        $service->confirmOccurrence($schedule, '2026-01-10', $this->userA);
    }

    public function test_recurring_transfer_cash_flow_neutrality(): void
    {
        $service = app(RecurringService::class);
        $schedule = $service->createSchedule($this->spaceA, $this->userA, [
            'type' => 'transfer',
            'name' => 'Pindahan Rutin BCA ke GoPay',
            'source_account_id' => $this->bca->id,
            'destination_account_id' => $this->gopay->id,
            'frequency' => 'monthly',
            'start_date' => '2026-01-05',
            'amount' => 500000,
        ]);

        $spaceTotalBefore = $this->spaceA->totalBalance();
        $bcaBefore = $this->bca->currentBalance();
        $gopayBefore = $this->gopay->currentBalance();

        $tx = $service->confirmOccurrence($schedule, '2026-01-05', $this->userA);

        $this->bca->refresh();
        $this->gopay->refresh();

        $this->assertEquals($bcaBefore - 500000, $this->bca->currentBalance());
        $this->assertEquals($gopayBefore + 500000, $this->gopay->currentBalance());
        $this->assertEquals($spaceTotalBefore, $this->spaceA->totalBalance());

        $this->assertEquals('transfer', $tx->type);
        $this->assertNull($tx->category_id);
    }

    public function test_skip_occurrence_advances_due_date_without_transaction(): void
    {
        $service = app(RecurringService::class);
        $schedule = $service->createSchedule($this->spaceA, $this->userA, [
            'type' => 'expense',
            'name' => 'Tagihan Langganan Gym',
            'category_id' => $this->internetCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'amount' => 300000,
        ]);

        $txCountBefore = FinancialTransaction::where('space_id', $this->spaceA->id)->count();

        $service->skipOccurrence($schedule, '2026-01-01', $this->userA, 'Libur cuti gym');

        $schedule->refresh();
        $this->assertEquals('2026-02-01', $schedule->next_due_date->toDateString());

        $occurrence = RecurringOccurrence::where('recurring_schedule_id', $schedule->id)
            ->where('due_date', '2026-01-01')
            ->first();

        $this->assertNotNull($occurrence);
        $this->assertTrue($occurrence->isSkipped());
        $this->assertNull($occurrence->transaction_id);

        $this->assertEquals($txCountBefore, FinancialTransaction::where('space_id', $this->spaceA->id)->count());
    }

    public function test_voiding_transaction_reverts_occurrence_back_to_pending(): void
    {
        $service = app(RecurringService::class);
        $txService = app(TransactionService::class);

        $schedule = $service->createSchedule($this->spaceA, $this->userA, [
            'type' => 'expense',
            'name' => 'Langganan Spotify Family',
            'category_id' => $this->internetCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'start_date' => '2026-01-12',
            'amount' => 86000,
        ]);

        $tx = $service->confirmOccurrence($schedule, '2026-01-12', $this->userA);

        $occurrence = RecurringOccurrence::where('recurring_schedule_id', $schedule->id)
            ->where('due_date', '2026-01-12')
            ->first();

        $this->assertTrue($occurrence->isConfirmed());
        $this->assertEquals($tx->id, $occurrence->transaction_id);

        $txService->voidTransaction($tx, $this->userA, 'Salah periode tagihan');

        $occurrence->refresh();
        $this->assertTrue($occurrence->isPending());
        $this->assertNull($occurrence->transaction_id);
    }

    public function test_space_isolation_for_recurring_schedules(): void
    {
        $service = app(RecurringService::class);
        $scheduleB = $service->createSchedule($this->spaceB, $this->userB, [
            'type' => 'expense',
            'name' => 'Secret Schedule B',
            'category_id' => Category::create(['space_id' => $this->spaceB->id, 'name' => 'Cat B', 'type' => 'expense'])->id,
            'source_account_id' => Account::create(['space_id' => $this->spaceB->id, 'name' => 'Acc B', 'type' => 'bank', 'opening_balance' => 1000000, 'opened_on' => '2026-01-01'])->id,
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'amount' => 100000,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get("/recurring/{$scheduleB->id}");

        $response->assertStatus(403);
    }
}
