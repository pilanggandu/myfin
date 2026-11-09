<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'category_id',
        'year',
        'month',
        'limit_amount',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'limit_amount' => 'integer',
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

    public function spentAmount(): int
    {
        $start = sprintf('%04d-%02d-01', $this->year, $this->month);
        $end = Carbon::createFromDate($this->year, $this->month, 1)->endOfMonth()->toDateString();

        $directExpense = (int) FinancialTransaction::query()
            ->where('financial_transactions.space_id', $this->space_id)
            ->where('financial_transactions.category_id', $this->category_id)
            ->posted()
            ->expense()
            ->whereBetween('financial_transactions.transaction_date', [$start, $end])
            ->join('transaction_entries', 'financial_transactions.id', '=', 'transaction_entries.transaction_id')
            ->sum(DB::raw('ABS(transaction_entries.amount_signed)'));

        $transferFees = (int) FinancialTransaction::query()
            ->where('space_id', $this->space_id)
            ->where('fee_category_id', $this->category_id)
            ->posted()
            ->transfer()
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('fee_amount');

        return $directExpense + $transferFees;
    }

    public function remainingAmount(): int
    {
        return (int) ($this->limit_amount - $this->spentAmount());
    }

    public function percentageSpent(): float
    {
        if ($this->limit_amount <= 0) {
            return 0.0;
        }

        return round(($this->spentAmount() / $this->limit_amount) * 100, 1);
    }

    public function isOverBudget(): bool
    {
        return $this->spentAmount() > $this->limit_amount;
    }

    public function periodLabel(): string
    {
        return Carbon::createFromDate($this->year, $this->month, 1)->translatedFormat('F Y');
    }

    public function scopePeriod($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }
}
