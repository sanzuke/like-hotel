<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\Audit;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['apartment', 'owner'])->latest('id')->get();
        $paid = $bookings->where('booking_status', 'dibayar');

        return view('admin.bookings', [
            'bookings' => $bookings,
            'stats' => [
                'total' => $bookings->count(),
                'lunas' => $paid->count(),
                'belum' => $bookings->count() - $paid->count(),
                'komisi' => $paid->sum('commission_amount'),
            ],
        ]);
    }

    public function markPaid(Booking $booking)
    {
        if ($booking->isCancelled()) {
            return back()->with('error', 'Booking yang sudah dibatalkan tidak dapat ditandai lunas.');
        }

        $booking->markPaid('lunas manual');
        Audit::log('booking.paid_manual', "Booking {$booking->booking_code} ditandai lunas manual", $booking);

        return back()->with('success', "Booking {$booking->booking_code} ditandai lunas.");
    }

    public function cancel(Booking $booking)
    {
        if ($booking->isPaid()) {
            return back()->with('error', 'Booking yang sudah lunas tidak dapat dibatalkan dari sini.');
        }

        $booking->markCancelled('dibatalkan manual');
        Audit::log('booking.cancelled_manual', "Booking {$booking->booking_code} dibatalkan manual", $booking);

        return back()->with('success', "Booking {$booking->booking_code} dibatalkan.");
    }
}
