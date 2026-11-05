<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Space extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'owner_id',
        'timezone',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'space_members')
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('status', 'active');
    }

    public function spaceMembers(): HasMany
    {
        return $this->hasMany(SpaceMember::class);
    }

    public function isPersonal(): bool
    {
        return $this->type === 'personal';
    }

    public function isShared(): bool
    {
        return $this->type === 'shared';
    }

    public function isOwner(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return (int) $this->owner_id === (int) $userId;
    }

    public function hasMember(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->activeMembers()->where('users.id', $userId)->exists();
    }

    public function hasActiveMember(User|int $user): bool
    {
        return $this->hasMember($user);
    }

    public function now(): Carbon
    {
        return Carbon::now($this->timezone ?: config('app.timezone', 'Asia/Jakarta'));
    }

    public function getRoleOf(User|int $user): ?string
    {
        $userId = $user instanceof User ? $user->id : $user;
        $member = $this->spaceMembers()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        return $member?->role;
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function activeAccounts(): HasMany
    {
        return $this->accounts()->whereNull('archived_at');
    }

    public function defaultAccount(): ?Account
    {
        return $this->activeAccounts()->where('is_default', true)->first()
            ?? $this->activeAccounts()->first();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function activeCategories(): HasMany
    {
        return $this->categories()->whereNull('archived_at');
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function customMembers(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function recurringSchedules(): HasMany
    {
        return $this->hasMany(RecurringSchedule::class);
    }

    public function creditAccounts(): HasMany
    {
        return $this->hasMany(CreditAccount::class);
    }

    public function installmentPlans(): HasMany
    {
        return $this->hasMany(InstallmentPlan::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function activeGoals(): HasMany
    {
        return $this->goals()->where('status', 'active');
    }

    public function goalContributions(): HasMany
    {
        return $this->hasMany(GoalContribution::class);
    }

    public function totalBalance(): int
    {
        $total = 0;
        foreach ($this->accounts()->whereNull('archived_at')->whereIn('type', ['bank', 'e-wallet', 'cash', 'investment'])->get() as $account) {
            $total += $account->currentBalance();
        }

        return $total;
    }

    public function investmentBalance(): int
    {
        $total = 0;
        foreach ($this->accounts()->whereNull('archived_at')->where('type', 'investment')->get() as $account) {
            $total += $account->currentBalance();
        }

        return $total;
    }

    public function creditLiabilities(): int
    {
        $total = 0;
        foreach ($this->creditAccounts()->whereNull('archived_at')->get() as $creditAccount) {
            $total += $creditAccount->currentLiability();
        }

        return $total;
    }

    public function createDefaultCategories(): void
    {
        $defaultIncome = [
            'Gaji',
            'Bonus & Tunjangan',
            'Usaha & Bisnis',
            'Investasi',
            'Lainnya',
        ];

        $defaultExpense = [
            'Makanan & Minuman',
            'Belanja Harian',
            'Tempat Tinggal',
            'Transportasi',
            'Utilitas & Tagihan',
            'Kesehatan',
            'Hiburan & Rekreasi',
            'Pendidikan',
            'Lainnya',
        ];

        foreach ($defaultIncome as $name) {
            $this->categories()->firstOrCreate([
                'name' => $name,
                'type' => 'income',
            ]);
        }

        foreach ($defaultExpense as $name) {
            $this->categories()->firstOrCreate([
                'name' => $name,
                'type' => 'expense',
            ]);
        }
    }
}
