<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\Turnstile;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.admin-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'cf-turnstile-response' => [new Turnstile],
        ]);

        $throttleKey = Str::lower($data['login']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'login' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.',
            ]);
        }

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::guard('admin')->attempt([$field => $data['login'], 'password' => $data['password'], 'status' => 'aktif'])) {
            RateLimiter::hit($throttleKey, 60);
            Audit::log('admin.login_failed', "Login admin gagal untuk \"{$data['login']}\"", null, [], ['type' => 'guest', 'name' => $data['login']]);

            throw ValidationException::withMessages(['login' => 'Username/email atau password salah.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        Auth::guard('admin')->user()->forceFill(['last_login_at' => now()])->save();
        Audit::log('admin.login', 'Admin login');

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
