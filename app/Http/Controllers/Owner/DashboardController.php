<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\SettlementService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(SettlementService $settlements)
    {
        $owner = Auth::guard('owner')->user();

        return view('owner.dashboard', [
            'owner' => $owner,
            'unit' => $owner->apartments()->count(),
            'booking' => $owner->bookings()->count(),
            'paidBooking' => $owner->bookings()->paid()->count(),
            'pendingBooking' => $owner->bookings()->where('booking_status', Booking::STATUS_NEW)->count(),
            'balance' => $settlements->balance($owner->id),
            'latestBookings' => $owner->bookings()->with('apartment')->latest('id')->limit(8)->get(),
            'latestSettlements' => $owner->settlements()->latest('id')->limit(5)->get(),
        ]);
    }
}
