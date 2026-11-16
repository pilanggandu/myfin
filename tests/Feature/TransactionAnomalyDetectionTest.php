<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Space;
use App\Models\User;
use App\Services\TransactionAnomalyService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransactionAnomalyDetectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Space $space;

    private Account $bca;

    private Category $foodCategory;

    private Category $techCategory;

    private Category $salaryCategory;

    private TransactionService $transactionService;

    private TransactionAnomalyService $anomalyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.com']);
        $this->space = Space::create([
            'name' => 'Ruang Pribadi',
            'type' => 'personal',
            'owner_id' => $this->user->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->space->members()->attach($this->user->id, ['role' => 'owner', 'status' => 'active']);

        $this->bca = Account::create([
            'space_id' => $this->space->id,
            'name' => 'BCA Utama',
            'type' => 'bank',
            'opening_balance' => 15000000,
            'opened_on' => '2026-01-01',
        ]);

        $this->foodCategory = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Makanan & Minuman',
            'type' => 'expense',
        ]);

        $this->techCategory = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Elektronik & Gadget',
            'type' => 'expense',
        ]);

        $this->salaryCategory = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Gaji Pokok',
            'type' => 'income',
        ]);

        $this->transactionService = app(TransactionService::class);
        $this->anomalyService = app(TransactionAnomalyService::class);
    }

    public function test_detects_potential_duplicate_transactions(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        // Catat transaksi pertama
        $tx1 = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 75000,
            'transaction_date' => '2026-06-15',
            'transaction_time' => '12:05:00',
            'description' => 'Makan Siang Resto',
        ]);

        // Catat transaksi kedua dengan nominal dan rincian sama persis pada hari yang sama
        $tx2 = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 75000,
            'transaction_date' => '2026-06-15',
            'transaction_time' => '12:06:00',
            'description' => 'Makan Siang Resto',
        ]);

        $result = $this->anomalyService->detectAnomaliesForSpace($this->space, '2026-06-01', '2026-06-30');

        $this->assertGreaterThanOrEqual(1, $result['summary']['duplicate_count']);
        $this->assertContains($tx1->id, $result['duplicate_ids']);
        $this->assertContains($tx2->id, $result['duplicate_ids']);

        // Periksa deteksi untuk single transaction (detail view)
        $detailAnomaly = $this->anomalyService->detectAnomaliesForSingleTransaction($tx1);
        $this->assertTrue($detailAnomaly['has_anomaly']);
        $this->assertTrue($detailAnomaly['is_duplicate']);
        $this->assertContains($tx2->id, $detailAnomaly['duplicate_with']);
    }

    public function test_voided_transactions_are_not_flagged_as_duplicate(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        $tx1 = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 50000,
            'transaction_date' => '2026-06-15',
            'description' => 'Kopi Pagi',
        ]);

        $tx2 = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 50000,
            'transaction_date' => '2026-06-15',
            'description' => 'Kopi Pagi',
        ]);

        // Batalkan (void) tx2
        $this->transactionService->voidTransaction($tx2, $this->user, 'Input salah');

        $result = $this->anomalyService->detectAnomaliesForSpace($this->space, '2026-06-01', '2026-06-30');

        // Karena satu sudah void, tidak ada lagi duplikat aktif
        $this->assertSame(0, $result['summary']['duplicate_count']);
        $this->assertNotContains($tx1->id, $result['duplicate_ids']);
    }

    public function test_transactions_with_different_types_or_distant_dates_are_not_duplicates(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        // Transaksi 1: Pemasukan 500.000 pada 15 Juni
        $this->transactionService->recordIncome($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->salaryCategory->id,
            'amount' => 500000,
            'transaction_date' => '2026-06-15',
            'description' => 'Bonus Proyek',
        ]);

        // Transaksi 2: Pengeluaran 500.000 pada 15 Juni (tipe berbeda -> bukan duplikat)
        $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->techCategory->id,
            'amount' => 500000,
            'transaction_date' => '2026-06-15',
            'description' => 'Beli Keyboard',
        ]);

        // Transaksi 3: Pengeluaran 500.000 tapi selisih 7 hari (22 Juni -> bukan duplikat)
        $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->techCategory->id,
            'amount' => 500000,
            'transaction_date' => '2026-06-22',
            'description' => 'Beli Monitor Bekas',
        ]);

        $result = $this->anomalyService->detectAnomaliesForSpace($this->space, '2026-06-01', '2026-06-30');
        $this->assertSame(0, $result['summary']['duplicate_count']);
    }

    public function test_detects_amount_outliers_above_category_habit(): void
    {
        Carbon::setTestNow('2026-06-20 10:00:00');

        // Buat 4 transaksi normal di kategori Makanan (rata-rata ~ Rp 40.000)
        $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 35000,
            'transaction_date' => '2026-06-01',
            'description' => 'Sarapan',
        ]);
        $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 45000,
            'transaction_date' => '2026-06-05',
            'description' => 'Makan Siang',
        ]);
        $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 40000,
            'transaction_date' => '2026-06-10',
            'description' => 'Makan Malam',
        ]);
        $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 40000,
            'transaction_date' => '2026-06-12',
            'description' => 'Makan Siang',
        ]);

        // Catat 1 transaksi bernominal ekstrem: Rp 1.500.000 (jauh di atas rata-rata Rp 40.000)
        $outlierTx = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 1500000,
            'transaction_date' => '2026-06-18',
            'description' => 'Traktir Makan Mewah Satu Kantor',
        ]);

        $result = $this->anomalyService->detectAnomaliesForSpace($this->space, '2026-06-01', '2026-06-30');

        $this->assertGreaterThanOrEqual(1, $result['summary']['outlier_count']);
        $this->assertContains($outlierTx->id, $result['outlier_ids']);

        $detailAnomaly = $this->anomalyService->detectAnomaliesForSingleTransaction($outlierTx);
        $this->assertTrue($detailAnomaly['has_anomaly']);
        $this->assertTrue($detailAnomaly['is_outlier']);
        $this->assertNotNull($detailAnomaly['outlier_data']);
        $this->assertGreaterThanOrEqual(3.0, $detailAnomaly['outlier_data']['multiplier']);
    }

    public function test_detects_category_spending_spike(): void
    {
        // Bulan 1 (Mei 2026): Belanja teknologi hanya Rp 300.000
        $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->techCategory->id,
            'amount' => 300000,
            'transaction_date' => '2026-05-10',
            'description' => 'Beli Kabel & Charger',
        ]);

        // Bulan 2 (Juni 2026): Belanja teknologi melonjak menjadi Rp 3.000.000 (+900%)
        $txSpike = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->techCategory->id,
            'amount' => 3000000,
            'transaction_date' => '2026-06-10',
            'description' => 'Beli Tablet Baru',
        ]);

        $spikes = $this->anomalyService->detectCategorySpikes($this->space, '2026-06-01', '2026-06-30');

        $this->assertNotEmpty($spikes);
        $techSpike = collect($spikes)->firstWhere('category_id', $this->techCategory->id);
        $this->assertNotNull($techSpike);
        $this->assertSame(3000000, $techSpike['current_amount']);
        $this->assertSame(300000, $techSpike['prev_amount']);
        $this->assertSame(2700000, $techSpike['diff_amount']);
        $this->assertEquals(900.0, $techSpike['diff_pct']);

        // Periksa deteksi transaksi di dalam kategori yang melonjak
        $detailAnomaly = $this->anomalyService->detectAnomaliesForSingleTransaction($txSpike);
        $this->assertTrue($detailAnomaly['is_category_spike']);
    }

    public function test_transactions_index_page_displays_anomaly_banner_and_filters(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        // Buat pasangan duplikat
        $tx1 = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 125000,
            'transaction_date' => '2026-06-15',
            'description' => 'Belanja Supermarket',
        ]);

        $tx2 = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 125000,
            'transaction_date' => '2026-06-15',
            'description' => 'Belanja Supermarket',
        ]);

        // Akses daftar transaksi normal
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('Deteksi Transaksi Tidak Biasa');
        $response->assertSee('Potensi Ganda');

        // Akses dengan filter khusus potensi ganda
        $filterResponse = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('transactions.index', ['unusual' => 'duplicate']));

        $filterResponse->assertOk();
        $filterResponse->assertSee('Filter Aktif: Potensi Ganda');
        $filterResponse->assertSee('Belanja Supermarket');

        // Akses dengan filter khusus outlier (kosong untuk dataset ini)
        $outlierFilterResponse = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('transactions.index', ['unusual' => 'outlier']));

        $outlierFilterResponse->assertOk();
        $outlierFilterResponse->assertSee('Filter Aktif: Nominal Ekstrem');

        // Akses dengan filter khusus spike (kosong untuk dataset ini)
        $spikeFilterResponse = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('transactions.index', ['unusual' => 'spike']));

        $spikeFilterResponse->assertOk();
        $spikeFilterResponse->assertSee('Filter Aktif: Lonjakan Kategori');
    }

    public function test_transaction_show_displays_anomaly_alert_card(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $tx1 = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 88000,
            'transaction_date' => '2026-06-15',
            'description' => 'Makan Bareng Rekan',
        ]);

        $tx2 = $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 88000,
            'transaction_date' => '2026-06-15',
            'description' => 'Makan Bareng Rekan',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('transactions.show', $tx1));

        $response->assertOk();
        $response->assertSee('Peringatan: Transaksi Tidak Biasa');
        $response->assertSee('Potensi Transaksi Ganda');
        $response->assertSee('Transaksi #'.$tx2->id);
    }

    public function test_advanced_analytics_surfaces_anomaly_section_and_insights(): void
    {
        Carbon::setTestNow('2026-06-25 10:00:00');

        // Buat duplikat
        $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 99000,
            'transaction_date' => '2026-06-20',
            'description' => 'Pizza Delivery',
        ]);
        $this->transactionService->recordExpense($this->space, $this->user, [
            'account_id' => $this->bca->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 99000,
            'transaction_date' => '2026-06-20',
            'description' => 'Pizza Delivery',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get('/reports?tab=advanced&year=2026&month=6');

        $response->assertOk();
        $response->assertSee('Deteksi Transaksi Tidak Biasa &amp; Anomali', false);
        $response->assertSee('Potensi Transaksi Ganda');

        $aa = $response->viewData('advancedAnalytics');
        $this->assertArrayHasKey('anomalyAnalysis', $aa);
        $this->assertGreaterThanOrEqual(1, $aa['anomalyAnalysis']['summary']['duplicate_count']);
    }
}
