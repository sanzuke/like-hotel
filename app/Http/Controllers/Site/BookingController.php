<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function create(Apartment $apartment)
    {
        abort_unless($apartment->status === 'aktif', 404, 'Unit ini sedang tidak tersedia untuk dibooking.');

        return view('site.booking', [
            'a' => $apartment,
            'bookedDates' => $this->bookings->bookedDates($apartment),
        ]);
    }

    public function store(Request $request, Apartment $apartment)
    {
        $data = $request->validate([
            'guest_name' => 'required|string|max:150',
            'guest_phone' => ['required', 'string', 'max:50', 'regex:/^[0-9+\s\-]{8,}$/'],
            'guest_email' => 'required|email|max:150',
            'promo_code' => 'nullable|string|max:50',
            'checkin' => 'required|date_format:Y-m-d|after_or_equal:today',
            'checkout' => 'required|date_format:Y-m-d|after:checkin',
        ], [
            'checkin.after_or_equal' => 'Tanggal check-in tidak boleh sebelum hari ini.',
            'checkout.after' => 'Tanggal check-out harus setelah tanggal check-in.',
            'guest_phone.regex' => 'Nomor WhatsApp tidak valid.',
        ]);

        $booking = $this->bookings->create($apartment, $data);

        return redirect()->route('booking.notify', $booking->access_token);
    }

    /** Halaman setelah booking dibuat: lanjut bayar + tautan WhatsApp. */
    public function notify(string $token)
    {
        $b = Booking::with(['apartment', 'owner'])->where('access_token', $token)->firstOrFail();

        return view('site.booking-notify', ['b' => $b]);
    }
}
