<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditDebitNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'credit_account_id',
        'installment_plan_id',
        'installment_id',
        'financial_transaction_id',
        'type',
        'amount',
        'note_date',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'note_date' => 'date',
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

    public function installmentPlan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'financial_transaction_id');
    }

    public function isCreditNote(): bool
    {
        return $this->type === 'credit_note';
    }

    public function isDebitNote(): bool
    {
        return $this->type === 'debit_note';
    }

    public function typeLabel(): string
    {
        return $this->isCreditNote() ? 'Nota Kredit' : 'Nota Debit';
    }

    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->isCreditNote() ? '- Rp ' : '+ Rp ';

        return $prefix.number_format($this->amount, 0, ',', '.');
    }
}
