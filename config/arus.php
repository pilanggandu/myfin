<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ARUS Application Configuration
    |--------------------------------------------------------------------------
    */

    // Apakah pendaftaran akun baru dibuka untuk umum
    'registration_open' => env('ARUS_REGISTRATION_OPEN', true),

    // Zona waktu default
    'default_timezone' => env('ARUS_DEFAULT_TIMEZONE', 'Asia/Jakarta'),

    // Mata uang utama (MVP: IDR tanpa desimal)
    'currency' => 'IDR',
];
