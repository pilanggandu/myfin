<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditFacility extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'name',
        'total_limit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_limit' => 'float',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function creditAccounts(): HasMany
    {
        return $this->hasMany(CreditAccount::class);
    }

    public function currentLiability(): float
    {
        return round($this->creditAccounts()
            ->active()
            ->get()
            ->sum(fn (CreditAccount $account) => $account->currentLiability()), 2);
    }

    public function availableLimit(): float
    {
        return max(0.0, round((float) $this->total_limit - $this->currentLiability(), 2));
    }

    public function utilizationRate(): float
    {
        if ($this->total_limit <= 0) {
            return 0.0;
        }

        return round(($this->currentLiability() / $this->total_limit) * 100, 1);
    }

    public function utilizationStatus(): string
    {
        $rate = $this->utilizationRate();
        if ($rate <= 30.0) {
            return 'safe';
        }
        if ($rate <= 70.0) {
            return 'moderate';
        }

        return 'high';
    }

    public function getFormattedTotalLimitAttribute(): string
    {
        $decimals = fmod($this->total_limit, 1.0) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($this->total_limit, $decimals, ',', '.');
    }

    public function getFormattedLiabilityAttribute(): string
    {
        $amt = $this->currentLiability();
        $decimals = fmod($amt, 1.0) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($amt, $decimals, ',', '.');
    }

    public function getFormattedAvailableLimitAttribute(): string
    {
        $amt = $this->availableLimit();
        $decimals = fmod($amt, 1.0) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($amt, $decimals, ',', '.');
    }

    public function getFormattedUtilizationRateAttribute(): string
    {
        return $this->utilizationRate().'%';
    }
}
