<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'name',
        'type',
        'rollover_enabled',
        'rollover_mode',
        'rollover_cap',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'rollover_enabled' => 'boolean',
            'rollover_cap' => 'integer',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function isIncome(): bool
    {
        return $this->type === 'income';
    }

    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    public function archive(): void
    {
        $this->update(['archived_at' => now()]);
    }

    public function unarchive(): void
    {
        $this->update(['archived_at' => null]);
    }

    public function isRolloverEnabled(): bool
    {
        return (bool) $this->rollover_enabled && $this->isExpense();
    }

    public function rolloverModeLabel(): string
    {
        return match ($this->rollover_mode) {
            'surplus_only' => 'Hanya Sisa Hemat (Surplus)',
            'deficit_only' => 'Hanya Kelebihan Belanja (Defisit)',
            'all' => 'Sisa Hemat & Defisit',
            default => 'Sisa Hemat & Defisit',
        };
    }

    public function scopeRolloverEnabled(Builder $query): Builder
    {
        return $query->where('rollover_enabled', true);
    }
}
