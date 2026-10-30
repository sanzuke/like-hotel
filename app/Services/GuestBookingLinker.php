<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Guest;

class GuestBookingLinker
{
    /**
     * Tautkan booking lama (belum punya akun) yang emailnya sama dengan email akun.
     * Hanya boleh dipanggil untuk akun dengan email TERVERIFIKASI.
     */
    public function link(Guest $guest): int
    {
        if (! $guest->hasVerifiedEmail()) {
            return 0;
        }

        return Booking::whereNull('guest_id')
            ->whereRaw('LOWER(guest_email) = ?', [strtolower($guest->email)])
            ->update(['guest_id' => $guest->id]);
    }
}
