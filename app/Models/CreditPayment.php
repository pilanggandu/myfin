<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'credit_account_id',
        'installment_id',
        'source_account_id',
        'transaction_id',
        'principal_amount',
        'interest_amount',
        'fee_amount',
        'total_amount',
        'payment_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'integer',
            'interest_amount' => 'integer',
            'fee_amount' => 'integer',
            'total_amount' => 'integer',
            'payment_date' => 'date',
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

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return 'Rp '.number_format($this->total_amount, 0, ',', '.');
    }
}
