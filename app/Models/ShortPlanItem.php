<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortPlanItem extends Model
{
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estimated_amount' => 0,
        'is_checked' => false,
        'sort_order' => 0,
    ];

    protected $fillable = [
        'space_id',
        'short_plan_id',
        'title',
        'estimated_amount',
        'is_checked',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'estimated_amount' => 'float',
            'is_checked' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function shortPlan(): BelongsTo
    {
        return $this->belongsTo(ShortPlan::class);
    }
}
