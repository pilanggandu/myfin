<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\Space;
use App\Models\TransactionRule;
use App\Models\User;
use App\Services\TransactionImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TransactionImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Space $space;

    private Account $bca;

    private Category $foodCategory;

    private Category $salaryCategory;

    private Member $memberGilang;

    private TransactionImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Gilang Pratama', 'email' => 'gilang@example.com']);

        $this->space = Space::create([
            'name' => 'Ruang Pribadi Gilang',
            'type' => 'personal',
            'owner_id' => $this->user->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->space->members()->attach($this->user->id, ['role' => 'owner', 'status' => 'active']);

        $this->bca = Account::create([
            'space_id' => $this->space->id,
            'name' => 'BCA Rekening Utama',
            'type' => 'bank',
            'opening_balance' => 10000000,
            'opened_on' => '2026-01-01',
            'is_default' => true,
        ]);

        $this->foodCategory = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Makanan & Minuman',
            'type' => 'expense',
        ]);

        $this->salaryCategory = Category::create([
            'space_id' => $this->space->id,
            'name' => 'Gaji Pokok',
            'type' => 'income',
        ]);

        $this->memberGilang = Member::create([
            'space_id' => $this->space->id,
            'name' => 'Gilang',
            'color' => '#10b981',
        ]);

        $this->importService = app(TransactionImportService::class);
    }

    public function test_user_can_access_import_form(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('transactions.import'));

        $response->assertStatus(200);
        $response->assertSee('Impor Transaksi &amp; Aturan Otomatis', false);
        $response->assertSee('BCA Rekening Utama');
    }

    public function test_user_can_download_sample_csv(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('transactions.import.sample'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="contoh_impor_transaksi.csv"');
        $this->assertStringContainsString('Tanggal,Keterangan,Nominal,Jenis', $response->getContent());
    }

    public function test_csv_parse_applies_automated_rules_and_enriches_rows(): void
    {
        // 1. Buat aturan otomatis: jika keterangan mengandung "kopi", set Kategori: Makanan & Minuman, Member: Gilang, Catatan: "Kopi santai"
        TransactionRule::create([
            'space_id' => $this->space->id,
            'name' => 'Aturan Auto Kopi',
            'is_active' => true,
            'priority' => 10,
            'type_criterion' => 'expense',
            'keyword' => 'kopi',
            'keyword_operator' => 'contains',
            'amount_operator' => 'any',
            'action_category_id' => $this->foodCategory->id,
            'action_member_id' => $this->memberGilang->id,
            'action_note' => 'Kopi santai',
            'created_by' => $this->user->id,
        ]);

        // 2. Buat berkas CSV tiruan
        $csvContent = implode("\n", [
            'Tanggal,Keterangan,Nominal,Jenis',
            '16/11/2026,Kopi Kenangan Senayan,24.000,Pengeluaran',
            '15/11/2026,Gaji Pokok Bulanan,8.500.000,Pemasukan',
        ]);

        $file = UploadedFile::fake()->createWithContent('mutasi.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->postJson(route('transactions.import.parse'), [
                'account_id' => $this->bca->id,
                'csv_file' => $file,
            ]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(2, $json['summary']['total_rows']);
        $this->assertEquals(1, $json['summary']['matched_rules_count']);

        // Baris pertama (Kopi) harus terisi otomatis oleh aturan
        $kopiRow = $json['rows'][0];
        $this->assertEquals('Kopi Kenangan Senayan', $kopiRow['description']);
        $this->assertEquals(24000, $kopiRow['amount']);
        $this->assertEquals('expense', $kopiRow['type']);
        $this->assertEquals($this->foodCategory->id, $kopiRow['category_id']);
        $this->assertEquals($this->memberGilang->id, $kopiRow['member_id']);
        $this->assertEquals('Kopi santai', $kopiRow['notes']);
        $this->assertEquals('Aturan Auto Kopi', $kopiRow['primary_matched_rule']);

        // Baris kedua (Gaji)
        $gajiRow = $json['rows'][1];
        $this->assertEquals(8500000, $gajiRow['amount']);
        $this->assertEquals('income', $gajiRow['type']);
    }

    public function test_csv_parse_handles_indonesian_number_and_date_formats(): void
    {
        $raw = implode("\n", [
            'Tanggal;Keterangan;Nominal;Jenis',
            '10/11/2026;Alfamart Mingguan;150.750,50;Pengeluaran',
            '11-11-2026;Topup E-Money;-50000;Pengeluaran',
        ]);

        $parsed = $this->importService->parseCsvContent($raw, $this->space, $this->bca->id);

        $this->assertCount(2, $parsed['rows']);
        $this->assertEquals('2026-11-10', $parsed['rows'][0]['date']);
        $this->assertEquals(150750.50, $parsed['rows'][0]['amount']);
        $this->assertEquals('2026-11-11', $parsed['rows'][1]['date']);
        $this->assertEquals(50000, $parsed['rows'][1]['amount']);
    }

    public function test_execute_import_creates_transactions_and_entries_in_database(): void
    {
        $rows = [
            [
                'selected' => true,
                'date' => '2026-11-16',
                'description' => 'Makan Siang Soto Betawi',
                'amount' => 45000,
                'type' => 'expense',
                'account_id' => $this->bca->id,
                'category_id' => $this->foodCategory->id,
                'member_id' => $this->memberGilang->id,
                'notes' => 'Makan bareng tim',
                'primary_matched_rule' => 'Aturan Soto',
            ],
            [
                'selected' => true,
                'date' => '2026-11-15',
                'description' => 'Gaji Pokok Transfer BCA',
                'amount' => 8000000,
                'type' => 'income',
                'account_id' => $this->bca->id,
                'category_id' => $this->salaryCategory->id,
                'notes' => 'Payroll bulan November',
            ],
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('transactions.import.execute'), [
                'account_id' => $this->bca->id,
                'rows' => $rows,
            ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success');

        // Verifikasi transaksi tersimpan di database
        $this->assertDatabaseHas('financial_transactions', [
            'space_id' => $this->space->id,
            'description' => 'Makan Siang Soto Betawi',
            'type' => 'expense',
            'category_id' => $this->foodCategory->id,
            'member_id' => $this->memberGilang->id,
            'notes' => 'Makan bareng tim',
        ]);

        $this->assertDatabaseHas('financial_transactions', [
            'space_id' => $this->space->id,
            'description' => 'Gaji Pokok Transfer BCA',
            'type' => 'income',
            'category_id' => $this->salaryCategory->id,
            'notes' => 'Payroll bulan November',
        ]);

        // Verifikasi entri jurnal akuntansi tersimpan
        $expenseTx = FinancialTransaction::where('description', 'Makan Siang Soto Betawi')->first();
        $this->assertNotNull($expenseTx);
        $this->assertEquals(-45000, (float) $expenseTx->entries->first()->amount_signed);

        $incomeTx = FinancialTransaction::where('description', 'Gaji Pokok Transfer BCA')->first();
        $this->assertNotNull($incomeTx);
        $this->assertEquals(8000000, (float) $incomeTx->entries->first()->amount_signed);
    }

    public function test_execute_import_skips_unselected_rows(): void
    {
        $rows = [
            [
                'selected' => false, // Dilewati!
                'date' => '2026-11-16',
                'description' => 'Transaksi Dilewati',
                'amount' => 10000,
                'type' => 'expense',
                'category_id' => $this->foodCategory->id,
            ],
            [
                'selected' => true,
                'date' => '2026-11-16',
                'description' => 'Transaksi Disimpan',
                'amount' => 20000,
                'type' => 'expense',
                'category_id' => $this->foodCategory->id,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('transactions.import.execute'), [
                'account_id' => $this->bca->id,
                'rows' => $rows,
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertDatabaseMissing('financial_transactions', [
            'description' => 'Transaksi Dilewati',
        ]);

        $this->assertDatabaseHas('financial_transactions', [
            'description' => 'Transaksi Disimpan',
        ]);
    }
}
