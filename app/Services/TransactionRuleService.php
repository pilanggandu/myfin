<?php

namespace App\Services;

use App\Models\Space;
use App\Models\TransactionRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionRuleService
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Mengevaluasi apakah suatu aturan cocok dengan kriteria transaksi.
     */
    public function evaluateRule(
        TransactionRule $rule,
        string $description,
        float $amount,
        ?string $type = null,
        ?int $accountId = null
    ): bool {
        // 1. Cek tipe transaksi (expense, income, all)
        if ($rule->type_criterion !== 'all' && $type !== null) {
            if ($rule->type_criterion !== $type) {
                return false;
            }
        }

        // 2. Cek rekening jika ditentukan
        if ($rule->account_id !== null && $accountId !== null) {
            if ((int) $rule->account_id !== (int) $accountId) {
                return false;
            }
        }

        // 3. Cek kondisi nominal
        $amountAbs = abs($amount);
        $min = $rule->amount_min !== null ? (float) $rule->amount_min : null;
        $max = $rule->amount_max !== null ? (float) $rule->amount_max : null;

        $amountMatches = match ($rule->amount_operator) {
            'any' => true,
            'equals' => $min !== null && abs($amountAbs - $min) < 0.009,
            'greater_than' => $min !== null && $amountAbs > $min,
            'greater_than_or_equal' => $min !== null && $amountAbs >= $min,
            'less_than' => $min !== null && $amountAbs < $min,
            'less_than_or_equal' => $min !== null && $amountAbs <= $min,
            'between' => ($min !== null && $max !== null) && ($amountAbs >= $min && $amountAbs <= $max),
            default => true,
        };

        if (! $amountMatches) {
            return false;
        }

        // 4. Cek kata kunci
        if (blank($rule->keyword)) {
            return true;
        }

        $needle = Str::lower(trim((string) $rule->keyword));
        $haystack = Str::lower(trim($description));

        return match ($rule->keyword_operator) {
            'contains' => str_contains($haystack, $needle),
            'starts_with' => str_starts_with($haystack, $needle),
            'ends_with' => str_ends_with($haystack, $needle),
            'exact' => $haystack === $needle,
            'regex' => $this->evaluateRegex((string) $rule->keyword, $description),
            default => str_contains($haystack, $needle),
        };
    }

    /**
     * Evaluasi ekspresi reguler secara aman.
     */
    protected function evaluateRegex(string $pattern, string $subject): bool
    {
        $trimmed = trim($pattern);
        if ($trimmed === '') {
            return false;
        }

        if (! str_starts_with($trimmed, '/') && ! str_starts_with($trimmed, '#') && ! str_starts_with($trimmed, '~')) {
            $trimmed = '#'.str_replace('#', '\#', $trimmed).'#iu';
        }

        try {
            return (bool) @preg_match($trimmed, $subject);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Menemukan aturan pertama yang cocok untuk transaksi dalam ruang tertentu.
     */
    public function matchRule(
        Space $space,
        string $description,
        float $amount,
        ?string $type = null,
        ?int $accountId = null
    ): ?TransactionRule {
        $rules = $space->activeTransactionRules()
            ->with(['actionCategory', 'actionMember'])
            ->get();

        foreach ($rules as $rule) {
            if ($this->evaluateRule($rule, $description, $amount, $type, $accountId)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Menerapkan aturan ke satu baris data impor transaksi.
     *
     * @param  array<string, mixed>  $row
     * @param  Collection<int, TransactionRule>|null  $cachedRules
     * @return array<string, mixed>
     */
    public function applyRulesToRow(Space $space, array $row, ?Collection $cachedRules = null): array
    {
        $rules = $cachedRules ?? $space->activeTransactionRules()
            ->with(['actionCategory', 'actionMember'])
            ->get();

        $description = (string) ($row['description'] ?? '');
        $amount = (float) ($row['amount'] ?? 0);
        $type = ! empty($row['type']) ? (string) $row['type'] : null;
        $accountId = ! empty($row['account_id']) ? (int) $row['account_id'] : null;

        $matchedRules = [];

        foreach ($rules as $rule) {
            if (! $this->evaluateRule($rule, $description, $amount, $type, $accountId)) {
                continue;
            }

            $matchedRules[] = [
                'id' => $rule->id,
                'name' => $rule->name,
            ];

            // Terapkan kategori jika belum ada atau jika ditentukan oleh aturan
            if ($rule->action_category_id && empty($row['category_id'])) {
                $row['category_id'] = $rule->action_category_id;
                $row['category_name'] = $rule->actionCategory?->name;
            }

            // Terapkan member jika belum ada atau ditentukan oleh aturan
            if ($rule->action_member_id && empty($row['member_id'])) {
                $row['member_id'] = $rule->action_member_id;
                $row['member_name'] = $rule->actionMember?->name;
            }

            // Terapkan catatan
            if (! empty($rule->action_note)) {
                if (empty($row['notes'])) {
                    $row['notes'] = $rule->action_note;
                } else {
                    $row['notes'] = $row['notes'].' | '.$rule->action_note;
                }
            }

            // Ubah keterangan jika ditentukan oleh aturan
            if (! empty($rule->action_description)) {
                $row['description'] = $rule->action_description;
                $description = $rule->action_description;
            }

            if ($rule->stop_processing) {
                break;
            }
        }

        if (! empty($matchedRules)) {
            $row['matched_rules'] = $matchedRules;
            $row['primary_matched_rule'] = $matchedRules[0]['name'];
        }

        return $row;
    }

    /**
     * Mensimulasikan evaluasi aturan terhadap input pengujian.
     *
     * @return array<string, mixed>
     */
    public function simulate(
        Space $space,
        string $description,
        float $amount,
        ?string $type = null,
        ?int $accountId = null
    ): array {
        $rules = $space->activeTransactionRules()
            ->with(['actionCategory', 'actionMember'])
            ->get();

        $matchedRules = [];
        $simulated = [
            'category_id' => null,
            'category_name' => null,
            'member_id' => null,
            'member_name' => null,
            'notes' => null,
            'description' => $description,
        ];

        foreach ($rules as $rule) {
            $matched = $this->evaluateRule($rule, $description, $amount, $type, $accountId);
            if ($matched) {
                $matchedRules[] = [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'priority' => $rule->priority,
                    'action_category' => $rule->actionCategory?->name,
                    'action_member' => $rule->actionMember?->name,
                    'action_note' => $rule->action_note,
                    'action_description' => $rule->action_description,
                    'stop_processing' => $rule->stop_processing,
                ];

                if ($rule->action_category_id && empty($simulated['category_id'])) {
                    $simulated['category_id'] = $rule->action_category_id;
                    $simulated['category_name'] = $rule->actionCategory?->name;
                }

                if ($rule->action_member_id && empty($simulated['member_id'])) {
                    $simulated['member_id'] = $rule->action_member_id;
                    $simulated['member_name'] = $rule->actionMember?->name;
                }

                if (! empty($rule->action_note)) {
                    $simulated['notes'] = empty($simulated['notes'])
                        ? $rule->action_note
                        : $simulated['notes'].' | '.$rule->action_note;
                }

                if (! empty($rule->action_description)) {
                    $simulated['description'] = $rule->action_description;
                }

                if ($rule->stop_processing) {
                    break;
                }
            }
        }

        return [
            'matched' => count($matchedRules) > 0,
            'matched_count' => count($matchedRules),
            'matched_rules' => $matchedRules,
            'result' => $simulated,
        ];
    }

    /**
     * Terapkan satu aturan ke transaksi yang sudah ada di dalam ruang keuangan.
     */
    public function applyToExisting(Space $space, TransactionRule $rule, bool $onlyUncategorized = true): int
    {
        $rule->loadMissing(['actionCategory', 'actionMember']);

        $query = $space->financialTransactions()
            ->with('entries');

        if ($onlyUncategorized) {
            $query->whereNull('category_id');
        }

        if ($rule->type_criterion !== 'all') {
            $query->where('type', $rule->type_criterion);
        }

        $transactions = $query->get();
        $updatedCount = 0;

        DB::transaction(function () use ($transactions, $rule, $onlyUncategorized, &$updatedCount, $space): void {
            foreach ($transactions as $transaction) {
                $amount = (float) $transaction->entries->sum(fn ($e): float => abs((float) $e->amount_signed));
                $accountId = (int) ($transaction->entries->first()?->account_id ?? 0);

                if (! $this->evaluateRule($rule, (string) $transaction->description, $amount, (string) $transaction->type, $accountId)) {
                    continue;
                }

                $updates = [];

                if ($rule->action_category_id && (! $onlyUncategorized || empty($transaction->category_id))) {
                    $updates['category_id'] = $rule->action_category_id;
                }

                if ($rule->action_member_id && empty($transaction->member_id)) {
                    $updates['member_id'] = $rule->action_member_id;
                }

                if (! empty($rule->action_note)) {
                    $updates['notes'] = empty($transaction->notes)
                        ? $rule->action_note
                        : $transaction->notes.' | '.$rule->action_note;
                }

                if (! empty($rule->action_description)) {
                    $updates['description'] = $rule->action_description;
                }

                if (! empty($updates)) {
                    $transaction->update($updates);
                    $updatedCount++;
                }
            }

            if ($updatedCount > 0) {
                $this->activityLog->log(
                    module: 'transaction_rule',
                    action: 'applied_retroactive',
                    description: "Menerapkan aturan '{$rule->name}' ke {$updatedCount} transaksi lama",
                    space: $space,
                    subject: $rule,
                    subjectLabel: $rule->name,
                    properties: [
                        'rule_id' => $rule->id,
                        'updated_count' => $updatedCount,
                        'only_uncategorized' => $onlyUncategorized,
                    ]
                );
            }
        });

        return $updatedCount;
    }
}
