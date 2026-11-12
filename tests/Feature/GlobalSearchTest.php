<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Member;
use App\Models\RecurringSchedule;
use App\Models\Reminder;
use App\Models\ShortPlan;
use App\Models\Space;
use App\Models\User;
use App\Services\CreditService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Space $spaceA;

    private Space $spaceB;

    private Account $bcaA;

    private Account $gopayA;

    private Category $expenseCatA;

    private Category $incomeCatA;

    private TransactionService $txService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->txService = app(TransactionService::class);

        $this->userA = User::factory()->create(['name' => 'User A', 'email' => 'search-a@example.com']);
        $this->spaceA = Space::create([
            'name' => 'Ruang Search A',
            'type' => 'shared',
            'owner_id' => $this->userA->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceA->members()->attach($this->userA->id, ['role' => 'owner', 'status' => 'active']);

        $this->userB = User::factory()->create(['name' => 'User B', 'email' => 'search-b@example.com']);
        $this->spaceB = Space::create([
            'name' => 'Ruang Search B',
            'type' => 'personal',
            'owner_id' => $this->userB->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->spaceB->members()->attach($this->userB->id, ['role' => 'owner', 'status' => 'active']);

        $this->bcaA = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Bank BCA Utama',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->gopayA = Account::create([
            'space_id' => $this->spaceA->id,
            'name' => 'GoPay Harian',
            'type' => 'e-wallet',
            'opening_balance' => 500000,
            'opened_on' => '2026-01-01',
        ]);

        $this->expenseCatA = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Kopi & Kuliner',
            'type' => 'expense',
        ]);

        $this->incomeCatA = Category::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Gaji Proyek',
            'type' => 'income',
        ]);
    }

    public function test_guest_cannot_access_global_search_endpoint(): void
    {
        $this->get(route('search.global', ['q' => 'kopi']))
            ->assertRedirect(route('login'));

        $this->getJson(route('search.global', ['q' => 'kopi']))
            ->assertUnauthorized();
    }

    public function test_dashboard_renders_topbar_advanced_search_controls_and_modal(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('id="globalSearchInput"', false);
        $response->assertDontSee('id="globalSearchAdvancedBtn"', false);
        $response->assertSee('id="mobileSearchToggle"', false);
        $response->assertSee('id="globalAdvancedSearchModal"', false);
        $response->assertSee('id="advSearchQueryInput"', false);
        $response->assertSee('id="advSearchFilterPanel"', false);
    }

    public function test_global_search_finds_matching_records_across_modules_and_enforces_space_isolation(): void
    {
        $memberA = Member::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Budi Santoso',
        ]);

        $txA = $this->txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->gopayA->id,
            'category_id' => $this->expenseCatA->id,
            'member_id' => $memberA->id,
            'amount' => 45000,
            'transaction_date' => '2026-03-10',
            'description' => 'Langganan Kopi Robusta',
        ]);

        Goal::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Dana Kopi & Mesin Espresso',
            'target_amount' => 5000000,
            'current_amount' => 1000000,
            'start_date' => '2026-03-01',
            'status' => 'active',
            'created_by' => $this->userA->id,
        ]);

        ShortPlan::create([
            'space_id' => $this->spaceA->id,
            'title' => 'Beli Biji Kopi Bulanan',
            'planned_amount' => 250000,
            'target_date' => '2026-03-20',
            'status' => 'planned',
            'created_by' => $this->userA->id,
        ]);

        RecurringSchedule::create([
            'space_id' => $this->spaceA->id,
            'type' => 'expense',
            'name' => 'Paket Kopi Mingguan',
            'category_id' => $this->expenseCatA->id,
            'source_account_id' => $this->gopayA->id,
            'frequency' => 'weekly',
            'start_date' => '2026-03-01',
            'next_due_date' => '2026-03-17',
            'amount' => 100000,
            'status' => 'active',
            'created_by' => $this->userA->id,
        ]);

        Budget::create([
            'space_id' => $this->spaceA->id,
            'category_id' => $this->expenseCatA->id,
            'year' => 2026,
            'month' => 3,
            'limit_amount' => 750000,
        ]);

        Reminder::create([
            'space_id' => $this->spaceA->id,
            'user_id' => $this->userA->id,
            'title' => 'Restock Kopi Kantor',
            'type' => 'custom',
            'channel' => 'email',
            'frequency' => 'once',
            'due_date' => '2026-03-25',
            'is_active' => true,
        ]);

        $creditService = app(CreditService::class);
        $creditAcc = $creditService->createCreditAccount($this->spaceA, [
            'name' => 'Kartu Kopi Rewards',
            'type' => 'credit_card',
            'credit_limit' => 10000000,
            'billing_cycle_day' => 5,
            'due_day' => 20,
        ]);

        $plan = $creditService->recordCreditPurchase($this->spaceA, $this->userA, [
            'credit_account_id' => $creditAcc->id,
            'category_id' => $this->expenseCatA->id,
            'name' => 'Cicilan Grinder Kopi',
            'total_amount' => 2400000,
            'tenor' => 6,
            'start_date' => '2026-03-01',
        ]);

        // Space B data with same keyword MUST NOT appear
        $accB = Account::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Dompet Kopi Rahasia B',
            'type' => 'cash',
            'opening_balance' => 100000,
            'opened_on' => '2026-01-01',
        ]);
        $catB = Category::create([
            'space_id' => $this->spaceB->id,
            'name' => 'Kopi Space B',
            'type' => 'expense',
        ]);
        $txB = $this->txService->recordExpense($this->spaceB, $this->userB, [
            'account_id' => $accB->id,
            'category_id' => $catB->id,
            'amount' => 99000,
            'transaction_date' => '2026-03-10',
            'description' => 'Kopi Rahasia Space B',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->getJson(route('search.global', ['q' => 'Kopi']));

        $response->assertOk();
        $response->assertJsonPath('query', 'Kopi');

        $data = $response->json();

        $txIds = collect($data['groups']['transactions'])->pluck('id')->all();
        $this->assertContains($txA->id, $txIds);
        $this->assertContains($plan->financial_transaction_id, $txIds);
        $this->assertNotContains($txB->id, $txIds);

        $this->assertSame(['Dana Kopi & Mesin Espresso'], collect($data['groups']['goals'])->pluck('name')->all());
        $this->assertSame(['Beli Biji Kopi Bulanan'], collect($data['groups']['short_plans'])->pluck('title')->all());
        $this->assertSame(['Paket Kopi Mingguan'], collect($data['groups']['recurring'])->pluck('name')->all());
        $this->assertSame(['Kopi & Kuliner'], collect($data['groups']['categories'])->pluck('name')->all());
        $this->assertSame(['Kopi & Kuliner'], collect($data['groups']['budgets'])->pluck('category_name')->all());
        $this->assertSame(['Restock Kopi Kantor'], collect($data['groups']['reminders'])->pluck('title')->all());
        $this->assertCount(2, $data['groups']['credit']);
        $this->assertSame(['Kartu Kopi Rewards (Kewajiban)'], collect($data['groups']['accounts'])->pluck('name')->all());
    }

    public function test_global_search_supports_advanced_transaction_filters_and_amount_ranges(): void
    {
        $memberA = Member::create([
            'space_id' => $this->spaceA->id,
            'name' => 'Siti Aminah',
        ]);

        $smallExpense = $this->txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->gopayA->id,
            'category_id' => $this->expenseCatA->id,
            'amount' => 25000,
            'transaction_date' => '2026-03-05',
            'description' => 'Jajan Kecil',
        ]);

        $targetExpense = $this->txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bcaA->id,
            'category_id' => $this->expenseCatA->id,
            'member_id' => $memberA->id,
            'amount' => 150000,
            'transaction_date' => '2026-03-12',
            'description' => 'Makan Malam Bersama',
        ]);

        $largeIncome = $this->txService->recordIncome($this->spaceA, $this->userA, [
            'account_id' => $this->bcaA->id,
            'category_id' => $this->incomeCatA->id,
            'amount' => 3000000,
            'transaction_date' => '2026-03-15',
            'description' => 'Termin Proyek',
        ]);

        $voidedExpense = $this->txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bcaA->id,
            'category_id' => $this->expenseCatA->id,
            'amount' => 200000,
            'transaction_date' => '2026-03-18',
            'description' => 'Transaksi Salah Input',
        ]);
        $this->txService->voidTransaction($voidedExpense, $this->userA, 'Salah nominal');

        $futureDate = $this->spaceA->now()->addDays(14)->toDateString();
        $futureExpense = $this->txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bcaA->id,
            'category_id' => $this->expenseCatA->id,
            'amount' => 180000,
            'transaction_date' => $futureDate,
            'description' => 'Tagihan Mendatang',
        ]);

        // Filter by type + account + member + date range + formatted Indonesian min/max amount
        $filtered = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->getJson(route('search.global', [
                'scope' => 'transactions',
                'type' => 'expense',
                'account_id' => $this->bcaA->id,
                'member_id' => $memberA->id,
                'start_date' => '2026-03-01',
                'end_date' => '2026-03-31',
                'min_amount' => '100.000',
                'max_amount' => '500.000',
            ]))
            ->assertOk()
            ->json();

        $this->assertSame([$targetExpense->id], collect($filtered['groups']['transactions'])->pluck('id')->all());
        $this->assertSame(1, $filtered['counts']['transactions']);

        // Filter void status specifically
        $voidOnly = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->getJson(route('search.global', [
                'scope' => 'transactions',
                'status' => 'void',
            ]))
            ->assertOk()
            ->json();

        $this->assertSame([$voidedExpense->id], collect($voidOnly['groups']['transactions'])->pluck('id')->all());

        // Exclude future when include_future=0
        $pastOnly = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->getJson(route('search.global', [
                'scope' => 'transactions',
                'include_future' => 0,
            ]))
            ->assertOk()
            ->json();

        $pastIds = collect($pastOnly['groups']['transactions'])->pluck('id')->all();
        $this->assertNotContains($futureExpense->id, $pastIds);
        $this->assertContains($largeIncome->id, $pastIds);
        $this->assertContains($smallExpense->id, $pastIds);

        // Sort by amount descending
        $sortedDesc = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->getJson(route('search.global', [
                'scope' => 'transactions',
                'sort' => 'amount_desc',
                'include_future' => 0,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(
            [$largeIncome->id, $targetExpense->id, $smallExpense->id],
            collect($sortedDesc['groups']['transactions'])->pluck('id')->all()
        );
    }

    public function test_global_search_matches_accounts_and_pages_and_validates_inputs(): void
    {
        $pageRes = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->getJson(route('search.global', [
                'q' => 'Laporan',
                'scope' => 'pages',
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(['Laporan Keuangan'], collect($pageRes['groups']['pages'])->pluck('title')->all());

        $accRes = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->getJson(route('search.global', [
                'q' => 'E-Wallet',
                'scope' => 'accounts',
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(['GoPay Harian'], collect($accRes['groups']['accounts'])->pluck('name')->all());

        $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->getJson(route('search.global', ['scope' => 'invalid_scope']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['scope']);
    }

    public function test_non_json_global_search_request_redirects_to_transactions_index_with_filters(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('search.global', [
                'q' => 'Makan',
                'type' => 'expense',
                'min_amount' => '25.000',
            ]));

        $response->assertRedirect(route('transactions.index', [
            'search' => 'Makan',
            'type' => 'expense',
            'min_amount' => 25000,
        ]));
    }

    public function test_transactions_index_supports_min_and_max_amount_filters(): void
    {
        $txLow = $this->txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bcaA->id,
            'category_id' => $this->expenseCatA->id,
            'amount' => 20000,
            'transaction_date' => '2026-03-01',
            'description' => 'Murah',
        ]);

        $txMid = $this->txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bcaA->id,
            'category_id' => $this->expenseCatA->id,
            'amount' => 120000,
            'transaction_date' => '2026-03-02',
            'description' => 'Sedang',
        ]);

        $txHigh = $this->txService->recordExpense($this->spaceA, $this->userA, [
            'account_id' => $this->bcaA->id,
            'category_id' => $this->expenseCatA->id,
            'amount' => 900000,
            'transaction_date' => '2026-03-03',
            'description' => 'Mahal',
        ]);

        $res = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->get(route('transactions.index', [
                'min_amount' => '100.000',
                'max_amount' => '500.000',
            ]));

        $res->assertOk();
        $ids = $res->viewData('transactions')->pluck('id')->all();
        $this->assertSame([$txMid->id], $ids);
        $this->assertNotContains($txLow->id, $ids);
        $this->assertNotContains($txHigh->id, $ids);
    }

    public function test_global_search_labels_menu_and_general_pages_with_explicit_urls(): void
    {
        $menuRes = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->getJson(route('search.global', ['q' => 'Laporan']));

        $menuRes->assertOk();
        $menuPages = collect($menuRes->json('groups.pages'));
        $reportPage = $menuPages->firstWhere('title', 'Laporan Keuangan');
        $this->assertNotNull($reportPage);
        $this->assertSame('Menu', $reportPage['kind_label']);
        $this->assertSame(route('reports.index'), $reportPage['url']);

        $generalRes = $this->actingAs($this->userA)
            ->withSession(['active_space_id' => $this->spaceA->id])
            ->getJson(route('search.global', ['q' => 'Log Aktivitas']));

        $generalRes->assertOk();
        $generalPages = collect($generalRes->json('groups.pages'));
        $logPage = $generalPages->firstWhere('title', 'Log Aktivitas');
        $this->assertNotNull($logPage);
        $this->assertSame('General', $logPage['kind_label']);
        $this->assertSame(route('activity-logs.index'), $logPage['url']);
    }
}
