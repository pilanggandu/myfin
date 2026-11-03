<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'whatsapp_api_key',
        'whatsapp_gateway_url',
        'email_notifications_enabled',
        'whatsapp_notifications_enabled',
        'daily_reminder_enabled',
        'daily_reminder_time',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_notifications_enabled' => 'boolean',
            'whatsapp_notifications_enabled' => 'boolean',
            'daily_reminder_enabled' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }

    public function ownedSpaces(): HasMany
    {
        return $this->hasMany(Space::class, 'owner_id');
    }

    public function spaces(): BelongsToMany
    {
        return $this->belongsToMany(Space::class, 'space_members')
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    public function activeSpaces(): BelongsToMany
    {
        return $this->spaces()->wherePivot('status', 'active');
    }

    public function personalSpace(): ?Space
    {
        return $this->activeSpaces()->where('type', 'personal')->first();
    }

    public function ensurePersonalSpace(): Space
    {
        $personal = $this->personalSpace();
        if ($personal) {
            return $personal;
        }

        return DB::transaction(function () {
            $existing = Space::where('owner_id', $this->id)
                ->where('type', 'personal')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->members()->syncWithoutDetaching([
                    $this->id => ['role' => 'owner', 'status' => 'active'],
                ]);

                return $existing;
            }

            try {
                $space = Space::create([
                    'name' => 'Keuangan Pribadi',
                    'type' => 'personal',
                    'owner_id' => $this->id,
                    'timezone' => config('arus.default_timezone', 'Asia/Jakarta'),
                ]);

                $space->members()->attach($this->id, [
                    'role' => 'owner',
                    'status' => 'active',
                ]);

                $space->createDefaultCategories();

                return $space;
            } catch (UniqueConstraintViolationException $e) {
                $space = Space::where('owner_id', $this->id)
                    ->where('type', 'personal')
                    ->firstOrFail();

                $space->members()->syncWithoutDetaching([
                    $this->id => ['role' => 'owner', 'status' => 'active'],
                ]);

                return $space;
            }
        });
    }
}
