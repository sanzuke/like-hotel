<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Guest;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/** Daftar / login penyewa lewat Google (OAuth 2.0 authorization code). Akun langsung aktif. */
class GuestGoogleController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->configured()) {
            return redirect()->route('guest.login')->withErrors(['email' => 'Login Google belum diaktifkan.']);
        }

        $state = Str::random(40);
        $request->session()->put('guest_google_state', $state);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('guest.google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]));
    }

    public function callback(Request $request): RedirectResponse
    {
        $fail = fn (string $msg) => redirect()->route('guest.login')->withErrors(['email' => $msg]);

        $state = $request->session()->pull('guest_google_state');
        if (! $this->configured() || ! $state || ! hash_equals($state, (string) $request->query('state'))) {
            return $fail('Sesi login Google tidak valid. Silakan coba lagi.');
        }
        if ($request->filled('error') || ! $request->filled('code')) {
            return $fail('Login Google dibatalkan.');
        }

        try {
            $token = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'code' => $request->query('code'),
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => route('guest.google.callback'),
                'grant_type' => 'authorization_code',
            ]);
            if (! $token->ok() || ! $token->json('access_token')) {
                return $fail('Gagal masuk dengan Google. Silakan coba lagi.');
            }

            $profile = Http::withToken($token->json('access_token'))->timeout(10)
                ->get('https://openidconnect.googleapis.com/v1/userinfo')->json();
        } catch (Throwable) {
            return $fail('Google tidak dapat dihubungi. Silakan coba lagi.');
        }

        $sub = $profile['sub'] ?? null;
        $email = strtolower((string) ($profile['email'] ?? ''));
        if (! $sub || $email === '' || empty($profile['email_verified'])) {
            return $fail('Email Google Anda belum terverifikasi.');
        }

        $guest = Guest::where('google_id', $sub)->first() ?? Guest::where('email', $email)->first();

        if (! $guest) {
            $guest = Guest::create(['name' => $profile['name'] ?? $email, 'email' => $email, 'google_id' => $sub]);
            Audit::log('guest.registered', "Penyewa baru mendaftar via Google: {$guest->name} ({$guest->email})", $guest, [], ['type' => 'guest', 'name' => $guest->name]);
        } elseif (! $guest->google_id) {
            $guest->update(['google_id' => $sub]);
        }

        // Booking lama dengan email yang sama ditautkan hanya untuk akun murni-Google:
        // akun berpassword bisa saja didaftarkan orang lain dengan email ini sebelum diverifikasi.
        if (! $guest->password) {
            Booking::whereNull('guest_id')->where('guest_email', $email)->update(['guest_id' => $guest->id]);
        }

        Auth::guard('guest')->login($guest, true);
        $request->session()->regenerate();

        return redirect()->intended(route('guest.bookings'));
    }

    private function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }
}
