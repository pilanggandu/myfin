<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'decimal_digits',
        'is_default',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'decimal_digits' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function formatAmount(float|int $amount): string
    {
        $decimals = $this->decimal_digits;
        $formatted = number_format($amount, $decimals, ',', '.');

        return $this->symbol . ' ' . $formatted;
    }

    public function convertToIdr(float|int $amount): float
    {
        return (float) $amount * (float) $this->exchange_rate;
    }

    public function convertFromIdr(float|int $amountIdr): float
    {
        $rate = (float) $this->exchange_rate;
        if ($rate <= 0) {
            return 0.0;
        }

        return (float) $amountIdr / $rate;
    }

    public function convertTo(float|int $amount, Currency $targetCurrency): float
    {
        $targetRate = (float) $targetCurrency->exchange_rate;
        if ($targetRate <= 0) {
            return 0.0;
        }

        $idrValue = $this->convertToIdr($amount);

        return $idrValue / $targetRate;
    }
}
