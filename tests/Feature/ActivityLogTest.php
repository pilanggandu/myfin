<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\CreditAccount;
use App\Models\FinancialTransaction;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\ShortPlan;
use App\Models\Space;
use App\Models\User;
use App\Services\CreditService;
use App\Services\RecurringService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithPersonalSpace(string $email = 'user@example.com', string $name = 'User Satu'): array
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
        ]);

        $space = Space::create([
            'name' => "Ruang {$name}",
            'type' => 'personal',
            'owner_id' => $user->id,
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
        ]);
        $space->members()->attach($user->id, ['role' => 'owner', 'status' => 'active']);

        return [$user, $space];
    }

    public function test_guest_is_redirected_to_login_from_activity_logs(): void
    {
        $this->get(route('activity-logs.index'))
            ->assertRedirect(route('login'));

        $this->get(route('activity-logs.export-csv'))
            ->assertRedirect(route('login'));
    }

    public function test_index_renders_empty_state_when_no_logs_exist(): void
    {
        [$user, $space] = $this->createUserWithPersonalSpace();

        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('Log Aktivitas')
            ->assertSee('Belum ada aktivitas tercatat');
    }

    public function test_transactions_accounts_categories_and_budgets_record_activity_logs_without_duplicates(): void
    {
        [$user, $space] = $this->createUserWithPersonalSpace();

        $category = Category::create([
            'space_id' => $space->id,
            'name' => 'Gaji',
            'type' => 'income',
        ]);

        // 1. Account creation
        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->post(route('accounts.store'), [
                'name' => 'BCA Utama',
                'type' => 'bank',
                'opening_balance' => 1000000,
                'opened_on' => now()->toDateString(),
            ])
            ->assertRedirect(route('accounts.index'));

        $account = Account::where('space_id', $space->id)->where('name', 'BCA Utama')->firstOrFail();

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'actor_id' => $user->id,
            'module' => 'account',
            'action' => 'created',
            'subject_type' => Account::class,
            'subject_id' => $account->id,
        ]);

        // 2. Balance adjustment emits one account.adjusted entry (no duplicate transaction.created)
        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->post(route('accounts.adjust', $account), [
                'target_balance' => 1500000,
                'adjustment_date' => now()->toDateString(),
                'reason' => 'Koreksi kas bank',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'account',
            'action' => 'adjusted',
            'subject_id' => $account->id,
        ]);
        $this->assertSame(
            0,
            ActivityLog::where('space_id', $space->id)
                ->where('module', 'transaction')
                ->where('action', 'created')
                ->count()
        );

        // 3. Transaction via service + void
        $txService = app(TransactionService::class);
        $tx = $txService->recordIncome($space, $user, [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 500000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Bonus Proyek',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'transaction',
            'action' => 'created',
            'subject_type' => FinancialTransaction::class,
            'subject_id' => $tx->id,
        ]);

        $txService->voidTransaction($tx, $user);

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'transaction',
            'action' => 'voided',
            'subject_type' => FinancialTransaction::class,
            'subject_id' => $tx->id,
        ]);

        // 4. Category create & archive
        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->post(route('categories.store'), [
                'name' => 'Transportasi',
                'type' => 'expense',
            ])
            ->assertRedirect();

        $expenseCategory = Category::where('space_id', $space->id)->where('name', 'Transportasi')->firstOrFail();

        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->post(route('categories.archive', $expenseCategory))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'category',
            'action' => 'created',
            'subject_id' => $expenseCategory->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'category',
            'action' => 'archived',
            'subject_id' => $expenseCategory->id,
        ]);

        // 5. Budget create & delete (needs an active expense category)
        $budgetCategory = Category::create([
            'space_id' => $space->id,
            'name' => 'Makanan',
            'type' => 'expense',
        ]);

        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->post(route('budgets.store'), [
                'category_id' => $budgetCategory->id,
                'limit_amount' => 750000,
                'month' => now()->month,
                'year' => now()->year,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'budget',
            'action' => 'created',
        ]);
    }

    public function test_goals_short_plans_recurring_credit_reminders_and_reports_record_activity_logs(): void
    {
        [$user, $space] = $this->createUserWithPersonalSpace();

        $account = Account::create([
            'space_id' => $space->id,
            'name' => 'Mandiri Tabungan',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => now()->toDateString(),
        ]);

        $expenseCategory = Category::create([
            'space_id' => $space->id,
            'name' => 'Belanja',
            'type' => 'expense',
        ]);

        // Goal create & contribute (deduct_from_account true should NOT duplicate transaction.created)
        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->post(route('goals.store'), [
                'name' => 'Dana Darurat',
                'target_amount' => 5000000,
                'start_date' => now()->toDateString(),
                'account_id' => $account->id,
                'category_id' => $expenseCategory->id,
            ])
            ->assertRedirect();

        $goal = Goal::where('space_id', $space->id)->where('name', 'Dana Darurat')->firstOrFail();

        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->post(route('goals.contribute', $goal), [
                'amount' => 500000,
                'contribution_date' => now()->toDateString(),
                'account_id' => $account->id,
                'deduct_from_account' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'goal',
            'action' => 'created',
            'subject_id' => $goal->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'goal',
            'action' => 'contributed',
            'subject_id' => $goal->id,
        ]);

        // Short Plan create & realize (should emit short_plan.realized and NOT duplicate transaction.created)
        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->post(route('short-plans.store'), [
                'title' => 'Beli Keyboard',
                'planned_amount' => 450000,
                'priority' => 'high',
                'target_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $shortPlan = ShortPlan::where('space_id', $space->id)->where('title', 'Beli Keyboard')->firstOrFail();

        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->post(route('short-plans.realize', $shortPlan), [
                'record_transaction' => 1,
                'account_id' => $account->id,
                'category_id' => $expenseCategory->id,
                'actual_amount' => 400000,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'short_plan',
            'action' => 'realized',
            'subject_id' => $shortPlan->id,
        ]);

        // Recurring schedule create & confirm via service
        $recurringService = app(RecurringService::class);
        $schedule = $recurringService->createSchedule($space, $user, [
            'type' => 'expense',
            'name' => 'Internet Bulanan',
            'source_account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'amount' => 350000,
        ]);
        $recurringService->confirmOccurrence($schedule, now()->toDateString(), $user);

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'recurring',
            'action' => 'created',
            'subject_id' => $schedule->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'recurring',
            'action' => 'confirmed',
            'subject_id' => $schedule->id,
        ]);

        // Assert none of the nested Goal / ShortPlan / Recurring operations emitted duplicate transaction.created logs
        $this->assertSame(
            0,
            ActivityLog::where('space_id', $space->id)
                ->where('module', 'transaction')
                ->where('action', 'created')
                ->count()
        );

        // Credit account & purchase
        $creditService = app(CreditService::class);
        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->post(route('credit.accounts.store'), [
                'name' => 'Kartu BCA',
                'type' => 'credit_card',
                'credit_limit' => 5000000,
                'billing_cycle_day' => 15,
                'due_day' => 25,
            ])
            ->assertRedirect();

        $creditAccount = CreditAccount::where('space_id', $space->id)->firstOrFail();
        $plan = $creditService->recordCreditPurchase($space, $user, [
            'credit_account_id' => $creditAccount->id,
            'category_id' => $expenseCategory->id,
            'name' => 'Monitor Kerja',
            'total_amount' => 2000000,
            'tenor' => 2,
            'start_date' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'credit',
            'action' => 'created',
            'subject_type' => CreditAccount::class,
            'subject_id' => $creditAccount->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'credit',
            'action' => 'created',
            'subject_id' => $plan->id,
        ]);

        // Reminder store + toggle
        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->post(route('reminders.store'), [
                'title' => 'Bayar Listrik',
                'type' => 'bill_payment',
                'channel' => 'email',
                'frequency' => 'monthly',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $reminder = Reminder::where('space_id', $space->id)->where('title', 'Bayar Listrik')->firstOrFail();

        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->post(route('reminders.toggleActive', $reminder))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'reminder',
            'action' => 'created',
            'subject_id' => $reminder->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'reminder',
            'action' => 'toggled',
            'subject_id' => $reminder->id,
        ]);

        // Report CSV export logs report.exported_csv
        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->get(route('reports.export-csv'))
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => $space->id,
            'module' => 'report',
            'action' => 'exported_csv',
        ]);

        // Index page loads and renders recorded logs cleanly
        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('Beli Keyboard')
            ->assertSee('Bayar Listrik');
    }

    public function test_auth_and_profile_actions_log_personal_entries_isolated_from_other_users(): void
    {
        [$userA, $spaceA] = $this->createUserWithPersonalSpace('alpha@example.com', 'Alpha');
        [$userB, $spaceB] = $this->createUserWithPersonalSpace('beta@example.com', 'Beta');

        // Login by User A creates auth.login log with space_id = null
        $this->post('/login', [
            'email' => 'alpha@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => null,
            'actor_id' => $userA->id,
            'module' => 'auth',
            'action' => 'login',
        ]);

        // Profile update by User A
        $this->actingAs($userA)
            ->withSession(['active_space_id' => $spaceA->id])
            ->patch(route('profile.update'), [
                'name' => 'Alpha Updated',
                'email' => 'alpha@example.com',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => null,
            'actor_id' => $userA->id,
            'module' => 'profile',
            'action' => 'updated',
        ]);

        // Create Shared Space with both User A and User B
        $sharedSpace = Space::create([
            'name' => 'Keluarga Bersama',
            'type' => 'shared',
            'owner_id' => $userA->id,
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
        ]);
        $sharedSpace->members()->attach($userA->id, ['role' => 'owner', 'status' => 'active']);
        $sharedSpace->members()->attach($userB->id, ['role' => 'member', 'status' => 'active']);

        Account::create([
            'space_id' => $spaceA->id,
            'name' => 'Kas Rahasia Alpha',
            'type' => 'cash',
            'opening_balance' => 100000,
            'opened_on' => now()->toDateString(),
        ]);

        ActivityLog::create([
            'space_id' => $spaceA->id,
            'actor_id' => $userA->id,
            'actor_name' => 'Alpha',
            'module' => 'account',
            'action' => 'created',
            'event' => 'account.created',
            'subject_label' => 'Kas Rahasia Alpha',
            'description' => 'Membuat rekening Kas Rahasia Alpha',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'space_id' => $sharedSpace->id,
            'actor_id' => $userA->id,
            'actor_name' => 'Alpha',
            'module' => 'budget',
            'action' => 'created',
            'event' => 'budget.created',
            'subject_label' => 'Anggaran Dapur Bersama',
            'description' => 'Membuat anggaran bersama dapur',
            'created_at' => now(),
        ]);

        // User B inside Shared Space sees shared space logs in default 'space' scope,
        // and NEVER sees User A's personal space logs or User A's auth/profile logs.
        $this->actingAs($userB)
            ->withSession(['active_space_id' => $sharedSpace->id])
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('Membuat anggaran bersama dapur')
            ->assertDontSee('Kas Rahasia Alpha')
            ->assertDontSee('Masuk ke akun menggunakan email dan kata sandi');

        // Even with scope=all or scope=account, User B NEVER sees User A's personal auth/profile logs
        $this->actingAs($userB)
            ->withSession(['active_space_id' => $sharedSpace->id])
            ->get(route('activity-logs.index', ['scope' => 'all']))
            ->assertOk()
            ->assertSee('Membuat anggaran bersama dapur')
            ->assertDontSee('Masuk ke akun menggunakan email dan kata sandi')
            ->assertDontSee('Memperbarui informasi profil akun');

        // Even inside User B's own personal space, forged actor_id=$userA->id cannot reveal User A's logs
        $this->actingAs($userB)
            ->withSession(['active_space_id' => $spaceB->id])
            ->get(route('activity-logs.index', ['scope' => 'all', 'actor_id' => $userA->id]))
            ->assertOk()
            ->assertDontSee('Kas Rahasia Alpha')
            ->assertDontSee('Memperbarui informasi profil akun');

        // User A logout creates auth.logout log
        $this->actingAs($userA)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => null,
            'actor_id' => $userA->id,
            'module' => 'auth',
            'action' => 'logout',
        ]);
    }

    public function test_filters_like_escaping_and_csv_export_work_correctly(): void
    {
        [$user, $space] = $this->createUserWithPersonalSpace('filter@example.com', 'Filter User');

        ActivityLog::create([
            'space_id' => $space->id,
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'module' => 'transaction',
            'action' => 'created',
            'event' => 'transaction.created',
            'subject_label' => 'Diskon 50%_Promo',
            'description' => 'Mencatat pengeluaran Diskon 50%_Promo',
            'created_at' => Carbon::now('Asia/Jakarta')->subDays(2),
        ]);

        ActivityLog::create([
            'space_id' => $space->id,
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'module' => 'account',
            'action' => 'updated',
            'event' => 'account.updated',
            'subject_label' => 'Rekening Biasa',
            'description' => 'Memperbarui rekening biasa',
            'created_at' => Carbon::now('Asia/Jakarta'),
        ]);

        // Filter by module
        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->get(route('activity-logs.index', ['module' => 'transaction']))
            ->assertOk()
            ->assertSee('Diskon 50%_Promo')
            ->assertDontSee('Memperbarui rekening biasa');

        // Search with wildcard characters (%) must only match literal %
        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->get(route('activity-logs.index', ['search' => '50%']))
            ->assertOk()
            ->assertSee('Diskon 50%_Promo')
            ->assertDontSee('Memperbarui rekening biasa');

        // Date range filter (only today)
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->get(route('activity-logs.index', ['start_date' => $today, 'end_date' => $today]))
            ->assertOk()
            ->assertSee('Memperbarui rekening biasa')
            ->assertDontSee('Diskon 50%_Promo');

        // CSV Export respects filters and emits UTF-8 BOM
        $response = $this->actingAs($user)
            ->withSession(['active_space_id' => $space->id])
            ->get(route('activity-logs.export-csv', ['module' => 'transaction']));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Diskon 50%_Promo', $csv);
        $this->assertStringNotContainsString('Memperbarui rekening biasa', $csv);
    }

    public function test_space_delete_records_account_level_log(): void
    {
        [$user, $personalSpace] = $this->createUserWithPersonalSpace('owner@example.com', 'Owner');

        $extraSpace = Space::create([
            'name' => 'Proyek Sampingan',
            'type' => 'shared',
            'owner_id' => $user->id,
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
        ]);
        $extraSpace->members()->attach($user->id, ['role' => 'owner', 'status' => 'active']);

        $this->actingAs($user)
            ->withSession(['active_space_id' => $personalSpace->id])
            ->delete(route('spaces.destroy', $extraSpace))
            ->assertRedirect(route('spaces.index'));

        $this->assertDatabaseHas('activity_logs', [
            'space_id' => null,
            'actor_id' => $user->id,
            'module' => 'space',
            'action' => 'deleted',
            'subject_label' => 'Proyek Sampingan',
        ]);
    }
}
