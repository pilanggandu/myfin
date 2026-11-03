<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'name',
        'type',
        'opening_balance',
        'opened_on',
        'archived_at',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'float',
            'opened_on' => 'date',
            'archived_at' => 'datetime',
            'is_default' => 'boolean',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    public function openingBalanceLogs(): HasMany
    {
        return $this->hasMany(AccountOpeningBalanceLog::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function archive(): void
    {
        $this->update(['archived_at' => now(), 'is_default' => false]);
    }

    public function unarchive(): void
    {
        $this->update(['archived_at' => null]);
    }

    public function isDefault(): bool
    {
        return (bool) $this->is_default;
    }

    public function makeDefault(): void
    {
        DB::transaction(function () {
            static::where('space_id', $this->space_id)->lockForUpdate()->get();

            static::where('space_id', $this->space_id)
                ->where('id', '!=', $this->id)
                ->update(['is_default' => false]);

            $this->update(['is_default' => true]);
        });
    }

    public function currentBalance(): float|int
    {
        $entriesSum = (float) $this->entries()
            ->whereHas('transaction', function ($query) {
                $query->where('status', 'posted');
            })
            ->sum('amount_signed');

        return round((float) $this->opening_balance + $entriesSum, 2);
    }

    public function balanceOnDate(Carbon|string $date): float|int
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : $date;

        $entriesSum = (float) $this->entries()
            ->whereHas('transaction', function ($query) use ($dateStr) {
                $query->where('status', 'posted')
                    ->where('transaction_date', '<=', $dateStr);
            })
            ->sum('amount_signed');

        return round((float) $this->opening_balance + $entriesSum, 2);
    }

    public function getFormattedBalanceAttribute(): string
    {
        $bal = $this->currentBalance();
        $decimals = fmod((float) $bal, 1) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($bal, $decimals, ',', '.');
    }
}
