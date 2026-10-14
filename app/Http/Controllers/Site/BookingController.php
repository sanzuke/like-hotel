<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Booking;
use App\Services\BookingException;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function create(Apartment $apartment)
    {
        abort_unless($apartment->status === 'aktif', 404, 'Unit ini sedang tidak tersedia untuk dibooking.');

        return view('site.booking', [
            'apartment' => $apartment,
            'bookedDates' => $this->bookings->bookedDates($apartment),
        ]);
    }

    public function store(Request $request, Apartment $apartment)
    {
        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:150'],
            'guest_phone' => ['required', 'string', 'max:50'],
            'guest_email' => ['required', 'email', 'max:150'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'checkin' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'checkout' => ['required', 'date_format:Y-m-d', 'after:checkin'],
        ], [
            'checkin.after_or_equal' => 'Tanggal check-in tidak boleh di masa lalu.',
            'checkout.after' => 'Tanggal check-out harus setelah tanggal check-in.',
        ]);

        try {
            $booking = $this->bookings->create($apartment, $data);
        } catch (BookingException $e) {
            throw ValidationException::withMessages(['booking' => $e->getMessage()]);
        }

        return redirect()->route('invoice.show', $booking);
    }

    public function lookupForm()
    {
        return view('site.lookup');
    }

    /** Kode booking saja tidak cukup: harus cocok dengan email / WhatsApp pemesan. */
    public function lookup(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:80'], 'contact' => ['required', 'string', 'max:150']]);

        $booking = Booking::where(fn ($q) => $q->where('booking_code', trim($data['code']))->orWhere('invoice_number', trim($data['code'])))->first();

        $digits = fn (?string $v) => preg_replace('/\D/', '', $v ?? '');
        $contact = trim($data['contact']);

        $match = $booking && (
            strcasecmp($booking->guest_email, $contact) === 0
            || ($digits($contact) !== '' && substr($digits($booking->guest_phone), -9) === substr($digits($contact), -9))
        );

        if (! $match) {
            return back()->withInput()->withErrors(['code' => 'Data booking tidak ditemukan atau kontak tidak cocok.']);
        }

        return redirect()->route('invoice.show', $booking);
    }
}
