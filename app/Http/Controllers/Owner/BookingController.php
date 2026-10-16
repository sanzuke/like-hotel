<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;

class BookingController extends Controller
{
    public function index()
    {
        return view('owner.bookings', [
            'bookings' => auth('owner')->user()->bookings()->with('apartment')->latest('id')->get(),
        ]);
    }
}
