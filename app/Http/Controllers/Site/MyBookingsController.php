<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** "Booking Saya": riwayat booking dan akses invoice untuk penyewa yang login. */
class MyBookingsController extends Controller
{
    public function index()
    {
        $guest = Auth::guard('guest')->user();

        return view('site.my-bookings', [
            'guest' => $guest,
            'bookings' => $guest->bookings()->with('apartment')->latest('id')->paginate(10),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => ['required', 'string', 'max:50', 'regex:/^[0-9+\s\-]{8,}$/'],
        ], ['phone.regex' => 'Nomor WhatsApp tidak valid.']);

        Auth::guard('guest')->user()->update($data);

        return back()->with('success', 'Profil diperbarui. Data ini akan terisi otomatis saat Anda booking.');
    }
}
