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
            'principal_amount' => 'float',
            'interest_amount' => 'float',
            'fee_amount' => 'float',
            'total_amount' => 'float',
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

    public function creditDebitNotes(): HasMany
    {
        return $this->hasMany(CreditDebitNote::class)->orderBy('note_date', 'desc')->orderBy('id', 'desc');
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

    public function isPaidWithoutCash(): bool
    {
        return $this->isPaid() && $this->payments->isEmpty();
    }

    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    public function isDueToday(): bool
    {
        return $this->isPending() && $this->due_date->isToday();
    }

    public function isDueSoon(int $days = 3): bool
    {
        if (! $this->isPending() || $this->isOverdue() || $this->isDueToday()) {
            return false;
        }

        $now = now()->startOfDay();
        $due = $this->due_date->copy()->startOfDay();

        return $due->diffInDays($now) <= $days;
    }

    public function daysOverdue(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return (int) $this->due_date->diffInDays(now()->startOfDay());
    }

    public function dueStatus(): string
    {
        if ($this->isPaid()) {
            return 'paid';
        }
        if ($this->isOverdue()) {
            return 'overdue';
        }
        if ($this->isDueToday()) {
            return 'due_today';
        }
        if ($this->isDueSoon()) {
            return 'due_soon';
        }

        return 'upcoming';
    }

    public function dueStatusLabel(): string
    {
        return match ($this->dueStatus()) {
            'paid' => 'Lunas',
            'overdue' => 'Terlambat '.$this->daysOverdue().' hari',
            'due_today' => 'Jatuh Tempo Hari Ini',
            'due_soon' => 'Jatuh Tempo Segera',
            default => 'Mendatang',
        };
    }

    public function whatsAppReminderMessage(): string
    {
        $plan = $this->installmentPlan;
        $creditAccount = $plan->creditAccount;

        return "*[ARUS - PENGINGAT JATUH TEMPO CICILAN]*\n\n"
            ."Halo, mengingatkan tagihan cicilan Anda:\n"
            ."• *Barang / Rencana:* {$plan->name} (#{$this->installment_number}/{$plan->tenor})\n"
            ."• *Pembiayaan:* {$creditAccount->name}\n"
            ."• *Pokok Cicilan:* {$this->formatted_principal}\n"
            .($this->interest_amount > 0 ? "• *Bunga:* {$this->formatted_interest}\n" : '')
            .($this->fee_amount > 0 ? "• *Biaya Admin:* {$this->formatted_fee}\n" : '')
            ."• *Total Tagihan:* {$this->formatted_total_amount}\n"
            .'• *Jatuh Tempo:* '.$this->due_date->format('d M Y')."\n"
            .'• *Status:* '.($this->isOverdue() ? '⚠️ TERLAMBAT' : 'Menunggu Pembayaran')."\n\n"
            .'Mohon pastikan saldo rekening pembayaran mencukupi.';
    }

    public function whatsAppReminderUrl(?string $phone = null): string
    {
        $digits = preg_replace('/\D/', '', $phone ?? '');
        if ($digits && str_starts_with($digits, '08')) {
            $digits = '628'.substr($digits, 2);
        } elseif ($digits && str_starts_with($digits, '8')) {
            $digits = '628'.substr($digits, 1);
        }

        $text = rawurlencode($this->whatsAppReminderMessage());

        return $digits
            ? "https://api.whatsapp.com/send?phone={$digits}&text={$text}"
            : "https://api.whatsapp.com/send?text={$text}";
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        $decimals = fmod((float) $this->total_amount, 1) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($this->total_amount, $decimals, ',', '.');
    }

    public function getFormattedPrincipalAttribute(): string
    {
        $decimals = fmod((float) $this->principal_amount, 1) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($this->principal_amount, $decimals, ',', '.');
    }

    public function getFormattedInterestAttribute(): string
    {
        $decimals = fmod((float) $this->interest_amount, 1) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($this->interest_amount, $decimals, ',', '.');
    }

    public function getFormattedFeeAttribute(): string
    {
        $decimals = fmod((float) $this->fee_amount, 1) == 0.0 ? 0 : 2;

        return 'Rp '.number_format($this->fee_amount, $decimals, ',', '.');
    }
}
