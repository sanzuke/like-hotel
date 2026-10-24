<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Support\TableFilter;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $owner = auth('owner')->user();
        $apartments = $owner->apartments()->orderBy('name')->get(['id', 'name']);
        $payments = $owner->bookings()->whereNotNull('payment_status')->distinct()->orderBy('payment_status')->pluck('payment_status')->all();

        $filters = TableFilter::active([
            'q' => TableFilter::text($request, 'q'),
            'apartment' => TableFilter::enum($request, 'apartment', $apartments->pluck('id')->map(fn ($id) => (string) $id)->all()),
            'payment' => TableFilter::enum($request, 'payment', $payments),
        ]);

        $bookings = $owner->bookings()->with('apartment')
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('booking_code', 'like', '%'.$v.'%')
                ->orWhere('guest_name', 'like', '%'.$v.'%')
                ->orWhere('guest_phone', 'like', '%'.$v.'%')))
            ->when($filters['apartment'] ?? null, fn ($q, $v) => $q->where('bookings.apartment_id', $v))
            ->when($filters['payment'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->latest('id')->paginate(10)->withQueryString();

        return view('owner.bookings', [
            'bookings' => $bookings,
            'filters' => $filters,
            'filterApartments' => $apartments,
            'payments' => $payments,
        ]);
    }
}
