<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailOtp extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'password',
        'otp_code',
        'attempts',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isMaxAttemptsReached(): bool
    {
        return $this->attempts >= 5;
    }

    public function isValid(string $inputCode): bool
    {
        if ($this->isExpired() || $this->isMaxAttemptsReached()) {
            return false;
        }

        return hash_equals(trim($this->otp_code), trim($inputCode));
    }

    public static function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }
}
