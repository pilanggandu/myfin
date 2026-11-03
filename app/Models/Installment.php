<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installment extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'installment_plan_id',
        'installment_number',
        'due_date',
        'principal_amount',
        'interest_amount',
        'fee_amount',
        'total_amount',
        'status',
        'paid_at',
        'payment_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'installment_number' => 'integer',
            'due_date' => 'date',
            'principal_amount' => 'integer',
            'interest_amount' => 'integer',
            'fee_amount' => 'integer',
            'total_amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function installmentPlan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'payment_transaction_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    public function getFormattedPrincipalAttribute(): string
    {
        return 'Rp ' . number_format($this->principal_amount, 0, ',', '.');
    }

    public function getFormattedInterestAttribute(): string
    {
        return 'Rp ' . number_format($this->interest_amount, 0, ',', '.');
    }

    public function getFormattedFeeAttribute(): string
    {
        return 'Rp ' . number_format($this->fee_amount, 0, ',', '.');
    }
}
