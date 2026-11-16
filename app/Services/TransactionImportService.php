<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Member;
use App\Models\Space;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransactionImportService
{
    public function __construct(
        protected TransactionRuleService $ruleService,
        protected TransactionService $transactionService,
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Parsing isi file atau teks CSV menjadi array baris transaksi yang siap diimpor.
     *
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     summary: array{
     *         total_rows: int,
     *         matched_rules_count: int,
     *         total_expense: float,
     *         total_income: float,
     *         uncategorized_count: int
     *     },
     *     errors: list<string>
     * }
     */
    public function parseCsvContent(string $rawContent, Space $space, ?int $defaultAccountId = null): array
    {
        // 1. Bersihkan UTF-8 BOM dan normalisasi baris
        $content = ltrim($rawContent, "\xEF\xBB\xBF");
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $rawLines = array_filter(explode("\n", $content), fn ($line) => trim((string) $line) !== '');

        if (empty($rawLines)) {
            return [
                'rows' => [],
                'summary' => [
                    'total_rows' => 0,
                    'matched_rules_count' => 0,
                    'total_expense' => 0.0,
                    'total_income' => 0.0,
                    'uncategorized_count' => 0,
                ],
                'errors' => ['File atau teks CSV kosong.'],
            ];
        }

        // 2. Deteksi delimiter (, atau ; atau tab)
        $delimiter = $this->detectDelimiter($rawLines);

        // 3. Pre-load kategori, member, akun, dan aturan untuk performa cepat tanpa N+1
        $categories = $space->activeCategories()->get()->keyBy(fn ($c) => Str::lower(trim($c->name)));
        $members = $space->members()->get()->keyBy(fn ($m) => Str::lower(trim($m->name)));
        $accounts = $space->activeAccounts()->get()->keyBy(fn ($a) => Str::lower(trim($a->name)));
        $activeRules = $space->activeTransactionRules()->with(['actionCategory', 'actionMember'])->get();

        $defaultAccount = $defaultAccountId
            ? $space->accounts()->find($defaultAccountId)
            : $space->defaultAccount();

        // 4. Analisis baris header
        $firstLine = array_shift($rawLines);
        $headerColumns = str_getcsv($firstLine, $delimiter);
        $columnMap = $this->mapHeaders($headerColumns);

        // Jika baris pertama bukan header (misal baris data langsung), kembalikan baris pertama ke data
        $isFirstLineData = $this->isLikelyDataRow($headerColumns, $columnMap);
        if ($isFirstLineData) {
            array_unshift($rawLines, $firstLine);
            $columnMap = [
                'date' => 0,
                'description' => 1,
                'amount' => 2,
                'type' => 3,
                'category' => 4,
                'notes' => 5,
            ];
        }

        $rows = [];
        $errors = [];
        $matchedRulesCount = 0;
        $totalExpense = 0.0;
        $totalIncome = 0.0;
        $uncategorizedCount = 0;

        $rowIndex = 0;
        foreach ($rawLines as $line) {
            $rowIndex++;
            $columns = str_getcsv($line, $delimiter);
            if (empty($columns) || count(array_filter($columns, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $dateRaw = $this->getColumnValue($columns, $columnMap['date'] ?? null);
            $descRaw = $this->getColumnValue($columns, $columnMap['description'] ?? null);
            $amountRaw = $this->getColumnValue($columns, $columnMap['amount'] ?? null);
            $typeRaw = $this->getColumnValue($columns, $columnMap['type'] ?? null);
            $debitRaw = $this->getColumnValue($columns, $columnMap['debit'] ?? null);
            $creditRaw = $this->getColumnValue($columns, $columnMap['credit'] ?? null);
            $categoryRaw = $this->getColumnValue($columns, $columnMap['category'] ?? null);
            $memberRaw = $this->getColumnValue($columns, $columnMap['member'] ?? null);
            $notesRaw = $this->getColumnValue($columns, $columnMap['notes'] ?? null);
            $accountRaw = $this->getColumnValue($columns, $columnMap['account'] ?? null);

            // Parsing tanggal
            $parsedDate = $this->parseDate($dateRaw, $space);

            // Parsing nominal dan jenis
            [$parsedAmount, $parsedType] = $this->parseAmountAndType($amountRaw, $typeRaw, $debitRaw, $creditRaw);

            if ($parsedAmount <= 0) {
                continue; // Lewati jika nominal tidak valid atau nol
            }

            $cleanDescription = trim((string) $descRaw);
            if ($cleanDescription === '') {
                $cleanDescription = 'Transaksi Impor #'.$rowIndex;
            }

            // Temukan kategori awal dari file jika ada
            $initialCategoryId = null;
            $initialCategoryName = null;
            if ($categoryRaw !== null && $categoryRaw !== '') {
                $matchedCat = $categories->get(Str::lower(trim($categoryRaw)));
                if ($matchedCat) {
                    $initialCategoryId = $matchedCat->id;
                    $initialCategoryName = $matchedCat->name;
                }
            }

            // Temukan member awal dari file jika ada
            $initialMemberId = null;
            $initialMemberName = null;
            if ($memberRaw !== null && $memberRaw !== '') {
                $matchedMem = $members->get(Str::lower(trim($memberRaw)));
                if ($matchedMem) {
                    $initialMemberId = $matchedMem->id;
                    $initialMemberName = $matchedMem->name;
                }
            }

            // Rekening
            $resolvedAccountId = $defaultAccount?->id;
            if ($accountRaw !== null && $accountRaw !== '') {
                $matchedAcc = $accounts->get(Str::lower(trim($accountRaw)));
                if ($matchedAcc) {
                    $resolvedAccountId = $matchedAcc->id;
                }
            }

            $rowPayload = [
                'row_index' => $rowIndex,
                'date' => $parsedDate,
                'description' => $cleanDescription,
                'amount' => $parsedAmount,
                'type' => $parsedType,
                'account_id' => $resolvedAccountId,
                'category_id' => $initialCategoryId,
                'category_name' => $initialCategoryName,
                'member_id' => $initialMemberId,
                'member_name' => $initialMemberName,
                'notes' => $notesRaw ? trim((string) $notesRaw) : null,
                'matched_rules' => [],
                'primary_matched_rule' => null,
            ];

            // Terapkan ATURAN OTOMATIS TRANSAKSI!
            $enrichedRow = $this->ruleService->applyRulesToRow($space, $rowPayload, $activeRules);

            if (! empty($enrichedRow['primary_matched_rule'])) {
                $matchedRulesCount++;
            }

            if (empty($enrichedRow['category_id'])) {
                $uncategorizedCount++;
            }

            if ($enrichedRow['type'] === 'income') {
                $totalIncome += $enrichedRow['amount'];
            } else {
                $totalExpense += $enrichedRow['amount'];
            }

            $rows[] = $enrichedRow;
        }

        return [
            'rows' => $rows,
            'summary' => [
                'total_rows' => count($rows),
                'matched_rules_count' => $matchedRulesCount,
                'total_expense' => $totalExpense,
                'total_income' => $totalIncome,
                'uncategorized_count' => $uncategorizedCount,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * Simpan transaksi yang sudah divalidasi ke basis data.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{
     *     imported_count: int,
     *     matched_rules_count: int,
     *     total_expense: float,
     *     total_income: float
     * }
     */
    public function executeImport(Space $space, User $actor, array $rows, int $defaultAccountId): array
    {
        $defaultAccount = $space->accounts()->find($defaultAccountId);
        if (! $defaultAccount || $defaultAccount->isArchived()) {
            throw ValidationException::withMessages([
                'account_id' => 'Rekening tujuan tidak valid atau telah diarsipkan.',
            ]);
        }

        $importedCount = 0;
        $matchedRulesCount = 0;
        $totalExpense = 0.0;
        $totalIncome = 0.0;

        DB::transaction(function () use (
            $space,
            $actor,
            $rows,
            $defaultAccount,
            &$importedCount,
            &$matchedRulesCount,
            &$totalExpense,
            &$totalIncome
        ): void {
            // Ambil semua kategori aktif untuk fallback jika kategori kosong
            $firstExpenseCategory = $space->activeCategories()->expense()->first();
            $firstIncomeCategory = $space->activeCategories()->income()->first();

            foreach ($rows as $row) {
                // Lewati baris yang dinonaktifkan oleh user di preview
                if (isset($row['selected']) && ! $row['selected']) {
                    continue;
                }

                $amount = round((float) ($row['amount'] ?? 0), 2);
                if ($amount <= 0) {
                    continue;
                }

                $type = in_array($row['type'] ?? 'expense', ['income', 'expense'], true)
                    ? $row['type']
                    : 'expense';

                $accountId = ! empty($row['account_id']) ? (int) $row['account_id'] : $defaultAccount->id;
                $categoryId = ! empty($row['category_id']) ? (int) $row['category_id'] : null;

                // Jika kategori belum terisi, fallback ke kategori pertama sesuai jenis
                if (! $categoryId) {
                    $categoryId = $type === 'income'
                        ? $firstIncomeCategory?->id
                        : $firstExpenseCategory?->id;
                }

                if (! $categoryId) {
                    // Jika ruang sama sekali belum punya kategori, buatkan kategori default sederhana
                    $fallbackCat = Category::firstOrCreate(
                        ['space_id' => $space->id, 'name' => $type === 'income' ? 'Lain-lain (Pemasukan)' : 'Lain-lain', 'type' => $type],
                        ['archived_at' => null]
                    );
                    $categoryId = $fallbackCat->id;
                }

                $txData = [
                    'account_id' => $accountId,
                    'category_id' => $categoryId,
                    'member_id' => ! empty($row['member_id']) ? (int) $row['member_id'] : null,
                    'amount' => $amount,
                    'transaction_date' => ! empty($row['date']) ? $row['date'] : $space->now()->toDateString(),
                    'transaction_time' => $space->now()->format('H:i:s'),
                    'description' => (string) ($row['description'] ?? 'Transaksi Impor'),
                    'notes' => ! empty($row['notes']) ? (string) $row['notes'] : null,
                    'skip_activity_log' => true,
                ];

                if ($type === 'income') {
                    $this->transactionService->recordIncome($space, $actor, $txData);
                    $totalIncome += $amount;
                } else {
                    $this->transactionService->recordExpense($space, $actor, $txData);
                    $totalExpense += $amount;
                }

                if (! empty($row['primary_matched_rule'])) {
                    $matchedRulesCount++;
                }

                $importedCount++;
            }

            if ($importedCount > 0) {
                $this->activityLog->log(
                    module: 'transaction_import',
                    action: 'imported',
                    description: "Mengimpor {$importedCount} transaksi ke {$defaultAccount->name}".($matchedRulesCount > 0 ? " ({$matchedRulesCount} otomatis terisi oleh aturan transaksi)" : ''),
                    space: $space,
                    actor: $actor,
                    properties: [
                        'account_id' => $defaultAccount->id,
                        'imported_count' => $importedCount,
                        'matched_rules_count' => $matchedRulesCount,
                        'total_expense' => $totalExpense,
                        'total_income' => $totalIncome,
                    ]
                );
            }
        });

        return [
            'imported_count' => $importedCount,
            'matched_rules_count' => $matchedRulesCount,
            'total_expense' => $totalExpense,
            'total_income' => $totalIncome,
        ];
    }

    /**
     * Buat file template CSV contoh untuk kemudahan pengguna.
     */
    public function generateSampleCsv(): string
    {
        $headers = ['Tanggal', 'Keterangan', 'Nominal', 'Jenis', 'Kategori', 'Member', 'Catatan'];
        $sampleRows = [
            ['16/11/2026', 'Kopi Kenangan Mantan', '24000', 'Pengeluaran', '', '', 'Pembelian kopi pagi'],
            ['15/11/2026', 'Gaji Bulanan Perusahaan', '8500000', 'Pemasukan', 'Gaji & Upah', '', 'Transfer payroll bulanan'],
            ['14/11/2026', 'Superindo Belanja Sayur & Buah', '345000', 'Pengeluaran', '', '', 'Bahan makanan mingguan'],
            ['13/11/2026', 'Tagihan Listrik PLN Pascabayar', '185000', 'Pengeluaran', '', '', 'Token listrik rumah'],
            ['12/11/2026', 'Gojek Transport Perjalanan Dinas', '48000', 'Pengeluaran', '', '', 'Transportasi kantor'],
        ];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        foreach ($sampleRows as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv !== false ? $csv : '';
    }

    /**
     * Deteksi delimiter pemisah kolom dalam baris data.
     *
     * @param  list<string>  $lines
     */
    protected function detectDelimiter(array $lines): string
    {
        $sampleLines = array_slice($lines, 0, min(5, count($lines)));
        $delimiters = [',', ';', "\t"];
        $scores = [',' => 0, ';' => 0, "\t" => 0];

        foreach ($sampleLines as $line) {
            foreach ($delimiters as $delim) {
                $count = substr_count($line, $delim);
                $scores[$delim] += $count;
            }
        }

        arsort($scores);
        $bestDelimiter = array_key_first($scores);

        return ($bestDelimiter !== null && $scores[$bestDelimiter] > 0) ? $bestDelimiter : ',';
    }

    /**
     * Memetakan nama kolom dari baris header.
     *
     * @param  list<string>  $headers
     * @return array<string, int>
     */
    protected function mapHeaders(array $headers): array
    {
        $map = [];

        foreach ($headers as $index => $header) {
            $norm = Str::lower(trim((string) $header));
            $norm = str_replace(['_', '-'], ' ', $norm);

            if (in_array($norm, ['tanggal', 'date', 'tgl', 'waktu', 'transaction date'], true)) {
                $map['date'] = $index;
            } elseif (in_array($norm, ['keterangan', 'deskripsi', 'description', 'uraian', 'mutasi', 'transaksi', 'rincian', 'memo'], true)) {
                $map['description'] = $index;
            } elseif (in_array($norm, ['nominal', 'jumlah', 'amount', 'nilai', 'total'], true)) {
                $map['amount'] = $index;
            } elseif (in_array($norm, ['debit', 'debet', 'keluar', 'pengeluaran', 'out'], true)) {
                $map['debit'] = $index;
            } elseif (in_array($norm, ['kredit', 'credit', 'masuk', 'pemasukan', 'in'], true)) {
                $map['credit'] = $index;
            } elseif (in_array($norm, ['jenis', 'tipe', 'type', 'd/k', 'dk', 'arus'], true)) {
                $map['type'] = $index;
            } elseif (in_array($norm, ['kategori', 'category'], true)) {
                $map['category'] = $index;
            } elseif (in_array($norm, ['member', 'anggota'], true)) {
                $map['member'] = $index;
            } elseif (in_array($norm, ['catatan', 'note', 'notes'], true)) {
                $map['notes'] = $index;
            } elseif (in_array($norm, ['rekening', 'account', 'wallet', 'dompet'], true)) {
                $map['account'] = $index;
            }
        }

        return $map;
    }

    /**
     * Menilai apakah baris pertama lebih mirip data daripada nama kolom/header.
     *
     * @param  list<string>  $columns
     * @param  array<string, int>  $map
     */
    protected function isLikelyDataRow(array $columns, array $map): bool
    {
        if (count($map) >= 2) {
            return false;
        }

        $firstCol = trim((string) ($columns[0] ?? ''));

        // Jika kolom pertama adalah tanggal berformat angka (misal 16/11/2026 atau 2026-11-16), itu data baris
        return (bool) preg_match('/^\d{1,4}[-\/\.]\d{1,2}[-\/\.]\d{2,4}/', $firstCol);
    }

    /**
     * Mengambil nilai kolom secara aman.
     *
     * @param  list<string>  $columns
     */
    protected function getColumnValue(array $columns, ?int $index): ?string
    {
        if ($index === null || ! isset($columns[$index])) {
            return null;
        }

        return trim((string) $columns[$index]);
    }

    /**
     * Parsing tanggal dari berbagai format umum di Indonesia dan internasional.
     */
    protected function parseDate(?string $rawDate, Space $space): string
    {
        if (blank($rawDate)) {
            return $space->now()->toDateString();
        }

        $cleaned = trim((string) $rawDate);
        $formats = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'd.m.Y',
            'd/m/y',
            'd-m-y',
            'Y/m/d',
            'd M Y',
            'd F Y',
        ];

        foreach ($formats as $fmt) {
            try {
                $parsed = Carbon::createFromFormat($fmt, $cleaned);
                if ($parsed !== false) {
                    return $parsed->toDateString();
                }
            } catch (\Throwable) {
                // Coba format berikutnya
            }
        }

        try {
            return Carbon::parse($cleaned)->toDateString();
        } catch (\Throwable) {
            return $space->now()->toDateString();
        }
    }

    /**
     * Parsing nominal uang dan tentukan jenis (expense / income).
     *
     * @return array{0: float, 1: string}
     */
    protected function parseAmountAndType(
        ?string $amountRaw,
        ?string $typeRaw,
        ?string $debitRaw = null,
        ?string $creditRaw = null
    ): array {
        // Jika ada kolom terpisah untuk debit (pengeluaran) dan kredit (pemasukan)
        if ($debitRaw !== null || $creditRaw !== null) {
            $debit = $debitRaw !== null ? $this->cleanNumber($debitRaw) : 0.0;
            $credit = $creditRaw !== null ? $this->cleanNumber($creditRaw) : 0.0;

            if ($credit > 0) {
                return [$credit, 'income'];
            }
            if ($debit > 0) {
                return [$debit, 'expense'];
            }
        }

        $isExplicitNegative = false;
        $amountStr = (string) $amountRaw;

        // Cek jika terkurung dalam tanda kurung akuntansi: (100.000)
        if (preg_match('/^\((.*)\)$/', trim($amountStr), $matches)) {
            $isExplicitNegative = true;
            $amountStr = $matches[1];
        } elseif (str_starts_with(trim($amountStr), '-')) {
            $isExplicitNegative = true;
        }

        $cleanAmount = $this->cleanNumber($amountStr);

        $type = 'expense';
        if ($isExplicitNegative) {
            $type = 'expense';
        } elseif ($typeRaw !== null && $typeRaw !== '') {
            $normType = Str::lower(trim($typeRaw));
            if (in_array($normType, ['masuk', 'income', 'kredit', 'cr', 'pemasukan', 'in', 'c'], true)) {
                $type = 'income';
            } elseif (in_array($normType, ['keluar', 'expense', 'debet', 'debit', 'db', 'pengeluaran', 'out', 'd'], true)) {
                $type = 'expense';
            }
        }

        return [$cleanAmount, $type];
    }

    /**
     * Membersihkan string angka dan menyesuaikan separator desimal / ribuan.
     */
    protected function cleanNumber(string $raw): float
    {
        $s = trim($raw);
        $s = str_ireplace(['rp', 'idr', ' ', '+', '-'], '', $s);

        if ($s === '') {
            return 0.0;
        }

        $hasDot = str_contains($s, '.');
        $hasComma = str_contains($s, ',');

        if ($hasDot && $hasComma) {
            $lastDot = strrpos($s, '.');
            $lastComma = strrpos($s, ',');

            if ($lastComma > $lastDot) {
                // Format Indonesia: 1.500.000,50
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                // Format Standar / Internasional: 1,500,000.50
                $s = str_replace(',', '', $s);
            }
        } elseif ($hasComma && ! $hasDot) {
            // Hanya ada koma. Jika terdapat 3 angka setelah koma (misal 50,000), bisa ribuan; jika 1 atau 2 angka (50,5), desimal.
            $parts = explode(',', $s);
            $lastPart = end($parts);
            if (strlen($lastPart) === 3 && count($parts) > 1) {
                $s = str_replace(',', '', $s);
            } else {
                $s = str_replace(',', '.', $s);
            }
        } elseif ($hasDot && ! $hasComma) {
            // Hanya ada titik. Jika format Indonesia: 50.000 atau 1.500.000
            $parts = explode('.', $s);
            $lastPart = end($parts);
            if (strlen($lastPart) === 3 && count($parts) > 1) {
                $s = str_replace('.', '', $s);
            }
        }

        $val = (float) $s;

        return abs($val);
    }
}
