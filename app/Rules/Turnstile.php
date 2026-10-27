<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Verifikasi token Cloudflare Turnstile. Bila TURNSTILE_SECRET_KEY kosong (mis. lokal/tes) pemeriksaan dilewati.
 */
class Turnstile implements ValidationRule
{
    /** Tetap dijalankan walau token tidak dikirim sama sekali. */
    public bool $implicit = true;

    public static function enabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::enabled()) {
            return;
        }

        $message = 'Verifikasi keamanan gagal. Silakan centang kotak verifikasi dan coba lagi.';

        if (! is_string($value) || $value === '') {
            $fail($message);

            return;
        }

        try {
            $res = Http::asForm()->timeout(8)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);
        } catch (Throwable) {
            $fail('Layanan verifikasi tidak dapat dihubungi. Silakan coba lagi.');

            return;
        }

        if (! $res->ok() || ! $res->json('success')) {
            $fail($message);
        }
    }
}
