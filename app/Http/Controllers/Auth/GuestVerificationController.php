<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GuestBookingLinker;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Verifikasi email penyewa. Tautan hanya sah bila dibuka saat masuk ke akun yang sama:
 * mencegah orang yang mendaftarkan email orang lain ikut mendapat booking pemilik email aslinya.
 */
class GuestVerificationController extends Controller
{
    public function verify(Request $request, GuestBookingLinker $linker, int $id, string $hash): RedirectResponse
    {
        $guest = $request->user('guest');

        abort_unless($guest->getKey() === $id && hash_equals(sha1(strtolower($guest->email)), $hash), 403);

        if (! $guest->hasVerifiedEmail()) {
            $guest->markEmailAsVerified();
            Audit::log('guest.email_verified', "Penyewa memverifikasi email: {$guest->email}", $guest, [], ['type' => 'guest', 'name' => $guest->name]);
        }

        $linked = $linker->link($guest);

        return redirect()->route('guest.bookings')->with('success', $linked > 0
            ? "Email terverifikasi. {$linked} booking lama Anda kini tertaut ke akun."
            : 'Email terverifikasi.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $guest = $request->user('guest');

        if ($guest->hasVerifiedEmail()) {
            return back()->with('success', 'Email Anda sudah terverifikasi.');
        }

        try {
            $guest->sendEmailVerification();
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['email' => 'Email verifikasi belum dapat dikirim. Coba lagi nanti atau hubungi admin.']);
        }

        return back()->with('success', 'Email verifikasi dikirim ke '.$guest->email.'.');
    }
}
