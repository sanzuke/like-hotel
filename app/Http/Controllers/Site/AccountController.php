<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\SaleRequest;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Halaman akun utama: ringkasan, aktivasi profil pemilik (menyewakan unit),
 * dan daftar pengajuan titip jual milik pengguna.
 */
class AccountController extends Controller
{
    public function index()
    {
        $guest = auth('guest')->user();

        return view('site.account', [
            'guest' => $guest,
            'bookingCount' => $guest->bookings()->count(),
            'saleRequestCount' => $guest->saleRequests()->count(),
        ]);
    }

    public function ownerCreate()
    {
        $guest = auth('guest')->user();

        if ($guest->owner) {
            return redirect()->route('owner.dashboard')
                ->with('info', 'Profil pemilik Anda sudah aktif.');
        }

        return view('site.account-owner', ['guest' => $guest]);
    }

    public function ownerStore(Request $request): RedirectResponse
    {
        $guest = auth('guest')->user();

        if ($guest->owner) {
            return redirect()->route('owner.dashboard');
        }

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => ['required', 'string', 'max:50', 'regex:/^[0-9+\s\-]{8,}$/'],
        ], ['phone.regex' => 'Nomor WhatsApp tidak valid.']);

        if (Owner::where('email', $guest->email)->exists()) {
            return back()->withInput()->with('error', 'Email akun ini sudah terdaftar sebagai pemilik. Hubungi admin untuk menautkan akun.');
        }

        DB::transaction(function () use ($guest, $data) {
            $owner = Owner::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $guest->email,
                'status' => 'aktif',
            ]);

            $guest->forceFill(['name' => $data['name'], 'phone' => $data['phone']])->save();
            $guest->owner()->associate($owner);
            $guest->save();

            Audit::log('owner.activated', "Profil pemilik diaktifkan dari akun {$guest->email}", $owner, [], [
                'type' => 'guest', 'id' => $guest->id, 'name' => $guest->name,
            ]);
        });

        return redirect()->route('owner.dashboard')
            ->with('success', 'Profil pemilik aktif. Anda sekarang bisa menambahkan unit sewa.');
    }

    public function saleRequests()
    {
        $guest = auth('guest')->user();

        return view('site.my-sale-requests', [
            'guest' => $guest,
            'requests' => $guest->saleRequests()->latest('id')->paginate(10),
            'stages' => SaleRequest::STAGES,
        ]);
    }
}
