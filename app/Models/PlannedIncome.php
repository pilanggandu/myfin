<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlannedIncome extends Model
{
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
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
        'amount',
        'actual_amount',
        'expected_date',
        'status',
        'received_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'actual_amount' => 'float',
            'expected_date' => 'date',
            'received_at' => 'datetime',
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

    public function scopePlanned(Builder $query): Builder
    {
        return $query->where('status', 'planned');
    }

    public function scopeReceived(Builder $query): Builder
    {
        return $query->where('status', 'received');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function setExpectedDateAttribute($value): void
    {
        $this->attributes['expected_date'] = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : Carbon::parse($value)->format('Y-m-d');
    }

    public function isPlanned(): bool
    {
        return $this->status === 'planned';
    }

    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'received' => 'Diterima',
            'cancelled' => 'Dibatalkan',
            default => 'Direncanakan',
        };
    }

    public function daysRemaining(?Carbon $now = null): ?int
    {
        if (! $this->expected_date) {
            return null;
        }

        $today = ($now ? $now->copy() : Carbon::now('Asia/Jakarta'))->startOfDay();
        $target = $this->expected_date->copy()->startOfDay();

        return (int) $today->diffInDays($target, false);
    }

    public function dueStatus(?Carbon $now = null): string
    {
        if (! $this->expected_date) {
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
            'scheduled' => $this->expected_date?->translatedFormat('d M Y') ?? 'Terjadwal',
            default => 'Fleksibel',
        };
    }

    public function varianceAmount(): ?float
    {
        if ($this->actual_amount === null) {
            return null;
        }

        return round((float) $this->actual_amount - (float) $this->amount, 2);
    }
}
