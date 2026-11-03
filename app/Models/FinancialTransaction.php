<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'type',
        'transaction_date',
        'transaction_time',
        'description',
        'category_id',
        'member_id',
        'status',
        'request_key',
        'created_by',
        'updated_by',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'version' => 'integer',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function recurringOccurrence(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RecurringOccurrence::class, 'transaction_id');
    }

    public function installmentPlan(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InstallmentPlan::class, 'financial_transaction_id');
    }

    public function creditPayment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CreditPayment::class, 'transaction_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class, 'transaction_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(TransactionRevision::class, 'transaction_id')->orderBy('created_at', 'desc');
    }

    public function formattedTime(): ?string
    {
        return $this->transaction_time ? substr($this->transaction_time, 0, 5) : null;
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', 'posted');
    }

    public function scopeVoid(Builder $query): Builder
    {
        return $query->where('status', 'void');
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    public function scopeTransfer(Builder $query): Builder
    {
        return $query->where('type', 'transfer');
    }

    public function scopeCreditPayment(Builder $query): Builder
    {
        return $query->where('type', 'credit_payment');
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isVoid(): bool
    {
        return $this->status === 'void';
    }

    public function isIncome(): bool
    {
        return $this->type === 'income';
    }

    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    public function isTransfer(): bool
    {
        return $this->type === 'transfer';
    }

    public function isCreditPayment(): bool
    {
        return $this->type === 'credit_payment';
    }

    public function totalAmount(): int
    {
        if ($this->isExpense()) {
            $entry = $this->entries->firstWhere('amount_signed', '<', 0);
            return $entry ? abs((int) $entry->amount_signed) : 0;
        }

        $entry = $this->entries->firstWhere('amount_signed', '>', 0);
        return $entry ? (int) $entry->amount_signed : 0;
    }

    public function sourceAccount(): ?Account
    {
        return $this->entries->firstWhere('amount_signed', '<', 0)?->account;
    }

    public function destinationAccount(): ?Account
    {
        return $this->entries->firstWhere('amount_signed', '>', 0)?->account;
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->totalAmount(), 0, ',', '.');
    }
}
