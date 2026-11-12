<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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
            'credit_limit' => 'float',
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

    public function creditDebitNotes(): HasMany
    {
        return $this->hasMany(CreditDebitNote::class)->orderBy('note_date', 'desc')->orderBy('id', 'desc');
    }

    public function recurringSchedules(): HasMany
    {
        return $this->hasMany(RecurringSchedule::class);
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

    public function currentLiability(): float
    {
        $pendingPrincipal = (float) Installment::query()
            ->active()
            ->whereHas('installmentPlan', function ($q) {
                $q->where('credit_account_id', $this->id);
            })
            ->where('status', 'pending')
            ->sum('principal_amount');

        if ($this->ledgerAccount) {
            $ledgerDebt = max(0.0, -$this->ledgerAccount->currentBalance());
            $nonInstallmentDebt = max(0.0, $ledgerDebt - $pendingPrincipal);

            return round($pendingPrincipal + $nonInstallmentDebt, 2);
        }

        return round($pendingPrincipal, 2);
    }

    public function installmentLiability(): float
    {
        return $this->installmentPrincipalLiability();
    }

    public function installmentPrincipalLiability(): float
    {
        return round((float) Installment::query()
            ->active()
            ->whereHas('installmentPlan', function ($q) {
                $q->where('credit_account_id', $this->id);
            })
            ->where('status', 'pending')
            ->sum('principal_amount'), 2);
    }

    public function installmentTotalLiability(): float
    {
        return round((float) Installment::query()
            ->active()
            ->whereHas('installmentPlan', function ($q) {
                $q->where('credit_account_id', $this->id);
            })
            ->where('status', 'pending')
            ->sum('total_amount'), 2);
    }

    public function installmentInterestAndFeeLiability(): float
    {
        $interestAndFee = (float) Installment::query()
            ->active()
            ->whereHas('installmentPlan', function ($q) {
                $q->where('credit_account_id', $this->id);
            })
            ->where('status', 'pending')
            ->sum(DB::raw('interest_amount + fee_amount'));

        return round($interestAndFee, 2);
    }

    public function nonInstallmentLiability(): float
    {
        if ($this->ledgerAccount) {
            $ledgerDebt = max(0.0, -$this->ledgerAccount->currentBalance());
            $pendingPrincipal = $this->installmentPrincipalLiability();

            return round(max(0.0, $ledgerDebt - $pendingPrincipal), 2);
        }

        return 0.0;
    }

    public function availableLimit(): float
    {
        $available = (float) $this->credit_limit - $this->currentLiability();

        return max(0.0, round($available, 2));
    }

    public function utilizationRate(): float
    {
        if ($this->credit_limit <= 0) {
            return 0.0;
        }

        return round(($this->currentLiability() / $this->credit_limit) * 100, 1);
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

    public function getFormattedUtilizationRateAttribute(): string
    {
        return $this->utilizationRate().'%';
    }

    public function getFormattedLimitAttribute(): string
    {
        $decimals = fmod($this->credit_limit, 1.0) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($this->credit_limit, $decimals, ',', '.');
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

    public function getStatementBill(?string $monthYear = null): array
    {
        try {
            $date = $monthYear ? Carbon::createFromFormat('Y-m', $monthYear)->startOfMonth() : now()->startOfMonth();
        } catch (\Throwable) {
            $date = now()->startOfMonth();
        }

        $startOfMonth = $date->copy()->startOfMonth()->toDateString();
        $endOfMonth = $date->copy()->endOfMonth()->toDateString();

        $installments = Installment::query()
            ->whereHas('installmentPlan', function ($q) {
                $q->where('credit_account_id', $this->id)
                    ->where('status', 'active');
            })
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where(function ($sub) use ($startOfMonth) {
                    $sub->where('status', 'pending')
                        ->where('due_date', '<', $startOfMonth);
                })->orWhereBetween('due_date', [$startOfMonth, $endOfMonth]);
            })
            ->with(['installmentPlan.category', 'installmentPlan.member', 'payments'])
            ->orderBy('due_date', 'asc')
            ->orderBy('installment_number', 'asc')
            ->get();

        $totalBilled = (int) $installments->sum('total_amount');
        $totalPending = (int) $installments->where('status', 'pending')->sum('total_amount');
        $totalPaid = (int) $installments->where('status', 'paid')->sum('total_amount');

        $cycleDate = null;
        if (! empty($this->billing_cycle_day)) {
            $prevMonth = $date->copy()->subMonth();
            $maxDay = $prevMonth->daysInMonth;
            $cycleDate = $prevMonth->day(min($this->billing_cycle_day, $maxDay));
        }

        $dueDate = null;
        if (! empty($this->due_day)) {
            $maxDay = $date->daysInMonth;
            $dueDate = $date->copy()->day(min($this->due_day, $maxDay));
        } else {
            $dueDate = $date->copy()->endOfMonth();
        }

        $pendingCount = $installments->where('status', 'pending')->count();
        $isFullyPaid = $installments->isNotEmpty() && $pendingCount === 0;

        return [
            'month_year' => $date->format('Y-m'),
            'month_name' => $date->translatedFormat('F Y'),
            'date' => $date,
            'cycle_date' => $cycleDate,
            'due_date' => $dueDate,
            'total_billed' => $totalBilled,
            'total_pending' => $totalPending,
            'total_paid' => $totalPaid,
            'formatted_total_billed' => 'Rp '.number_format($totalBilled, 0, ',', '.'),
            'formatted_total_pending' => 'Rp '.number_format($totalPending, 0, ',', '.'),
            'formatted_total_paid' => 'Rp '.number_format($totalPaid, 0, ',', '.'),
            'is_fully_paid' => $isFullyPaid,
            'item_count' => $installments->count(),
            'pending_count' => $pendingCount,
            'installments' => $installments,
        ];
    }

    public function getStatementDueAmountAttribute(): int
    {
        return $this->getStatementBill()['total_pending'];
    }

    public function getFormattedStatementDueAttribute(): string
    {
        return 'Rp '.number_format($this->statement_due_amount, 0, ',', '.');
    }

    public function getStatementPendingCountAttribute(): int
    {
        return $this->getStatementBill()['pending_count'];
    }

    public function calculateFirstDueDate(Carbon|string $transactionDate): Carbon
    {
        $txDate = $transactionDate instanceof Carbon ? $transactionDate->copy() : Carbon::parse($transactionDate);

        if (! empty($this->billing_cycle_day)) {
            $maxDayThisMonth = $txDate->daysInMonth;
            $cycleDayThisMonth = min($this->billing_cycle_day, $maxDayThisMonth);

            if ($txDate->day <= $cycleDayThisMonth) {
                $statementDate = $txDate->copy()->setDay($cycleDayThisMonth);
            } else {
                $nextMonth = $txDate->copy()->addMonthNoOverflow();
                $statementDate = $nextMonth->setDay(min($this->billing_cycle_day, $nextMonth->daysInMonth));
            }

            if (! empty($this->due_day)) {
                if ($this->due_day > $this->billing_cycle_day) {
                    return $statementDate->copy()->setDay(min($this->due_day, $statementDate->daysInMonth));
                }

                $dueMonth = $statementDate->copy()->addMonthNoOverflow();

                return $dueMonth->setDay(min($this->due_day, $dueMonth->daysInMonth));
            }

            return $statementDate->copy()->addMonthNoOverflow();
        }

        if (! empty($this->due_day)) {
            $nextMonth = $txDate->copy()->addMonthNoOverflow();

            return $nextMonth->setDay(min($this->due_day, $nextMonth->daysInMonth));
        }

        return $txDate->copy()->addMonthNoOverflow();
    }
}
