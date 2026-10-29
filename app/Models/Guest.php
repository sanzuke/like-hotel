<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/** Akun penyewa: wajib untuk membuat booking, menyimpan riwayat booking dan invoice. */
class Guest extends Authenticatable
{
    protected $fillable = ['name', 'phone', 'email', 'google_id', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
