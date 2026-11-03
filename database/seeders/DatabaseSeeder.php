<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * Secara default kosong; untuk data demo gunakan: php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        // Kosong secara default agar aman untuk produksi.
    }
}
