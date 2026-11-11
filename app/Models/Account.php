<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function creditAccount(): HasOne
    {
        return $this->hasOne(CreditAccount::class, 'account_id');
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

    public function scopeLiquidAssets(Builder $query): Builder
    {
        return $query->whereIn('type', ['bank', 'e-wallet', 'cash', 'investment']);
    }

    public function scopeExpenseSources(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereIn('type', ['bank', 'e-wallet', 'cash', 'investment'])
                ->orWhere(function (Builder $creditQ) {
                    $creditQ->whereIn('type', ['credit_card', 'paylater'])
                        ->whereHas('creditAccount', fn (Builder $ca) => $ca->whereNull('archived_at'));
                });
        });
    }

    public function scopeBillPaymentSources(Builder $query): Builder
    {
        return $query->whereIn('type', ['bank', 'cash', 'e-wallet']);
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

    public function typeLabel(): string
    {
        return match ($this->type) {
            'bank' => 'Bank',
            'e-wallet' => 'E-Wallet',
            'cash' => 'Kas Tunai',
            'investment' => 'Investasi / RDN',
            'credit_card' => 'Kartu Kredit',
            'paylater' => 'PayLater',
            'loan' => 'Pinjaman',
            default => $this->type,
        };
    }

    public function isCreditCard(): bool
    {
        return $this->type === 'credit_card';
    }

    public function isCreditSpendAccount(): bool
    {
        return in_array($this->type, ['credit_card', 'paylater'], true);
    }

    public function isLoan(): bool
    {
        return $this->type === 'loan';
    }

    public function isLiability(): bool
    {
        return in_array($this->type, ['credit_card', 'paylater', 'loan'], true);
    }

    public function availableLimit(): ?float
    {
        if ($this->isCreditCard() && $this->creditAccount) {
            return $this->creditAccount->availableLimit();
        }

        return null;
    }

    public function formattedAvailableLimit(): ?string
    {
        $limit = $this->availableLimit();
        if ($limit === null) {
            return null;
        }

        $decimals = fmod($limit, 1.0) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($limit, $decimals, ',', '.');
    }

    public function liabilityAmount(): float
    {
        if ($this->isCreditCard() && $this->creditAccount) {
            return $this->creditAccount->currentLiability();
        }

        return abs((float) $this->currentBalance());
    }

    public function formattedLiabilityAmount(): string
    {
        $amt = $this->liabilityAmount();
        $decimals = fmod($amt, 1.0) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($amt, $decimals, ',', '.');
    }
}
