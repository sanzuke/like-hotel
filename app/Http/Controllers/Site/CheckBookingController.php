<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckBookingController extends Controller
{
    public function form()
    {
        return view('site.check-booking');
    }

    /** Kode booking saja tidak cukup: harus disertai nomor WhatsApp pemesan. */
    public function lookup(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:80', 'phone' => 'required|string|max:50']);
        $code = trim($data['code']);

        $booking = Booking::where(fn ($q) => $q->where('booking_code', $code)->orWhere('invoice_number', $code))->first();

        if (! $booking || Fmt::normalizeWa($booking->guest_phone) !== Fmt::normalizeWa($data['phone'])) {
            throw ValidationException::withMessages(['code' => 'Data booking tidak ditemukan.']);
        }

        return redirect()->route('invoice.show', $booking->access_token);
    }
}
