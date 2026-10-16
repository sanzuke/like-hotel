<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Owner;
use App\Models\SaleRequest;
use App\Models\SaleUnit;
use App\Models\Settlement;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $monthStart = now()->startOfMonth();
        $paidMonth = Booking::where('booking_status', 'dibayar')->where('created_at', '>=', $monthStart);

        $totalA = Apartment::count();
        $activeA = Apartment::where('status', 'aktif')->count();
        $totalSale = SaleUnit::count();
        $activeSale = SaleUnit::where('status', 'aktif')->count();
        $totalLeads = SaleRequest::count();
        $newLeads = SaleRequest::where('status', 'baru')->count();
        $closingLeads = SaleRequest::where('status', 'closing')->count();
        $bookingMonth = Booking::where('created_at', '>=', $monthStart)->count();
        $omzet = (float) (clone $paidMonth)->sum('total_price');

        $t = config('likehotel.targets');
        $bookingPct = min(100, (int) round($bookingMonth / max(1, $t['booking']) * 100));
        $omzetPct = min(100, (int) round($omzet / max(1, $t['omzet']) * 100));
        $closingPct = min(100, (int) round($closingLeads / max(1, $t['closing']) * 100));

        $health = (int) round((
            min(100, $activeA / max(1, $totalA) * 100)
            + $bookingPct + $omzetPct
            + min(100, $activeSale / max(1, $totalSale) * 100)
            + min(100, ($totalLeads - $newLeads) / max(1, $totalLeads) * 100)
        ) / 5);

        return view('admin.dashboard', [
            'totalA' => $totalA,
            'activeA' => $activeA,
            'maintenanceA' => Apartment::where('status', 'maintenance')->count(),
            'totalSale' => $totalSale,
            'activeSale' => $activeSale,
            'soldSale' => SaleUnit::where('status', 'terjual')->count(),
            'bookingMonth' => $bookingMonth,
            'todayBooking' => Booking::whereDate('created_at', today())->count(),
            'omzet' => $omzet,
            'commission' => (float) (clone $paidMonth)->sum('commission_amount'),
            'ownerShare' => (float) (clone $paidMonth)->sum('owner_amount'),
            'settlementPending' => Settlement::whereIn('status', ['pending', 'approved'])->count(),
            'pendingOwners' => Owner::where('status', 'pending')->count(),
            'totalLeads' => $totalLeads,
            'newLeads' => $newLeads,
            'followLeads' => SaleRequest::where('status', 'follow up')->count(),
            'surveyLeads' => SaleRequest::where('status', 'survey')->count(),
            'closingLeads' => $closingLeads,
            'targets' => $t,
            'bookingPct' => $bookingPct,
            'omzetPct' => $omzetPct,
            'closingPct' => $closingPct,
            'health' => $health,
            'bookings' => Booking::with('apartment')->latest('id')->take(5)->get(),
            'leads' => SaleRequest::latest('id')->take(5)->get(),
            'saleUnits' => SaleUnit::latest('id')->take(5)->get(),
        ]);
    }
}
