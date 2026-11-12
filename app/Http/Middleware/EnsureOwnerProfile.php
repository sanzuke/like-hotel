<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membuka dashboard pemilik untuk akun utama (guest) yang sudah mengaktifkan
 * profil pemilik. Profil tersebut dipasang ke guard "owner" hanya untuk request
 * ini, sehingga controller dan tampilan owner yang lama tetap bekerja.
 *
 * Akun owner lama (login langsung lewat guard owner) tetap diizinkan.
 */
class EnsureOwnerProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('owner')->check()) {
            return $next($request);
        }

        $guest = auth('guest')->user();
        $owner = $guest?->owner;

        if ($owner && $owner->status === 'aktif') {
            Auth::guard('owner')->setUser($owner);

            return $next($request);
        }

        return redirect()->route('owner.activate')
            ->with('info', 'Aktifkan profil pemilik dulu untuk membuka dashboard pemilik.');
    }
}
