<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'type',
        'name',
        'member_id',
        'category_id',
        'source_account_id',
        'destination_account_id',
        'frequency',
        'anchor_day',
        'start_date',
        'end_date',
        'next_due_date',
        'amount',
        'is_variable_amount',
        'description',
        'status',
        'archived_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'next_due_date' => 'date',
            'anchor_day' => 'integer',
            'amount' => 'integer',
            'is_variable_amount' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(RecurringOccurrence::class)->orderBy('due_date', 'desc');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at')->where('status', '!=', 'archived');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNotNull('archived_at')->orWhere('status', 'archived');
        });
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    public function scopeTransfer(Builder $query): Builder
    {
        return $query->where('type', 'transfer');
    }

    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    public function isTransfer(): bool
    {
        return $this->type === 'transfer';
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null || $this->status === 'archived';
    }

    public function archive(): void
    {
        $this->update([
            'archived_at' => now(),
            'status' => 'archived',
        ]);
    }

    public function unarchive(): void
    {
        $this->update([
            'archived_at' => null,
            'status' => 'active',
        ]);
    }

    public function setStartDateAttribute($value): void
    {
        $this->attributes['start_date'] = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : Carbon::parse($value)->format('Y-m-d');
    }

    public function setNextDueDateAttribute($value): void
    {
        $this->attributes['next_due_date'] = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : Carbon::parse($value)->format('Y-m-d');
    }

    public function setEndDateAttribute($value): void
    {
        $this->attributes['end_date'] = empty($value) ? null : (
            $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d')
                : Carbon::parse($value)->format('Y-m-d')
        );
    }

    public function dueStatus(string $spaceTimezone = 'Asia/Jakarta'): string
    {
        $todayStr = Carbon::now($spaceTimezone)->toDateString();
        $dueStr = Carbon::parse($this->next_due_date)->toDateString();

        if ($dueStr < $todayStr) {
            return 'overdue';
        }

        if ($dueStr === $todayStr) {
            return 'due';
        }

        return 'upcoming';
    }

    public function dueStatusLabel(string $spaceTimezone = 'Asia/Jakarta'): string
    {
        return match ($this->dueStatus($spaceTimezone)) {
            'overdue' => 'Terlambat',
            'due' => 'Jatuh Tempo Hari Ini',
            default => 'Mendatang',
        };
    }

    public function calculateNextDueDate(Carbon $fromDate): Carbon
    {
        if ($this->frequency === 'weekly') {
            return $fromDate->copy()->addWeek();
        }

        if ($this->frequency === 'yearly') {
            $target = $fromDate->copy()->startOfYear()->addYearsNoOverflow(1)->month($fromDate->month);
            $day = min((int) $this->anchor_day, (int) $target->daysInMonth);

            return $target->day($day);
        }

        $target = $fromDate->copy()->startOfMonth()->addMonthsNoOverflow(1);
        $day = min((int) $this->anchor_day, (int) $target->daysInMonth);

        return $target->day($day);
    }

    public function advanceNextDueDate(): void
    {
        $next = $this->calculateNextDueDate(Carbon::parse($this->next_due_date));
        $this->update(['next_due_date' => $next->toDateString()]);
    }
}
