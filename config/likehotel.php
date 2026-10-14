<?php

return [
    // Nomor WhatsApp admin (format internasional tanpa +), satu-satunya sumber di seluruh aplikasi.
    'admin_wa' => env('ADMIN_WA', '6282129248953'),
    'admin_wa_display' => env('ADMIN_WA_DISPLAY', '0821 2924 8953'),
    'site_domain' => env('SITE_DOMAIN', 'likehotelsewa.com'),

    // Komisi Like Hotel dari booking sewa (0.10 = 10%).
    'commission_rate' => (float) env('COMMISSION_RATE', 0.10),

    // Booking yang belum dibayar otomatis dibatalkan & melepas tanggal setelah N menit.
    'pending_expiry_minutes' => (int) env('BOOKING_PENDING_EXPIRY_MINUTES', 120),

    // Target dashboard admin.
    'targets' => [
        'booking' => (int) env('TARGET_BOOKING', 25),
        'omzet' => (int) env('TARGET_OMZET', 2000000),
        'closing' => (int) env('TARGET_CLOSING', 5),
    ],

    'midtrans' => [
        'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
    ],

    'upload' => [
        'max_kb' => 4096,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],
];
