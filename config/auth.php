<?php

use App\Models\Admin;
use App\Models\Owner;

return [
    // Dua area login terpisah: admin (tabel admins) dan owner (tabel owners).
    'defaults' => [
        'guard' => 'admin',
        'passwords' => null,
    ],

    'guards' => [
        'admin' => ['driver' => 'session', 'provider' => 'admins'],
        'owner' => ['driver' => 'session', 'provider' => 'owners'],
    ],

    'providers' => [
        'admins' => ['driver' => 'eloquent', 'model' => Admin::class],
        'owners' => ['driver' => 'eloquent', 'model' => Owner::class],
    ],

    'passwords' => [],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
