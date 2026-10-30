<?php

namespace App\Models;

use App\Notifications\VerifyGuestEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;

/** Akun penyewa: wajib untuk membuat booking, menyimpan riwayat booking dan invoice. */
class Guest extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'phone', 'email', 'google_id', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'email_verified_at' => 'datetime'];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function markEmailAsVerified(): void
    {
        $this->forceFill(['email_verified_at' => now()])->save();
    }

    /** Tautan bertanda tangan; hash terikat ke email saat ini sehingga basi bila email berubah. */
    public function verificationUrl(): string
    {
        return URL::temporarySignedRoute('guest.verify', now()->addHours(48), [
            'id' => $this->getKey(),
            'hash' => sha1(strtolower($this->email)),
        ]);
    }

    public function sendEmailVerification(): void
    {
        $this->notify(new VerifyGuestEmail);
    }
}
