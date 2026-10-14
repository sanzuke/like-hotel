<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['apartment', 'owner'])->latest('id')->get();

        $paid = $bookings->filter(fn ($b) => $b->isPaid());

        return view('admin.bookings', [
            'bookings' => $bookings,
            'totalLunas' => $paid->count(),
            'totalPending' => $bookings->count() - $paid->count(),
            'totalKomisi' => $paid->sum('commission_amount'),
        ]);
    }

    public function markPaid(Booking $booking)
    {
        if ($booking->isPaid()) {
            return back()->with('error', 'Booking sudah lunas.');
        }

        $booking->update(['payment_status' => 'lunas manual', 'booking_status' => Booking::STATUS_PAID]);
        ActivityLog::record('booking.mark_paid', $booking);

        return back()->with('status', "Booking {$booking->booking_code} ditandai lunas.");
    }

    public function cancel(Booking $booking)
    {
        if ($booking->booking_status === Booking::STATUS_CANCELLED) {
            return back()->with('error', 'Booking sudah dibatalkan.');
        }

        $booking->update(['payment_status' => 'dibatalkan manual', 'booking_status' => Booking::STATUS_CANCELLED]);
        ActivityLog::record('booking.cancel', $booking);

        return back()->with('status', "Booking {$booking->booking_code} dibatalkan.");
    }
}
