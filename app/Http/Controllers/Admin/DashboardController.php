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
    public function index()
    {
        $monthStart = now()->startOfMonth();
        $paidMonth = Booking::paid()->where('created_at', '>=', $monthStart);

        $totalA = Apartment::count();
        $activeA = Apartment::where('status', 'aktif')->count();
        $bookingMonth = Booking::where('created_at', '>=', $monthStart)->count();
        $incomeMonth = (float) (clone $paidMonth)->sum('total_price');

        $totalSale = SaleUnit::count();
        $activeSale = SaleUnit::where('status', 'aktif')->count();
        $leadCount = fn (string $s) => SaleRequest::where('status', $s)->count();
        $totalLeads = SaleRequest::count();
        $newLeads = $leadCount('baru');
        $closingLeads = $leadCount('closing');

        $t = config('likehotel.targets');
        $bookingPercent = min(100, round($bookingMonth / max(1, $t['booking']) * 100));
        $omzetPercent = min(100, round($incomeMonth / max(1, $t['omzet']) * 100));
        $closingPercent = min(100, round($closingLeads / max(1, $t['closing']) * 100));

        $health = round((
            min(100, $activeA / max(1, $totalA) * 100)
            + $bookingPercent + $omzetPercent
            + min(100, $activeSale / max(1, $totalSale) * 100)
            + min(100, ($totalLeads - $newLeads) / max(1, $totalLeads) * 100)
        ) / 5);

        return view('admin.dashboard', [
            'totalA' => $totalA, 'activeA' => $activeA,
            'maintenanceA' => Apartment::where('status', 'maintenance')->count(),
            'totalSale' => $totalSale, 'activeSale' => $activeSale,
            'soldSale' => SaleUnit::where('status', 'terjual')->count(),
            'bookingMonth' => $bookingMonth,
            'todayBooking' => Booking::whereDate('created_at', today())->count(),
            'incomeMonth' => $incomeMonth,
            'commMonth' => (float) (clone $paidMonth)->sum('commission_amount'),
            'ownerMonth' => (float) (clone $paidMonth)->sum('owner_amount'),
            'settlementPending' => Settlement::whereIn('status', ['pending', 'approved'])->count(),
            'pendingOwners' => Owner::where('status', 'pending')->count(),
            'totalLeads' => $totalLeads, 'newLeads' => $newLeads,
            'followLeads' => $leadCount('follow up'), 'surveyLeads' => $leadCount('survey'),
            'closingLeads' => $closingLeads,
            'targets' => $t,
            'bookingPercent' => $bookingPercent, 'omzetPercent' => $omzetPercent, 'closingPercent' => $closingPercent,
            'health' => $health,
            'rows' => Booking::with('apartment')->latest('id')->limit(5)->get(),
            'leads' => SaleRequest::latest('id')->limit(5)->get(),
            'saleUnits' => SaleUnit::latest('id')->limit(5)->get(),
        ]);
    }
}
