<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Booking;

class InvoiceController extends Controller
{
    public function show(Booking $booking)
    {
        return view('site.invoice', ['booking' => $booking->load('apartment')]);
    }

    public function notify(Booking $booking)
    {
        $booking->load('apartment', 'owner');
        $a = $booking->apartment;
        $total = rupiah($booking->total_price);

        $admin = "BOOKING BARU LIKE HOTEL\nKode: {$booking->booking_code}\nNama Tamu: {$booking->guest_name}\nWA Tamu: {$booking->guest_phone}\n"
            ."Apartment: {$a->name}\nLokasi: {$a->location}\nCheck-in: {$booking->checkin->toDateString()}\nCheck-out: {$booking->checkout->toDateString()}\n"
            ."Total: {$total}\nStatus: {$booking->payment_status}";

        $owner = "BOOKING BARU UNIT ANDA\nKode: {$booking->booking_code}\nNama Tamu: {$booking->guest_name}\nWA Tamu: {$booking->guest_phone}\n"
            ."Apartment: {$a->name}\nCheck-in: {$booking->checkin->toDateString()}\nCheck-out: {$booking->checkout->toDateString()}\n"
            ."Total: {$total}\nHak Owner: ".rupiah($booking->owner_amount);

        $guest = "Halo {$booking->guest_name}, booking Anda di Like Hotel telah dibuat.\nKode: {$booking->booking_code}\nApartment: {$a->name}\n"
            ."Check-in: {$booking->checkin->toDateString()}\nCheck-out: {$booking->checkout->toDateString()}\nTotal: {$total}\n"
            ."Invoice: {$booking->invoiceUrl()}\nSilakan lanjutkan pembayaran atau hubungi admin Like Hotel.";

        return view('site.notify', [
            'booking' => $booking,
            'links' => [
                'admin' => wa_link(config('likehotel.admin_wa'), $admin),
                'owner' => wa_link($booking->owner?->phone, $owner),
                'guest' => wa_link($booking->guest_phone, $guest),
            ],
        ]);
    }
}
