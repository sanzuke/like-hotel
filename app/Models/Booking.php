<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Booking extends BaseModel
{
    public const STATUS_NEW = 'baru';

    public const STATUS_PAID = 'dibayar';

    public const STATUS_CANCELLED = 'batal';

    protected $hidden = ['snap_token', 'midtrans_response'];

    protected function casts(): array
    {
        return [
            'checkin' => 'date:Y-m-d',
            'checkout' => 'date:Y-m-d',
            'total_price' => 'float',
            'commission_amount' => 'float',
            'owner_amount' => 'float',
            'discount_amount' => 'float',
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

    public function getRouteKeyName(): string
    {
        return 'access_token';
    }

    public function isPaid(): bool
    {
        return $this->booking_status === self::STATUS_PAID;
    }

    public function nights(): int
    {
        return max(1, (int) $this->checkin->diffInDays($this->checkout));
    }

    public function subtotal(): float
    {
        return $this->total_price + $this->discount_amount;
    }

    /** Booking yang masih memblokir kalender: tidak batal dan (dibayar atau belum kedaluwarsa). */
    public function scopeBlocking($q)
    {
        $cutoff = Carbon::now()->subMinutes(config('likehotel.pending_expiry_minutes'));

        return $q->where('booking_status', '!=', self::STATUS_CANCELLED)
            ->where(fn ($w) => $w->where('booking_status', '!=', self::STATUS_NEW)->orWhere('created_at', '>=', $cutoff));
    }

    public function scopeOverlapping($q, $checkin, $checkout)
    {
        return $q->where('checkin', '<', $checkout)->where('checkout', '>', $checkin);
    }

    public function scopePaid($q)
    {
        return $q->where('booking_status', self::STATUS_PAID);
    }

    /** Warna status pembayaran untuk tabel admin/owner. */
    public function statusColor(): string
    {
        $s = strtolower($this->payment_status);

        return match (true) {
            str_contains($s, 'lunas') => '#087a35',
            str_contains($s, 'pending'), str_contains($s, 'menunggu') => '#b45309',
            str_contains($s, 'batal'), str_contains($s, 'gagal') => '#991b1b',
            default => '#6b7280',
        };
    }

    public function invoiceUrl(): string
    {
        return route('invoice.show', $this);
    }
}
