<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'account_id',
        'name',
        'type',
        'credit_limit',
        'billing_cycle_day',
        'due_day',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'integer',
            'billing_cycle_day' => 'integer',
            'due_day' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function installmentPlans(): HasMany
    {
        return $this->hasMany(InstallmentPlan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
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
        $this->update(['archived_at' => now()]);
        if ($this->ledgerAccount) {
            $this->ledgerAccount->update(['archived_at' => now()]);
        }
    }

    public function unarchive(): void
    {
        $this->update(['archived_at' => null]);
        if ($this->ledgerAccount) {
            $this->ledgerAccount->update(['archived_at' => null]);
        }
    }

    public function currentLiability(): int
    {
        return (int) Installment::query()
            ->whereHas('installmentPlan', function ($q) {
                $q->where('credit_account_id', $this->id)
                    ->where('status', 'active');
            })
            ->where('status', 'pending')
            ->sum('principal_amount');
    }

    public function availableLimit(): int
    {
        $available = (int) $this->credit_limit - $this->currentLiability();
        return max(0, $available);
    }

    public function getFormattedLimitAttribute(): string
    {
        return 'Rp ' . number_format($this->credit_limit, 0, ',', '.');
    }

    public function getFormattedLiabilityAttribute(): string
    {
        return 'Rp ' . number_format($this->currentLiability(), 0, ',', '.');
    }

    public function getFormattedAvailableLimitAttribute(): string
    {
        return 'Rp ' . number_format($this->availableLimit(), 0, ',', '.');
    }
}
