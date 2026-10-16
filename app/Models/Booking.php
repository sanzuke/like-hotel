<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Booking extends Model
{
    protected $fillable = [
        'booking_code', 'invoice_number', 'access_token', 'apartment_id', 'owner_id',
        'guest_name', 'guest_phone', 'guest_email', 'checkin', 'checkout',
        'total_price', 'commission_amount', 'owner_amount', 'promo_code', 'discount_amount',
        'snap_token', 'payment_status', 'booking_status', 'paid_at', 'expires_at', 'midtrans_response',
    ];

    protected $hidden = ['access_token'];

    protected function casts(): array
    {
        return [
            'checkin' => 'date',
            'checkout' => 'date',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'total_price' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'owner_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function nights(): int
    {
        return max(1, $this->checkin->diffInDays($this->checkout));
    }

    public function isPaid(): bool
    {
        return $this->booking_status === 'dibayar';
    }

    public function isCancelled(): bool
    {
        return $this->booking_status === 'batal';
    }

    /** Booking yang masih memblokir kalender. */
    public function scopeBlocking($query)
    {
        return $query->where('booking_status', '!=', 'batal');
    }

    /** Bentrok dengan rentang [checkin, checkout). */
    public function scopeOverlapping($query, $checkin, $checkout)
    {
        return $query->where('checkin', '<', $checkout)->where('checkout', '>', $checkin);
    }

    public function markPaid(string $paymentStatus): void
    {
        $this->update([
            'payment_status' => $paymentStatus,
            'booking_status' => 'dibayar',
            'paid_at' => $this->paid_at ?? Carbon::now(),
            'expires_at' => null,
        ]);
    }

    public function markCancelled(string $paymentStatus): void
    {
        $this->update(['payment_status' => $paymentStatus, 'booking_status' => 'batal']);
    }
}
