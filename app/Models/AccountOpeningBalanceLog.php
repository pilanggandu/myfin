<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountOpeningBalanceLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'actor_id',
        'old_opening_balance',
        'new_opening_balance',
        'old_opened_on',
        'new_opened_on',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'old_opening_balance' => 'integer',
        'new_opening_balance' => 'integer',
        'old_opened_on' => 'date',
        'new_opened_on' => 'date',
        'created_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
