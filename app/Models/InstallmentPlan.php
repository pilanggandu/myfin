<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'credit_account_id',
        'financial_transaction_id',
        'category_id',
        'member_id',
        'name',
        'total_amount',
        'tenor',
        'interest_rate',
        'admin_fee',
        'start_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'tenor' => 'integer',
            'interest_rate' => 'decimal:2',
            'admin_fee' => 'integer',
            'start_date' => 'date',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(CreditAccount::class);
    }

    public function purchaseTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'financial_transaction_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class)->orderBy('installment_number', 'asc');
    }

    public function pendingInstallments(): HasMany
    {
        return $this->hasMany(Installment::class)->where('status', 'pending')->orderBy('installment_number', 'asc');
    }

    public function paidInstallments(): HasMany
    {
        return $this->hasMany(Installment::class)->where('status', 'paid')->orderBy('installment_number', 'asc');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function remainingPrincipal(): int
    {
        return (int) $this->installments()->where('status', 'pending')->sum('principal_amount');
    }

    public function paidPrincipal(): int
    {
        return (int) $this->installments()->where('status', 'paid')->sum('principal_amount');
    }

    public function totalInterest(): int
    {
        return (int) $this->installments()->sum('interest_amount');
    }

    public function totalFees(): int
    {
        return (int) $this->installments()->sum('fee_amount');
    }

    public function grandTotalAmount(): int
    {
        return (int) $this->installments()->sum('total_amount');
    }

    public function remainingTotalAmount(): int
    {
        return (int) $this->installments()->where('status', 'pending')->sum('total_amount');
    }

    public function getAdminFeeAttribute(): int
    {
        return (int) ($this->attributes['admin_fee'] ?? 0);
    }

    public function hasInterestOrFees(): bool
    {
        return $this->totalInterest() > 0 || $this->totalFees() > 0 || (float) $this->interest_rate > 0 || $this->admin_fee > 0;
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return 'Rp '.number_format($this->total_amount, 0, ',', '.');
    }

    public function getFormattedGrandTotalAttribute(): string
    {
        return 'Rp '.number_format($this->grandTotalAmount(), 0, ',', '.');
    }

    public function getFormattedRemainingAttribute(): string
    {
        return 'Rp '.number_format($this->remainingPrincipal(), 0, ',', '.');
    }

    public function getFormattedRemainingTotalAttribute(): string
    {
        return 'Rp '.number_format($this->remainingTotalAmount(), 0, ',', '.');
    }

    public function getFormattedPaidAttribute(): string
    {
        return 'Rp '.number_format($this->paidPrincipal(), 0, ',', '.');
    }

    public function getFormattedInterestAttribute(): string
    {
        return 'Rp '.number_format($this->totalInterest(), 0, ',', '.');
    }

    public function getFormattedFeeAttribute(): string
    {
        return 'Rp '.number_format($this->totalFees(), 0, ',', '.');
    }

    public function checkCompletion(): void
    {
        $hasPending = $this->installments()->where('status', 'pending')->exists();
        if (! $hasPending && $this->status === 'active') {
            $this->update(['status' => 'completed']);
        }
    }
}
