<?php

use App\Services\BookingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('bookings:expire', function (BookingService $bookings) {
    $n = $bookings->expirePending();
    $this->info("{$n} booking belum dibayar dibatalkan (kedaluwarsa).");
})->purpose('Batalkan booking belum dibayar yang melewati batas waktu');

// Jalankan lewat cron: * * * * * php artisan schedule:run
Schedule::command('bookings:expire')->everyFiveMinutes();
