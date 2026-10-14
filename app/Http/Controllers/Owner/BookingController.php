<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index()
    {
        return view('owner.bookings', [
            'bookings' => Auth::guard('owner')->user()->bookings()->with('apartment')->latest('id')->get(),
        ]);
    }
}
