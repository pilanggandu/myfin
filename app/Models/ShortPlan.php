<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShortPlan extends Model
{
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'priority' => 'medium',
        'status' => 'planned',
    ];

    protected $fillable = [
        'space_id',
        'account_id',
        'category_id',
        'member_id',
        'financial_transaction_id',
        'title',
        'notes',
        'planned_amount',
        'actual_amount',
        'target_date',
        'priority',
        'status',
        'realized_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'planned_amount' => 'float',
            'actual_amount' => 'float',
            'target_date' => 'date',
            'realized_at' => 'datetime',
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

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'financial_transaction_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShortPlanItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function scopePlanned(Builder $query): Builder
    {
        return $query->where('status', 'planned');
    }

    public function scopeDone(Builder $query): Builder
    {
        return $query->where('status', 'done');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function isPlanned(): bool
    {
        return $this->status === 'planned';
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'high' => 'Mendesak',
            'low' => 'Santai',
            default => 'Normal',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'done' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => 'Direncanakan',
        };
    }

    public function daysRemaining(?Carbon $now = null): ?int
    {
        if (! $this->target_date) {
            return null;
        }

        $today = ($now ? $now->copy() : Carbon::now('Asia/Jakarta'))->startOfDay();
        $target = $this->target_date->copy()->startOfDay();

        return (int) $today->diffInDays($target, false);
    }

    public function dueStatus(?Carbon $now = null): string
    {
        if (! $this->target_date) {
            return 'flexible';
        }

        $days = $this->daysRemaining($now);

        if ($days < 0) {
            return 'overdue';
        }

        if ($days === 0) {
            return 'due_today';
        }

        if ($days <= 7) {
            return 'soon';
        }

        return 'scheduled';
    }

    public function dueStatusLabel(?Carbon $now = null): string
    {
        $days = $this->daysRemaining($now);

        return match ($this->dueStatus($now)) {
            'overdue' => 'Lewat '.abs((int) $days).' hari',
            'due_today' => 'Hari Ini',
            'soon' => $days.' hari lagi',
            'scheduled' => $this->target_date?->translatedFormat('d M Y') ?? 'Terjadwal',
            default => 'Fleksibel',
        };
    }

    public function varianceAmount(): ?float
    {
        if ($this->actual_amount === null) {
            return null;
        }

        return round((float) $this->planned_amount - (float) $this->actual_amount, 2);
    }

    public function itemsTotalAmount(): float
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return round((float) $items->sum('estimated_amount'), 2);
    }

    public function checkedItemsCount(): int
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return $items->where('is_checked', true)->count();
    }
}
