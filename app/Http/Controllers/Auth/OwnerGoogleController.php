<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Daftar / login owner lewat Google (OAuth 2.0 authorization code).
 * Akun baru tetap berstatus "pending" sampai disetujui admin, sama seperti pendaftaran biasa.
 */
class OwnerGoogleController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->configured()) {
            return redirect()->route('owner.login')->withErrors(['email' => 'Login Google belum diaktifkan.']);
        }

        $state = Str::random(40);
        $request->session()->put('owner_google_state', $state);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('owner.google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]));
    }

    public function callback(Request $request): RedirectResponse
    {
        $fail = fn (string $msg) => redirect()->route('owner.login')->withErrors(['email' => $msg]);

        $state = $request->session()->pull('owner_google_state');
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
                'redirect_uri' => route('owner.google.callback'),
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

        $owner = Owner::where('google_id', $sub)->first() ?? Owner::where('email', $email)->first();

        if (! $owner) {
            $owner = Owner::create([
                'name' => $profile['name'] ?? $email,
                'email' => $email,
                'google_id' => $sub,
            ]);
            Audit::log('owner.registered', "Owner baru mendaftar via Google: {$owner->name} ({$owner->email})", $owner, [], ['type' => 'guest', 'name' => $owner->name]);

            return redirect()->route('owner.login')
                ->with('success', 'Pendaftaran berhasil. Akun Anda akan aktif setelah disetujui admin.');
        }

        if (! $owner->google_id) {
            $owner->update(['google_id' => $sub]);
        }

        if ($owner->status === 'pending') {
            return $fail('Akun Anda masih menunggu persetujuan admin.');
        }
        if ($owner->status !== 'aktif') {
            return $fail('Akun Anda tidak aktif. Hubungi admin.');
        }

        Auth::guard('owner')->login($owner);
        $request->session()->regenerate();
        Audit::log('owner.login', 'Owner login via Google');

        return redirect()->intended(route('owner.dashboard'));
    }

    private function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }
}
