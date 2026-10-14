<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Booking;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'checkin' => ['nullable', 'date'],
            'checkout' => ['nullable', 'date', 'after:checkin'],
        ]);

        $q = Apartment::active()->latest('id');

        if ($term = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('location', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%"));
        }

        // Saring unit yang kosong pada rentang tanggal yang dipilih.
        if ($request->filled('checkin') && $request->filled('checkout')) {
            $q->whereNotIn('id', Booking::blocking()
                ->overlapping($request->query('checkin'), $request->query('checkout'))
                ->select('apartment_id'));
        }

        return view('site.apartments', ['apartments' => $q->get()]);
    }

    public function show(Apartment $apartment)
    {
        return view('site.apartment-show', ['apartment' => $apartment->load('images')]);
    }
}
