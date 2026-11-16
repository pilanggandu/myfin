<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialSimulation extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'space_id',
        'user_id',
        'title',
        'scenario_type',
        'description',
        'baseline_income',
        'baseline_expense',
        'baseline_debt',
        'baseline_liquid_cash',
        'parameters',
        'results',
        'is_starred',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'baseline_income' => 'decimal:2',
            'baseline_expense' => 'decimal:2',
            'baseline_debt' => 'decimal:2',
            'baseline_liquid_cash' => 'decimal:2',
            'parameters' => 'array',
            'results' => 'array',
            'is_starred' => 'boolean',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scenarioTypeLabel(): string
    {
        return match ($this->scenario_type) {
            'asset_purchase' => 'Beli Kendaraan / Aset',
            'loan_installment' => 'Cicilan / Pinjaman Baru',
            'saving_target' => 'Menaikkan Target Tabungan',
            default => ucfirst(str_replace('_', ' ', $this->scenario_type)),
        };
    }

    public function healthStatus(): string
    {
        return $this->results['health_status'] ?? 'safe';
    }

    public function healthStatusLabel(): string
    {
        return match ($this->healthStatus()) {
            'safe' => 'Aman & Sangat Layak',
            'warning' => 'Perlu Perhatian / Waspada',
            'danger' => 'Berisiko Tinggi / Defisit',
            default => 'Netral',
        };
    }

    public function scopeStarred(Builder $query): Builder
    {
        return $query->where('is_starred', true);
    }

    public function scopeScenarioType(Builder $query, string $type): Builder
    {
        return $query->where('scenario_type', $type);
    }
}
