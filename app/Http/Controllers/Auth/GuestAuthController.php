<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Rules\Turnstile;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Daftar / login / logout akun penyewa. Akun langsung aktif (tanpa persetujuan admin). */
class GuestAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.guest-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'cf-turnstile-response' => [new Turnstile],
        ]);

        $throttleKey = 'guest|'.Str::lower($data['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.',
            ]);
        }

        if (! Auth::guard('guest')->attempt(['email' => $data['email'], 'password' => $data['password']], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages(['email' => 'Email atau password salah.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route('guest.bookings'));
    }

    public function showRegister()
    {
        return view('auth.guest-register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => ['required', 'string', 'max:50', 'regex:/^[0-9+\s\-]{8,}$/'],
            'email' => 'required|email|max:150|unique:guests,email',
            'password' => 'required|string|min:8|confirmed',
            'cf-turnstile-response' => [new Turnstile],
        ], [
            'email.unique' => 'Email sudah terdaftar. Silakan masuk.',
            'phone.regex' => 'Nomor WhatsApp tidak valid.',
        ]);

        $guest = Guest::create(collect($data)->except('cf-turnstile-response')->all());
        Audit::log('guest.registered', "Penyewa baru mendaftar: {$guest->name} ({$guest->email})", $guest, [], ['type' => 'guest', 'name' => $guest->name]);

        Auth::guard('guest')->login($guest);
        $request->session()->regenerate();

        try {
            $guest->sendEmailVerification();
        } catch (Throwable $e) {
            report($e); // gagal kirim email tidak boleh menggagalkan pendaftaran
        }

        return redirect()->intended(route('guest.bookings'))
            ->with('success', 'Akun dibuat. Kami mengirim email verifikasi untuk menautkan booking lama Anda.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('guest')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
