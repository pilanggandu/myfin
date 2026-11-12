<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @var array<string, string>
     */
    public const MODULE_LABELS = [
        'transaction' => 'Transaksi',
        'account' => 'Rekening & Wallet',
        'category' => 'Kategori',
        'budget' => 'Anggaran',
        'goal' => 'Target Tabungan',
        'short_plan' => 'Short Plan',
        'recurring' => 'Jadwal Berulang',
        'credit' => 'Kredit & Cicilan',
        'member' => 'Member',
        'reminder' => 'Pengingat',
        'currency' => 'Mata Uang',
        'space' => 'Ruang Keuangan',
        'profile' => 'Profil',
        'auth' => 'Autentikasi',
        'report' => 'Laporan',
    ];

    /**
     * @var array<string, string>
     */
    public const ACTION_LABELS = [
        'created' => 'Dibuat',
        'updated' => 'Diperbarui',
        'deleted' => 'Dihapus',
        'voided' => 'Dibatalkan (Void)',
        'bulk_updated' => 'Koreksi Massal',
        'bulk_voided' => 'Void Massal',
        'archived' => 'Diarsipkan',
        'unarchived' => 'Diaktifkan Kembali',
        'adjusted' => 'Penyesuaian Saldo',
        'set_default' => 'Set Default',
        'realized' => 'Direalisasikan',
        'cancelled' => 'Dibatalkan',
        'reopened' => 'Dibuka Kembali',
        'item_added' => 'Item Ditambah',
        'item_toggled' => 'Item Dicentang',
        'item_deleted' => 'Item Dihapus',
        'amount_synced' => 'Sinkron Estimasi',
        'confirmed' => 'Dikonfirmasi',
        'skipped' => 'Dilewati',
        'paid' => 'Dibayar',
        'mark_paid' => 'Tandai Lunas',
        'reset_pending' => 'Reset Menunggu',
        'payoff' => 'Pelunasan Sekaligus',
        'note_added' => 'Catatan Kredit/Debit',
        'contributed' => 'Setoran',
        'withdrawn' => 'Penarikan',
        'status_toggled' => 'Ubah Status',
        'toggled' => 'Ubah Status',
        'sent' => 'Dikirim',
        'settings_updated' => 'Pengaturan Diperbarui',
        'data_cleaned' => 'Pembersihan Data',
        'synced' => 'Sinkronisasi Kurs',
        'exported_csv' => 'Ekspor CSV',
        'login' => 'Masuk',
        'logout' => 'Keluar',
        'registered' => 'Registrasi',
        'password_updated' => 'Ubah Kata Sandi',
        'password_reset' => 'Reset Kata Sandi',
        'google_linked' => 'Tautkan Google',
        'google_unlinked' => 'Putus Tautan Google',
    ];

    /**
     * @var array<string, string>
     */
    public const ACTION_TONES = [
        'created' => 'positive',
        'registered' => 'positive',
        'realized' => 'positive',
        'confirmed' => 'positive',
        'paid' => 'positive',
        'mark_paid' => 'positive',
        'payoff' => 'positive',
        'contributed' => 'positive',
        'unarchived' => 'positive',
        'reopened' => 'info',
        'updated' => 'info',
        'bulk_updated' => 'info',
        'adjusted' => 'info',
        'set_default' => 'info',
        'amount_synced' => 'info',
        'synced' => 'info',
        'sent' => 'info',
        'settings_updated' => 'info',
        'status_toggled' => 'info',
        'toggled' => 'info',
        'item_added' => 'info',
        'item_toggled' => 'info',
        'note_added' => 'info',
        'exported_csv' => 'info',
        'google_linked' => 'info',
        'archived' => 'warning',
        'skipped' => 'warning',
        'withdrawn' => 'warning',
        'reset_pending' => 'warning',
        'password_updated' => 'warning',
        'password_reset' => 'warning',
        'google_unlinked' => 'warning',
        'deleted' => 'danger',
        'item_deleted' => 'danger',
        'voided' => 'danger',
        'bulk_voided' => 'danger',
        'cancelled' => 'danger',
        'data_cleaned' => 'danger',
        'login' => 'neutral',
        'logout' => 'neutral',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'space_id',
        'actor_id',
        'actor_name',
        'module',
        'action',
        'event',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'properties',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeVisibleTo(Builder $query, User $user, ?Space $space, string $scope = 'all'): Builder
    {
        if ($scope === 'space' && $space) {
            return $query->where('space_id', $space->id);
        }

        if ($scope === 'account') {
            return $query->whereNull('space_id')->where('actor_id', $user->id);
        }

        return $query->where(function (Builder $q) use ($user, $space): void {
            if ($space) {
                $q->where('space_id', $space->id)
                    ->orWhere(function (Builder $sub) use ($user): void {
                        $sub->whereNull('space_id')->where('actor_id', $user->id);
                    });
            } else {
                $q->whereNull('space_id')->where('actor_id', $user->id);
            }
        });
    }

    public function moduleLabel(): string
    {
        return self::MODULE_LABELS[$this->module] ?? ucfirst(str_replace('_', ' ', $this->module));
    }

    public function actionLabel(): string
    {
        return self::ACTION_LABELS[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    public function actionTone(): string
    {
        return self::ACTION_TONES[$this->action] ?? 'neutral';
    }

    public function actorDisplayName(): string
    {
        return $this->actor?->name ?? $this->actor_name ?? 'Sistem';
    }

    /**
     * Bangun URL detail menuju entitas terkait hanya jika entitas masih ada dan milik ruang aktif.
     */
    public function subjectUrl(?Space $currentSpace = null): ?string
    {
        if (! $this->subject_type || ! $this->subject_id) {
            return null;
        }

        if (! $this->relationLoaded('subject') || $this->subject === null) {
            return null;
        }

        $subject = $this->subject;

        if ($subject instanceof Space) {
            return route('spaces.index');
        }

        if ($currentSpace && isset($subject->space_id) && (int) $subject->space_id !== (int) $currentSpace->id) {
            return null;
        }

        return match (true) {
            $subject instanceof FinancialTransaction => route('transactions.show', $subject),
            $subject instanceof Account => route('accounts.show', $subject),
            $subject instanceof Goal => route('goals.show', $subject),
            $subject instanceof ShortPlan => route('short-plans.show', $subject),
            $subject instanceof RecurringSchedule => route('recurring.show', $subject),
            $subject instanceof InstallmentPlan => route('credit.plans.show', $subject),
            $subject instanceof CreditAccount => route('credit.accounts.statement', $subject),
            default => null,
        };
    }
}
