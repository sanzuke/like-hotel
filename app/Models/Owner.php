<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Owner extends Authenticatable
{
    public const SETTLEMENT_ACTIVE = ['pending', 'approved', 'paid'];

    protected $fillable = ['name', 'phone', 'email', 'google_id', 'password', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    /** Total hak owner dari booking yang sudah dibayar. */
    public function totalEarnings(): float
    {
        return (float) $this->bookings()->where('booking_status', 'dibayar')->sum('owner_amount');
    }

    /** Dana yang sudah diajukan / diproses / dibayar. */
    public function totalRequested(): float
    {
        return (float) $this->settlements()->whereIn('status', self::SETTLEMENT_ACTIVE)->sum('amount');
    }

    public function totalPaid(): float
    {
        return (float) $this->settlements()->where('status', 'paid')->sum('amount');
    }

    public function availableBalance(): float
    {
        return max(0, $this->totalEarnings() - $this->totalRequested());
    }
}
