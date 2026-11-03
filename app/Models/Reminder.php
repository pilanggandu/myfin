<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'user_id',
        'title',
        'type',
        'channel',
        'amount',
        'due_date',
        'reminder_time',
        'frequency',
        'notes',
        'is_active',
        'last_sent_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
    ];

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'bill_payment' => 'Tagihan & Pembayaran',
            'daily_bookkeeping' => 'Catat Transaksi Harian',
            'budget_alert' => 'Peringatan Anggaran',
            'custom' => 'Pengingat Kustom',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function channelLabel(): string
    {
        return match ($this->channel) {
            'email' => 'Email',
            'whatsapp' => 'WhatsApp',
            'both' => 'Email & WhatsApp',
            default => ucfirst($this->channel),
        };
    }

    public function frequencyLabel(): string
    {
        return match ($this->frequency) {
            'once' => 'Satu Kali',
            'daily' => 'Harian (Setiap Hari)',
            'weekly' => 'Mingguan (Tiap Pekan)',
            'monthly' => 'Bulanan',
            default => ucfirst($this->frequency),
        };
    }
}
