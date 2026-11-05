<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'account_id',
        'category_id',
        'name',
        'notes',
        'target_amount',
        'current_amount',
        'start_date',
        'target_date',
        'icon',
        'color',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'float',
            'current_amount' => 'float',
            'start_date' => 'date',
            'target_date' => 'date',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(GoalContribution::class)->orderByDesc('contribution_date')->orderByDesc('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeAchieved(Builder $query): Builder
    {
        return $query->where('status', 'achieved');
    }

    public function scopePaused(Builder $query): Builder
    {
        return $query->where('status', 'paused');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAchieved(): bool
    {
        return $this->status === 'achieved';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function percentage(): float
    {
        if ($this->target_amount <= 0) {
            return 0.0;
        }

        $pct = ($this->current_amount / $this->target_amount) * 100;

        return min(100.0, round($pct, 1));
    }

    public function remainingAmount(): float
    {
        return max(0.0, round($this->target_amount - $this->current_amount, 2));
    }

    public function daysRemaining(?Carbon $now = null): ?int
    {
        if (! $this->target_date) {
            return null;
        }

        $now = $now ?: Carbon::now('Asia/Jakarta')->startOfDay();
        $target = $this->target_date->copy()->startOfDay();

        return (int) $now->diffInDays($target, false);
    }

    public function monthlyRecommendation(?Carbon $now = null): ?float
    {
        if (! $this->target_date || $this->remainingAmount() <= 0) {
            return null;
        }

        $now = $now ?: Carbon::now('Asia/Jakarta')->startOfDay();
        $target = $this->target_date->copy()->startOfDay();

        if ($target->lessThanOrEqualTo($now)) {
            return $this->remainingAmount();
        }

        $diffMonths = $now->diffInMonths($target);
        if ($diffMonths <= 0) {
            $diffMonths = 1;
        }

        return round($this->remainingAmount() / $diffMonths, 2);
    }

    public function dailyRecommendation(?Carbon $now = null): ?float
    {
        if (! $this->target_date || $this->remainingAmount() <= 0) {
            return null;
        }

        $days = $this->daysRemaining($now);
        if ($days === null || $days <= 0) {
            return $this->remainingAmount();
        }

        return round($this->remainingAmount() / $days, 2);
    }

    public function syncAmount(): void
    {
        $depositSum = (float) $this->contributions()->where('type', 'deposit')->sum('amount');
        $withdrawalSum = (float) $this->contributions()->where('type', 'withdrawal')->sum('amount');
        $total = max(0.0, round($depositSum - $withdrawalSum, 2));

        $this->current_amount = $total;

        if ($this->target_amount > 0 && $total >= $this->target_amount && $this->status === 'active') {
            $this->status = 'achieved';
        } elseif ($this->target_amount > 0 && $total < $this->target_amount && $this->status === 'achieved') {
            $this->status = 'active';
        }

        $this->save();
    }
}
