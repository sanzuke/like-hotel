<?php

return [
    'name' => 'Like Hotel',
    'tagline' => 'Sewa Apartment Dekat',
    'domain' => 'likehotelsewa.com',

    // Nomor WhatsApp admin (format internasional tanpa +).
    'admin_wa' => env('LIKEHOTEL_ADMIN_WA', '6282129248953'),

    // Komisi Like Hotel dari setiap booking (0.10 = 10%).
    'commission_rate' => (float) env('LIKEHOTEL_COMMISSION_RATE', 0.10),

    // Booking yang belum dibayar dibatalkan otomatis setelah N jam.
    'pending_expiry_hours' => (int) env('LIKEHOTEL_PENDING_EXPIRY_HOURS', 24),

    // Target bisnis bulanan untuk dashboard admin.
    'targets' => [
        'booking' => (int) env('LIKEHOTEL_TARGET_BOOKING', 25),
        'omzet' => (int) env('LIKEHOTEL_TARGET_OMZET', 2000000),
        'closing' => (int) env('LIKEHOTEL_TARGET_CLOSING', 5),
    ],

    'midtrans' => [
        'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
    ],

    // Profil resmi (kosongkan bila belum ada). Dipakai untuk schema sameAs dan tautan footer.
    'social' => [
        'instagram' => env('SOCIAL_INSTAGRAM'),
        'tiktok' => env('SOCIAL_TIKTOK'),
        'facebook' => env('SOCIAL_FACEBOOK'),
        'google_business' => env('SOCIAL_GOOGLE_BUSINESS'),
    ],

    // Kode verifikasi Google Search Console (meta tag).
    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),
];
