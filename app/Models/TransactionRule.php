<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'name',
        'is_active',
        'priority',
        'type_criterion',
        'keyword',
        'keyword_operator',
        'amount_operator',
        'amount_min',
        'amount_max',
        'account_id',
        'action_category_id',
        'action_member_id',
        'action_description',
        'action_note',
        'stop_processing',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'amount_min' => 'decimal:2',
            'amount_max' => 'decimal:2',
            'stop_processing' => 'boolean',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function actionCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'action_category_id');
    }

    public function actionMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'action_member_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('priority')->orderBy('id');
    }

    public function keywordOperatorLabel(): string
    {
        return match ($this->keyword_operator) {
            'contains' => 'Mengandung',
            'starts_with' => 'Diawali',
            'ends_with' => 'Diakhiri',
            'exact' => 'Sama Persis',
            'regex' => 'RegEx (Pola)',
            default => 'Mengandung',
        };
    }

    public function amountOperatorLabel(): string
    {
        return match ($this->amount_operator) {
            'any' => 'Berapapun Nominal',
            'equals' => 'Sama Dengan (=)',
            'greater_than' => 'Lebih Dari (>)',
            'greater_than_or_equal' => 'Minimal (>=)',
            'less_than' => 'Kurang Dari (<)',
            'less_than_or_equal' => 'Maksimal (<=)',
            'between' => 'Di Antara Rentang',
            default => 'Berapapun',
        };
    }

    public function typeCriterionLabel(): string
    {
        return match ($this->type_criterion) {
            'expense' => 'Hanya Pengeluaran',
            'income' => 'Hanya Pemasukan',
            'all' => 'Semua Jenis Transaksi',
            default => 'Semua Jenis',
        };
    }
}
