<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Rules\Turnstile;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OwnerAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.owner-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'cf-turnstile-response' => [new Turnstile],
        ]);

        $throttleKey = Str::lower($data['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.',
            ]);
        }

        $owner = Owner::where('email', $data['email'])->first();

        if ($owner && $owner->status === 'pending' && $owner->password && password_verify($data['password'], $owner->password)) {
            throw ValidationException::withMessages(['email' => 'Akun Anda masih menunggu persetujuan admin.']);
        }

        if (! Auth::guard('owner')->attempt(['email' => $data['email'], 'password' => $data['password'], 'status' => 'aktif'])) {
            RateLimiter::hit($throttleKey, 60);
            Audit::log('owner.login_failed', "Login owner gagal untuk {$data['email']}", null, [], ['type' => 'guest', 'name' => $data['email']]);

            throw ValidationException::withMessages(['email' => 'Email atau password salah.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        Audit::log('owner.login', 'Owner login');

        return redirect()->intended(route('owner.dashboard'));
    }

    public function showRegister()
    {
        return view('auth.owner-register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:150|unique:owners,email',
            'password' => 'required|string|min:8|confirmed',
            'cf-turnstile-response' => [new Turnstile],
        ], ['email.unique' => 'Email sudah terdaftar. Silakan login.']);

        // Akun baru menunggu persetujuan admin (status default: pending).
        $owner = Owner::create(collect($data)->except('cf-turnstile-response')->all());
        Audit::log('owner.registered', "Owner baru mendaftar: {$owner->name} ({$owner->email})", $owner, [], ['type' => 'guest', 'name' => $owner->name]);

        return redirect()->route('owner.login')
            ->with('success', 'Pendaftaran berhasil. Akun Anda akan aktif setelah disetujui admin.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('owner')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('owner.login');
    }
}
