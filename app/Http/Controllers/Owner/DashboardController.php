<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $owner = auth('owner')->user();
        $earnings = $owner->totalEarnings();
        $requested = $owner->totalRequested();

        return view('owner.dashboard', [
            'owner' => $owner,
            'units' => $owner->apartments()->count(),
            'bookingTotal' => $owner->bookings()->count(),
            'bookingPaid' => $owner->bookings()->where('booking_status', 'dibayar')->count(),
            'bookingNew' => $owner->bookings()->where('booking_status', 'baru')->count(),
            'earnings' => $earnings,
            'requested' => $requested,
            'paid' => $owner->totalPaid(),
            'available' => max(0, $earnings - $requested),
            'latestBookings' => $owner->bookings()->with('apartment')->latest('id')->take(8)->get(),
            'latestSettlements' => $owner->settlements()->latest('id')->take(5)->get(),
        ]);
    }
}
