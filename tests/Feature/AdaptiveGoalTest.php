<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalContribution;
use App\Models\Space;
use App\Models\User;
use App\Services\AdaptiveGoalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdaptiveGoalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Space $space;

    private Account $account;

    private Category $category;

    private AdaptiveGoalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Gilan Tester', 'email' => 'gilan@example.com']);
        $this->space = Space::create([
            'name' => 'Ruang Tabungan',
            'type' => 'personal',
            'owner_id' => $this->user->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->space->members()->attach($this->user->id, ['role' => 'owner', 'status' => 'active']);

        $this->account = Account::create([
            'space_id' => $this->space->id,
            'name' => 'BCA Utama',
            'type' => 'bank',
            'opening_balance' => 20000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->category = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Tabungan Masa Depan',
            'type' => 'expense',
            'color' => '#174C40',
            'icon' => '🎯',
        ]);

        $this->service = app(AdaptiveGoalService::class);
    }

    public function test_calculates_adaptive_deposit_requirements_for_fresh_goal(): void
    {
        // Target 12 juta dalam 12 bulan (365 hari)
        $start = Carbon::create(2026, 1, 1, 0, 0, 0, 'Asia/Jakarta');
        $targetDate = Carbon::create(2027, 1, 1, 0, 0, 0, 'Asia/Jakarta');
        $now = $start->copy();

        $goal = Goal::create([
            'space_id' => $this->space->id,
            'name' => 'Dana Darurat 12 Juta',
            'target_amount' => 12000000,
            'current_amount' => 0,
            'start_date' => $start,
            'target_date' => $targetDate,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $pace = $this->service->calculatePace($goal, $now);

        $this->assertTrue($pace['has_target_date']);
        $this->assertFalse($pace['is_overdue']);
        $this->assertFalse($pace['is_achieved']);
        $this->assertSame('on_track', $pace['pace_status']);
        $this->assertEquals(0.0, $pace['expected_amount_today']);
        $this->assertEquals(0.0, $pace['variance_amount']);
        $this->assertEquals(12000000, $pace['remaining_amount']);

        // Setoran bulanan sekitar 1 juta per bulan
        $this->assertGreaterThan(950000, $pace['required_monthly_deposit']);
        $this->assertLessThan(1050000, $pace['required_monthly_deposit']);

        // Setoran mingguan sekitar 230 ribu per minggu
        $this->assertGreaterThan(220000, $pace['required_weekly_deposit']);
        $this->assertLessThan(240000, $pace['required_weekly_deposit']);
    }

    public function test_detects_behind_pace_and_calculates_catch_up_requirements(): void
    {
        // Target 12 juta, mulai 2026-01-01 s/d 2027-01-01 (365 hari)
        $start = Carbon::create(2026, 1, 1, 0, 0, 0, 'Asia/Jakarta');
        $targetDate = Carbon::create(2027, 1, 1, 0, 0, 0, 'Asia/Jakarta');

        // Hari ke-182 (tengah tahun ~50%), ekspektasi ideal ~6 juta
        $now = $start->copy()->addDays(182);

        // Pengguna baru mengumpulkan 2 juta (tertinggal ~4 juta atau ~33% dari target -> critical)
        $goal = Goal::create([
            'space_id' => $this->space->id,
            'name' => 'Dana Liburan',
            'target_amount' => 12000000,
            'current_amount' => 2000000,
            'start_date' => $start,
            'target_date' => $targetDate,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $pace = $this->service->calculatePace($goal, $now);

        $this->assertSame('critical', $pace['pace_status']);
        $this->assertGreaterThan(0, $pace['catch_up_lump_sum']);
        $this->assertEquals(10000000, $pace['remaining_amount']);

        // Setoran bulanan adaptif meningkat dari awal 1 juta/bln menjadi ~1.66 juta/bln
        $this->assertGreaterThan(1600000, $pace['required_monthly_deposit']);

        // Ada estimasi tanggal realistis jika laju historis dipertahankan
        $this->assertNotNull($pace['projected_completion_date']);
        $this->assertGreaterThan(0, $pace['projected_delay_days']);

        // Rekomendasi mencakup opsi setoran adaptif baru, setoran lump sum, dan perpanjangan tenggat
        $this->assertCount(3, $pace['recommendations']);
        $this->assertStringContainsString('Setoran Adaptif Baru', $pace['recommendations'][0]);
        $this->assertStringContainsString('Setoran Penyelamat Sekaligus', $pace['recommendations'][1]);
    }

    public function test_detects_ahead_pace_when_savings_exceed_timeline(): void
    {
        $start = Carbon::create(2026, 1, 1, 0, 0, 0, 'Asia/Jakarta');
        $targetDate = Carbon::create(2027, 1, 1, 0, 0, 0, 'Asia/Jakarta');

        // Hari ke-90 (~25%), ekspektasi ~3 juta
        $now = $start->copy()->addDays(90);

        // Pengguna sudah mengumpulkan 6 juta (+3 juta di atas ekspektasi -> ahead)
        $goal = Goal::create([
            'space_id' => $this->space->id,
            'name' => 'Beli Laptop',
            'target_amount' => 12000000,
            'current_amount' => 6000000,
            'start_date' => $start,
            'target_date' => $targetDate,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $pace = $this->service->calculatePace($goal, $now);

        $this->assertSame('ahead', $pace['pace_status']);
        $this->assertGreaterThan(0, $pace['variance_amount']);
        $this->assertEquals(0.0, $pace['catch_up_lump_sum']);

        // Beban bulanan adaptif turun dari ~1 juta/bln menjadi ~664 ribu/bln
        $this->assertLessThan(750000, $pace['required_monthly_deposit']);

        // Proyeksi tanggal selesai lebih cepat dari target awal
        $this->assertNotNull($pace['projected_completion_date']);
        $this->assertLessThan(0, $pace['projected_delay_days']);
    }

    public function test_detects_overdue_goal(): void
    {
        $start = Carbon::create(2026, 1, 1, 0, 0, 0, 'Asia/Jakarta');
        $targetDate = Carbon::create(2026, 6, 1, 0, 0, 0, 'Asia/Jakarta');

        // Waktu sekarang melewati tanggal target
        $now = Carbon::create(2026, 7, 1, 0, 0, 0, 'Asia/Jakarta');

        $goal = Goal::create([
            'space_id' => $this->space->id,
            'name' => 'Target Telat',
            'target_amount' => 5000000,
            'current_amount' => 3000000,
            'start_date' => $start,
            'target_date' => $targetDate,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $pace = $this->service->calculatePace($goal, $now);

        $this->assertTrue($pace['is_overdue']);
        $this->assertSame('overdue', $pace['pace_status']);
        $this->assertEquals(2000000, $pace['remaining_amount']);
        $this->assertEquals(2000000, $pace['required_monthly_deposit']);
    }

    public function test_handles_flexible_goal_without_target_date(): void
    {
        $goal = Goal::create([
            'space_id' => $this->space->id,
            'name' => 'Tabungan Fleksibel',
            'target_amount' => 10000000,
            'current_amount' => 2500000,
            'start_date' => Carbon::create(2026, 1, 1, 0, 0, 0, 'Asia/Jakarta'),
            'target_date' => null,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $pace = $this->service->calculatePace($goal);

        $this->assertFalse($pace['has_target_date']);
        $this->assertSame('flexible', $pace['pace_status']);
        $this->assertNull($pace['required_monthly_deposit']);
        $this->assertNull($pace['expected_amount_today']);
    }

    public function test_handles_achieved_goal(): void
    {
        $goal = Goal::create([
            'space_id' => $this->space->id,
            'name' => 'Target Sukses',
            'target_amount' => 5000000,
            'current_amount' => 5000000,
            'start_date' => Carbon::create(2026, 1, 1, 0, 0, 0, 'Asia/Jakarta'),
            'target_date' => Carbon::create(2026, 12, 31, 0, 0, 0, 'Asia/Jakarta'),
            'status' => 'achieved',
            'created_by' => $this->user->id,
        ]);

        $pace = $this->service->calculatePace($goal);

        $this->assertTrue($pace['is_achieved']);
        $this->assertSame('achieved', $pace['pace_status']);
        $this->assertEquals(0.0, $pace['remaining_amount']);
        $this->assertEquals(0.0, $pace['required_monthly_deposit']);
    }

    public function test_adaptive_summary_aggregates_multiple_goals(): void
    {
        $now = Carbon::create(2026, 6, 1, 0, 0, 0, 'Asia/Jakarta');

        // Goal 1: On track
        $g1 = Goal::create([
            'space_id' => $this->space->id,
            'name' => 'Target 1',
            'target_amount' => 10000000,
            'current_amount' => 5000000,
            'start_date' => Carbon::create(2026, 1, 1, 0, 0, 0, 'Asia/Jakarta'),
            'target_date' => Carbon::create(2026, 11, 1, 0, 0, 0, 'Asia/Jakarta'),
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // Goal 2: Critical behind
        $g2 = Goal::create([
            'space_id' => $this->space->id,
            'name' => 'Target 2',
            'target_amount' => 10000000,
            'current_amount' => 500000,
            'start_date' => Carbon::create(2026, 1, 1, 0, 0, 0, 'Asia/Jakarta'),
            'target_date' => Carbon::create(2026, 11, 1, 0, 0, 0, 'Asia/Jakarta'),
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $summary = $this->service->calculateSummary(collect([$g1, $g2]), $now);

        $this->assertSame(2, $summary['active_goals']);
        $this->assertSame(1, $summary['on_track_or_ahead_count']);
        $this->assertSame(1, $summary['critical_count']);
        $this->assertGreaterThan(0, $summary['total_monthly_commitment']);
        $this->assertGreaterThan(0, $summary['total_catch_up_needed']);
    }

    public function test_goals_index_displays_adaptive_pace_and_warnings(): void
    {
        $start = Carbon::create(2026, 1, 1, 0, 0, 0, 'Asia/Jakarta');
        $targetDate = Carbon::create(2026, 12, 1, 0, 0, 0, 'Asia/Jakarta');

        // Buat target yang tertinggal
        Goal::create([
            'space_id' => $this->space->id,
            'name' => 'Liburan Jepang',
            'target_amount' => 20000000,
            'current_amount' => 1000000,
            'start_date' => $start,
            'target_date' => $targetDate,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->get(route('goals.index'));

        $response->assertOk();
        $response->assertSee('Liburan Jepang');
        $response->assertSee('KOMITMEN SETORAN ADAPTIF');
        $response->assertSee('Setoran Adaptif');
    }

    public function test_goals_show_displays_adaptive_tracking_and_catch_up_box(): void
    {
        $start = Carbon::create(2026, 1, 1, 0, 0, 0, 'Asia/Jakarta');
        $targetDate = Carbon::create(2027, 1, 1, 0, 0, 0, 'Asia/Jakarta');

        $goal = Goal::create([
            'space_id' => $this->space->id,
            'name' => 'Beli Motor Baru',
            'target_amount' => 15000000,
            'current_amount' => 1000000,
            'start_date' => $start,
            'target_date' => $targetDate,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_space_id' => $this->space->id])
            ->get(route('goals.show', $goal));

        $response->assertOk();
        $response->assertSee('Target Tabungan Adaptif & Laju Waktu');
        $response->assertSee('Target Ideal Hari Ini');
        $response->assertSee('Selisih Laju (Pace)');
        $response->assertSee('Setoran Adaptif / Bulan');
        $response->assertSee('Setoran Adaptif / Minggu');
    }

    public function test_adaptive_pace_recalculates_dynamically_after_new_contribution(): void
    {
        $start = Carbon::create(2026, 1, 1, 0, 0, 0, 'Asia/Jakarta');
        $targetDate = Carbon::create(2026, 11, 1, 0, 0, 0, 'Asia/Jakarta');
        $now = $start->copy();

        $goal = Goal::create([
            'space_id' => $this->space->id,
            'name' => 'Target Dana Siaga',
            'target_amount' => 10000000,
            'current_amount' => 0,
            'start_date' => $start,
            'target_date' => $targetDate,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $initialPace = $goal->adaptivePace($now);
        $initialMonthly = $initialPace['required_monthly_deposit'];

        // Pengguna menyetor 4.000.000
        GoalContribution::create([
            'space_id' => $this->space->id,
            'goal_id' => $goal->id,
            'user_id' => $this->user->id,
            'type' => 'deposit',
            'amount' => 4000000,
            'contribution_date' => $start->toDateString(),
            'created_by' => $this->user->id,
        ]);
        $goal->syncAmount();

        $updatedPace = $goal->adaptivePace($now);
        $updatedMonthly = $updatedPace['required_monthly_deposit'];

        $this->assertEquals(4000000, $goal->current_amount);
        $this->assertEquals(6000000, $updatedPace['remaining_amount']);
        $this->assertLessThan($initialMonthly, $updatedMonthly);
        $this->assertSame('ahead', $updatedPace['pace_status']);
    }

    public function test_goal_model_methods_delegate_to_adaptive_pace(): void
    {
        $start = Carbon::create(2026, 1, 1, 0, 0, 0, 'Asia/Jakarta');
        $targetDate = Carbon::create(2026, 11, 1, 0, 0, 0, 'Asia/Jakarta');

        $goal = Goal::create([
            'space_id' => $this->space->id,
            'name' => 'Target Kursus',
            'target_amount' => 6000000,
            'current_amount' => 1000000,
            'start_date' => $start,
            'target_date' => $targetDate,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $monthly = $goal->monthlyRecommendation($start);
        $weekly = $goal->weeklyRecommendation($start);
        $daily = $goal->dailyRecommendation($start);

        $this->assertNotNull($monthly);
        $this->assertNotNull($weekly);
        $this->assertNotNull($daily);
        $this->assertGreaterThan($weekly, $monthly);
        $this->assertGreaterThan($daily, $weekly);
    }
}
