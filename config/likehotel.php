<?php

return [
    'name' => 'Like Hotel',
    'tagline' => 'Sewa Apartment Dekat',
    'domain' => 'likehotelsewa.com',

    // Nomor WhatsApp admin (format internasional tanpa +).
    'admin_wa' => env('LIKEHOTEL_ADMIN_WA', '6289654231118'),

    // Nomor kontak kedua (format internasional tanpa +).
    'admin_wa_2' => env('LIKEHOTEL_ADMIN_WA_2', '6281927138394'),

    // Nama perusahaan (ditampilkan di footer dan invoice).
    'company' => env('LIKEHOTEL_COMPANY', 'PT ERA BARU PROPERTY'),

    // Komisi Like Hotel dari setiap booking sewa (0.15 = 15%).
    'commission_rate' => (float) env('LIKEHOTEL_COMMISSION_RATE', 0.15),

    // Komisi default jual/beli apartemen dalam persen (5 = 5%), nilai awal form closing.
    'sale_commission_percent' => (float) env('LIKEHOTEL_SALE_COMMISSION_PERCENT', 5),

    // Landmark untuk pencarian "dekat ...": kunci => [nama, lat, lng]. Koordinat perkiraan titik pusat.
    'landmarks' => [
        'jatinangor' => ['Jatinangor', -6.9275, 107.7710],
        'unpad-jatinangor' => ['UNPAD Jatinangor', -6.9272, 107.7728],
        'itb' => ['ITB Ganesha', -6.8915, 107.6107],
        'unpad-dipatiukur' => ['UNPAD Dipati Ukur', -6.8944, 107.6162],
        'alun-alun' => ['Alun-alun Bandung', -6.9215, 107.6070],
        'stasiun-bandung' => ['Stasiun Bandung', -6.9147, 107.6023],
        'bandara-husein' => ['Bandara Husein Sastranegara', -6.9006, 107.5763],
    ],

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
