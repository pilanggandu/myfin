<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Member;
use App\Models\RecurringOccurrence;
use App\Models\RecurringSchedule;
use App\Models\Space;
use App\Models\User;
use App\Services\SubscriptionAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Space $space;

    private Account $bca;

    private Account $gopay;

    private Category $entertainmentCat;

    private Category $utilitiesCat;

    private Member $memberA;

    private SubscriptionAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Gilan Tester', 'email' => 'gilan@test.com']);
        $this->space = Space::create([
            'name' => 'Ruang Finansial',
            'type' => 'shared',
            'owner_id' => $this->user->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->space->members()->attach($this->user->id, ['role' => 'owner', 'status' => 'active']);

        $this->bca = Account::create([
            'space_id' => $this->space->id,
            'name' => 'Bank BCA',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->gopay = Account::create([
            'space_id' => $this->space->id,
            'name' => 'GoPay',
            'type' => 'e-wallet',
            'opening_balance' => 1000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->entertainmentCat = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Hiburan & Streaming',
            'type' => 'expense',
            'color' => '#8b5cf6',
            'icon' => '🎬',
        ]);

        $this->utilitiesCat = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Internet & Utilitas',
            'type' => 'expense',
            'color' => '#0ea5e9',
            'icon' => '🌐',
        ]);

        $this->memberA = Member::create([
            'space_id' => $this->space->id,
            'name' => 'Anggota A',
            'color' => '#10b981',
            'status' => 'active',
        ]);

        $this->service = app(SubscriptionAnalyticsService::class);
    }

    public function test_normalizes_subscription_costs_across_frequencies(): void
    {
        // 1. Bulanan: 186.000 / bln => 186.000/bln, 2.232.000/thn
        $netflix = RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Netflix Premium',
            'category_id' => $this->entertainmentCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 5,
            'start_date' => '2026-01-05',
            'next_due_date' => '2026-12-05',
            'amount' => 186000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // 2. Tahunan: 1.200.000 / thn => 100.000/bln, 1.200.000/thn
        $googleOne = RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Google One 2TB',
            'category_id' => $this->utilitiesCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'yearly',
            'anchor_day' => 10,
            'start_date' => '2026-01-10',
            'next_due_date' => '2027-01-10',
            'amount' => 1200000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // 3. Mingguan: 50.000 / mgg => 50000 * 52 / 12 = 216.666,67/bln
        $gym = RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Gym Pass Mingguan',
            'category_id' => $this->entertainmentCat->id,
            'source_account_id' => $this->gopay->id,
            'frequency' => 'weekly',
            'anchor_day' => 1,
            'start_date' => '2026-01-01',
            'next_due_date' => '2026-11-20',
            'amount' => 50000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $analytics = $this->service->getAnalytics($this->space);

        $this->assertSame(3, $analytics['summary']['total_subscriptions_count']);

        // Total bulanan ekuivalen: 186.000 + 100.000 + 216.666,67 = 502.666,67
        $this->assertEquals(502666.67, $analytics['summary']['total_monthly_cost']);

        // Model helper methods
        $this->assertEquals(186000, $netflix->normalizedMonthlyCost());
        $this->assertEquals(2232000, $netflix->annualCost());
        $this->assertEquals(100000, $googleOne->normalizedMonthlyCost());
        $this->assertEquals(1200000, $googleOne->annualCost());
        $this->assertEquals(216666.67, $gym->normalizedMonthlyCost());
    }

    public function test_detects_price_increase_from_historical_occurrences(): void
    {
        $spotify = RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Spotify Premium Family',
            'category_id' => $this->entertainmentCat->id,
            'source_account_id' => $this->gopay->id,
            'frequency' => 'monthly',
            'anchor_day' => 15,
            'start_date' => '2026-01-15',
            'next_due_date' => '2026-05-15',
            'amount' => 86000, // Tarif baru
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // Catatan konfirmasi mutasi historis:
        // Jan: 79.000
        RecurringOccurrence::create([
            'recurring_schedule_id' => $spotify->id,
            'space_id' => $this->space->id,
            'due_date' => '2026-01-15',
            'status' => 'confirmed',
            'amount' => 79000,
            'confirmed_at' => '2026-01-15 10:00:00',
            'confirmed_by' => $this->user->id,
        ]);

        // Feb: 79.000
        RecurringOccurrence::create([
            'recurring_schedule_id' => $spotify->id,
            'space_id' => $this->space->id,
            'due_date' => '2026-02-15',
            'status' => 'confirmed',
            'amount' => 79000,
            'confirmed_at' => '2026-02-15 10:00:00',
            'confirmed_by' => $this->user->id,
        ]);

        // Mar: Kenaikan harga menjadi 86.000 (+7.000 / +8.9%)
        RecurringOccurrence::create([
            'recurring_schedule_id' => $spotify->id,
            'space_id' => $this->space->id,
            'due_date' => '2026-03-15',
            'status' => 'confirmed',
            'amount' => 86000,
            'confirmed_at' => '2026-03-15 10:00:00',
            'confirmed_by' => $this->user->id,
        ]);

        $analytics = $this->service->getAnalytics($this->space);

        $this->assertSame(1, $analytics['summary']['price_hike_count']);
        $this->assertEquals(7000, $analytics['summary']['total_monthly_increase']);
        $this->assertEquals(84000, $analytics['summary']['total_annual_increase']);

        $hike = $analytics['price_hikes'][0];
        $this->assertSame('Spotify Premium Family', $hike['name']);
        $this->assertTrue($hike['has_price_increase']);
        $this->assertEquals(79000, $hike['previous_amount']);
        $this->assertEquals(86000, $hike['current_amount']);
        $this->assertEquals(7000, $hike['price_difference']);
        $this->assertEquals(8.9, $hike['percentage_increase']);
        $this->assertEquals(84000, $hike['annual_increase']);
    }

    public function test_groups_subscriptions_by_category(): void
    {
        // 2 Layanan Hiburan
        RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Netflix',
            'category_id' => $this->entertainmentCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 1,
            'start_date' => '2026-01-01',
            'next_due_date' => '2026-12-01',
            'amount' => 186000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Disney+ Hotstar',
            'category_id' => $this->entertainmentCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 1,
            'start_date' => '2026-01-01',
            'next_due_date' => '2026-12-01',
            'amount' => 65000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // 1 Layanan Utilitas
        RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Indihome Fiber',
            'category_id' => $this->utilitiesCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 15,
            'start_date' => '2026-01-15',
            'next_due_date' => '2026-12-15',
            'amount' => 350000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $analytics = $this->service->getAnalytics($this->space);

        $this->assertCount(2, $analytics['by_category']);

        // Urutan berdasarkan total monthly cost terbesar: Utilitas (350.000) > Hiburan (251.000)
        $firstGroup = $analytics['by_category'][0];
        $this->assertSame('Internet & Utilitas', $firstGroup['category_name']);
        $this->assertEquals(350000, $firstGroup['monthly_cost']);
        $this->assertSame(1, $firstGroup['count']);

        $secondGroup = $analytics['by_category'][1];
        $this->assertSame('Hiburan & Streaming', $secondGroup['category_name']);
        $this->assertEquals(251000, $secondGroup['monthly_cost']);
        $this->assertSame(2, $secondGroup['count']);
    }

    public function test_groups_upcoming_timeline_correctly(): void
    {
        $today = Carbon::create(2026, 6, 10, 0, 0, 0, 'Asia/Jakarta');

        // 1. Overdue (2 hari yang lalu: 2026-06-08)
        RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Langganan Telat',
            'category_id' => $this->utilitiesCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 8,
            'start_date' => '2026-01-08',
            'next_due_date' => '2026-06-08',
            'amount' => 100000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // 2. Due Today (Hari ini: 2026-06-10)
        RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Tagihan Hari Ini',
            'category_id' => $this->entertainmentCat->id,
            'source_account_id' => $this->gopay->id,
            'frequency' => 'monthly',
            'anchor_day' => 10,
            'start_date' => '2026-01-10',
            'next_due_date' => '2026-06-10',
            'amount' => 50000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // 3. This Week (+5 hari: 2026-06-15)
        RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Tagihan Minggu Ini',
            'category_id' => $this->entertainmentCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 15,
            'start_date' => '2026-01-15',
            'next_due_date' => '2026-06-15',
            'amount' => 150000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // 4. This Month (+20 hari: 2026-06-30)
        RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Tagihan Akhir Bulan',
            'category_id' => $this->utilitiesCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 30,
            'start_date' => '2026-01-30',
            'next_due_date' => '2026-06-30',
            'amount' => 300000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $analytics = $this->service->getAnalytics($this->space, [], $today);

        $timeline = $analytics['upcoming_timeline'];

        $this->assertSame(1, $timeline['overdue']['count']);
        $this->assertEquals(100000, $timeline['overdue']['total_amount']);

        $this->assertSame(1, $timeline['due_today']['count']);
        $this->assertEquals(50000, $timeline['due_today']['total_amount']);

        $this->assertSame(1, $timeline['this_week']['count']);
        $this->assertEquals(150000, $timeline['this_week']['total_amount']);

        $this->assertSame(1, $timeline['this_month']['count']);
        $this->assertEquals(300000, $timeline['this_month']['total_amount']);

        // Tagihan 7 hari ke depan (Overdue + Due Today + This Week = 100k + 50k + 150k = 300k)
        $this->assertEquals(300000, $analytics['summary']['upcoming_7_days_total']);

        // Tagihan 30 hari ke depan (Semua di atas = 600k)
        $this->assertEquals(600000, $analytics['summary']['upcoming_30_days_total']);
    }

    public function test_handles_empty_space_without_errors(): void
    {
        $analytics = $this->service->getAnalytics($this->space);

        $this->assertSame(0, $analytics['summary']['total_subscriptions_count']);
        $this->assertEquals(0.0, $analytics['summary']['total_monthly_cost']);
        $this->assertEquals(0.0, $analytics['summary']['total_annual_cost']);
        $this->assertSame(0, $analytics['summary']['price_hike_count']);
        $this->assertEmpty($analytics['subscriptions']);
        $this->assertEmpty($analytics['price_hikes']);
    }

    public function test_subscriptions_view_renders_correctly(): void
    {
        // Buat 1 langganan
        RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Apple One Family',
            'category_id' => $this->entertainmentCat->id,
            'source_account_id' => $this->bca->id,
            'member_id' => $this->memberA->id,
            'frequency' => 'monthly',
            'anchor_day' => 12,
            'start_date' => '2026-01-12',
            'next_due_date' => '2026-12-12',
            'amount' => 165000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('recurring.subscriptions'));

        $response->assertOk();
        $response->assertSee('Analisis Langganan');
        $response->assertSee('TOTAL BIAYA LANGGANAN');
        $response->assertSee('Apple One Family');
        $response->assertSee('Berdasarkan Kategori');
        $response->assertSee('Jadwal Berikutnya');
    }

    public function test_subscriptions_view_shows_price_hike_alert(): void
    {
        $icloud = RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'iCloud 2TB',
            'category_id' => $this->utilitiesCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 1,
            'start_date' => '2026-01-01',
            'next_due_date' => '2026-12-01',
            'amount' => 149000, // Naik dari 99.000
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        RecurringOccurrence::create([
            'recurring_schedule_id' => $icloud->id,
            'space_id' => $this->space->id,
            'due_date' => '2026-01-01',
            'status' => 'confirmed',
            'amount' => 99000,
            'confirmed_at' => '2026-01-01 10:00:00',
            'confirmed_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('recurring.subscriptions'));

        $response->assertOk();
        $response->assertSee('Terdeteksi Kenaikan Biaya');
        $response->assertSee('iCloud 2TB');
        $response->assertSee('Rp 99.000');
        $response->assertSee('Rp 149.000');
    }

    public function test_recurring_index_has_shortcut_to_subscription_analytics(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('recurring.index'));

        $response->assertOk();
        $response->assertSee('Analisis Langganan');
        $response->assertSee(route('recurring.subscriptions'));
    }

    public function test_tabs_render_frequency_account_and_member_breakdowns(): void
    {
        RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Canva Pro',
            'category_id' => $this->utilitiesCat->id,
            'source_account_id' => $this->bca->id,
            'member_id' => $this->memberA->id,
            'frequency' => 'monthly',
            'anchor_day' => 1,
            'start_date' => '2026-01-01',
            'next_due_date' => '2026-12-01',
            'amount' => 95000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // Tab Timeline
        $resTimeline = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('recurring.subscriptions', ['tab' => 'timeline']));
        $resTimeline->assertOk();
        $resTimeline->assertSee('Canva Pro');

        // Tab Frequency
        $resFreq = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('recurring.subscriptions', ['tab' => 'frequency']));
        $resFreq->assertOk();
        $resFreq->assertSee('Bulanan');
        $resFreq->assertSee('Tahunan');
        $resFreq->assertSee('Mingguan');

        // Tab Account
        $resAccount = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('recurring.subscriptions', ['tab' => 'account']));
        $resAccount->assertOk();
        $resAccount->assertSee('Bank BCA');

        // Tab Member
        $resMember = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('recurring.subscriptions', ['tab' => 'member']));
        $resMember->assertOk();
        $resMember->assertSee('Anggota A');
    }

    public function test_filter_by_search_and_has_price_hike(): void
    {
        $adobe = RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Adobe Creative Cloud',
            'category_id' => $this->utilitiesCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 5,
            'start_date' => '2026-01-05',
            'next_due_date' => '2026-12-05',
            'amount' => 850000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        RecurringOccurrence::create([
            'recurring_schedule_id' => $adobe->id,
            'space_id' => $this->space->id,
            'due_date' => '2026-01-05',
            'status' => 'confirmed',
            'amount' => 700000,
            'confirmed_at' => '2026-01-05 10:00:00',
            'confirmed_by' => $this->user->id,
        ]);

        $zoom = RecurringSchedule::create([
            'space_id' => $this->space->id,
            'type' => 'expense',
            'name' => 'Zoom One Pro',
            'category_id' => $this->utilitiesCat->id,
            'source_account_id' => $this->bca->id,
            'frequency' => 'monthly',
            'anchor_day' => 5,
            'start_date' => '2026-01-05',
            'next_due_date' => '2026-12-05',
            'amount' => 200000,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // Search test
        $resSearch = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('recurring.subscriptions', ['search' => 'Adobe']));
        $resSearch->assertOk();
        $resSearch->assertSee('Adobe Creative Cloud');
        $resSearch->assertDontSee('Zoom One Pro');

        // Price hike filter test
        $resHike = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('recurring.subscriptions', ['has_price_hike' => '1']));
        $resHike->assertOk();
        $resHike->assertSee('Adobe Creative Cloud');
        $resHike->assertDontSee('Zoom One Pro');
    }
}
