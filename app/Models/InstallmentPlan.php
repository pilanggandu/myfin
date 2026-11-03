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
            'total_amount' => 'float',
            'tenor' => 'integer',
            'interest_rate' => 'decimal:2',
            'admin_fee' => 'float',
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

    public function creditDebitNotes(): HasMany
    {
        return $this->hasMany(CreditDebitNote::class)->orderBy('note_date', 'desc')->orderBy('id', 'desc');
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

    public function remainingPrincipal(): float|int
    {
        return round((float) $this->installments()->where('status', 'pending')->sum('principal_amount'), 2);
    }

    public function paidPrincipal(): float|int
    {
        return round((float) $this->installments()->where('status', 'paid')->sum('principal_amount'), 2);
    }

    public function totalInterest(): float|int
    {
        return round((float) $this->installments()->sum('interest_amount'), 2);
    }

    public function totalFees(): float|int
    {
        return round((float) $this->installments()->sum('fee_amount'), 2);
    }

    public function grandTotalAmount(): float|int
    {
        return round((float) $this->installments()->sum('total_amount'), 2);
    }

    public function remainingTotalAmount(): float|int
    {
        return round((float) $this->installments()->where('status', 'pending')->sum('total_amount'), 2);
    }

    public function getAdminFeeAttribute(): float|int
    {
        return round((float) ($this->attributes['admin_fee'] ?? 0), 2);
    }

    public function hasInterestOrFees(): bool
    {
        return $this->totalInterest() > 0 || $this->totalFees() > 0 || (float) $this->interest_rate > 0 || $this->admin_fee > 0;
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        $decimals = fmod((float) $this->total_amount, 1) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($this->total_amount, $decimals, ',', '.');
    }

    public function getFormattedGrandTotalAttribute(): string
    {
        $amt = $this->grandTotalAmount();
        $decimals = fmod((float) $amt, 1) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($amt, $decimals, ',', '.');
    }

    public function getFormattedRemainingAttribute(): string
    {
        $amt = $this->remainingPrincipal();
        $decimals = fmod((float) $amt, 1) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($amt, $decimals, ',', '.');
    }

    public function getFormattedRemainingTotalAttribute(): string
    {
        $amt = $this->remainingTotalAmount();
        $decimals = fmod((float) $amt, 1) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($amt, $decimals, ',', '.');
    }

    public function getFormattedPaidAttribute(): string
    {
        $amt = $this->paidPrincipal();
        $decimals = fmod((float) $amt, 1) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($amt, $decimals, ',', '.');
    }

    public function getFormattedInterestAttribute(): string
    {
        $amt = $this->totalInterest();
        $decimals = fmod((float) $amt, 1) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($amt, $decimals, ',', '.');
    }

    public function getFormattedFeeAttribute(): string
    {
        $amt = $this->totalFees();
        $decimals = fmod((float) $amt, 1) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($amt, $decimals, ',', '.');
    }

    public function checkCompletion(): void
    {
        $hasPending = $this->installments()->where('status', 'pending')->exists();
        if (! $hasPending && $this->status === 'active') {
            $this->update(['status' => 'completed']);
        }
    }
}
