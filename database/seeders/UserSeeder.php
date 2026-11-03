<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@arus.local',
                'password' => 'Password123!',
                'phone' => '081234567890',
            ],
            [
                'name' => 'Pengguna Demo',
                'email' => 'user@arus.local',
                'password' => 'Password123!',
                'phone' => '081234567891',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'phone' => $userData['phone'] ?? null,
                ]
            );

            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $user->ensurePersonalSpace();
        }
    }
}
