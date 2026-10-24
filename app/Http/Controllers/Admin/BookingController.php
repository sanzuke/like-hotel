<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Booking;
use App\Support\Audit;
use App\Support\TableFilter;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $total = Booking::count();
        $paid = Booking::where('booking_status', 'dibayar');

        $payments = Booking::query()->whereNotNull('payment_status')->distinct()->orderBy('payment_status')->pluck('payment_status')->all();
        $statuses = Booking::query()->whereNotNull('booking_status')->distinct()->orderBy('booking_status')->pluck('booking_status')->all();

        $filters = TableFilter::active([
            'q' => TableFilter::text($request, 'q'),
            'apartment' => TableFilter::id($request, 'apartment'),
            'payment' => TableFilter::enum($request, 'payment', $payments),
            'booking' => TableFilter::enum($request, 'booking', $statuses),
        ]);

        $bookings = Booking::with(['apartment', 'owner'])
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('booking_code', 'like', '%'.$v.'%')
                ->orWhere('invoice_number', 'like', '%'.$v.'%')
                ->orWhere('guest_name', 'like', '%'.$v.'%')
                ->orWhere('guest_phone', 'like', '%'.$v.'%')))
            ->when($filters['apartment'] ?? null, fn ($q, $v) => $q->where('apartment_id', (int) $v))
            ->when($filters['payment'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($filters['booking'] ?? null, fn ($q, $v) => $q->where('booking_status', $v))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.bookings', [
            'bookings' => $bookings,
            'filters' => $filters,
            'filterApartments' => Apartment::orderBy('name')->get(['id', 'name']),
            'paymentOptions' => $payments,
            'bookingOptions' => $statuses,
            // Statistik dihitung di database agar mencakup seluruh booking, bukan hanya halaman yang tampil.
            'stats' => [
                'total' => $total,
                'lunas' => (clone $paid)->count(),
                'belum' => $total - (clone $paid)->count(),
                'komisi' => (clone $paid)->sum('commission_amount'),
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
