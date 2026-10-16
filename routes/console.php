<?php

use App\Services\BookingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Batalkan booking yang tidak dibayar sampai batas waktu agar kalender kembali terbuka.
Artisan::command('likehotel:expire-bookings', function (BookingService $bookings) {
    $this->info($bookings->expirePending().' booking kedaluwarsa dibatalkan.');
})->purpose('Batalkan booking pending yang melewati batas waktu pembayaran');

Schedule::command('likehotel:expire-bookings')->everyFiveMinutes();
