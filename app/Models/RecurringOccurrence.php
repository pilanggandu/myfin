<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringOccurrence extends Model
{
    use HasFactory;

    protected $fillable = [
        'recurring_schedule_id',
        'space_id',
        'due_date',
        'transaction_id',
        'status',
        'amount',
        'confirmed_at',
        'confirmed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }

    public function setDueDateAttribute($value): void
    {
        $this->attributes['due_date'] = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : Carbon::parse($value)->format('Y-m-d');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(RecurringSchedule::class, 'recurring_schedule_id');
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'confirmed' => 'Tercatat',
            'skipped' => 'Dilewati',
            default => 'Menunggu Konfirmasi',
        };
    }
}
