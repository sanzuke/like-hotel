<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Promo;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    /** Tanggal (Y-m-d) yang tidak bisa dipesan untuk sebuah apartemen. */
    public function bookedDates(Apartment $apartment): array
    {
        $dates = [];

        $ranges = Booking::where('apartment_id', $apartment->id)->blocking()->get(['checkin', 'checkout']);

        foreach ($ranges as $r) {
            for ($d = $r->checkin->copy(); $d < $r->checkout; $d->addDay()) {
                $dates[] = $d->toDateString();
            }
        }

        return array_values(array_unique($dates));
    }

    public function isAvailable(Apartment $apartment, $checkin, $checkout): bool
    {
        return ! Booking::where('apartment_id', $apartment->id)->blocking()->overlapping($checkin, $checkout)->exists();
    }

    /** @return array{0: Promo, 1: float} */
    public function resolvePromo(string $code, float $subtotal): array
    {
        $promo = Promo::where('code', strtoupper(trim($code)))->first();

        if (! $promo || ! $promo->isUsable()) {
            throw new BookingException('Kode promo tidak valid atau sudah tidak berlaku.');
        }

        return [$promo, $promo->discountFor($subtotal)];
    }

    /**
     * Buat booking. Pengecekan bentrok dan insert berada dalam satu transaksi
     * dengan row-lock pada apartemen agar dua booking serentak tidak lolos bersamaan.
     */
    public function create(Apartment $apartment, array $data): Booking
    {
        $checkin = Carbon::parse($data['checkin'])->startOfDay();
        $checkout = Carbon::parse($data['checkout'])->startOfDay();

        if ($checkout->lte($checkin)) {
            throw new BookingException('Tanggal check-out harus setelah tanggal check-in.');
        }

        return DB::transaction(function () use ($apartment, $data, $checkin, $checkout) {
            $locked = Apartment::whereKey($apartment->id)->lockForUpdate()->first();

            if ($locked->status !== 'aktif') {
                throw new BookingException('Unit ini sedang tidak tersedia untuk dibooking.');
            }

            if (! $this->isAvailable($locked, $checkin->toDateString(), $checkout->toDateString())) {
                throw new BookingException('Mohon maaf, unit ini sudah dipesan pada periode yang Anda pilih. Silakan pilih tanggal menginap lainnya.');
            }

            $nights = (int) $checkin->diffInDays($checkout);
            $subtotal = $nights * $locked->price_per_night;
            $discount = 0.0;
            $promoCode = null;

            if (! empty($data['promo_code'])) {
                [$promo, $discount] = $this->resolvePromo($data['promo_code'], $subtotal);
                $promoCode = $promo->code;
            }

            $total = $subtotal - $discount;
            $commission = round($total * config('likehotel.commission_rate'));

            // Kode berisi suffix acak agar dua booking pada detik yang sama tidak bentrok.
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $code = 'LH'.now()->format('YmdHis').strtoupper(Str::random(4));

                try {
                    return Booking::create([
                        'booking_code' => $code,
                        'invoice_number' => 'INV-'.$code,
                        'access_token' => Str::random(40),
                        'apartment_id' => $locked->id,
                        'owner_id' => $locked->owner_id,
                        'guest_name' => $data['guest_name'],
                        'guest_phone' => $data['guest_phone'],
                        'guest_email' => $data['guest_email'],
                        'checkin' => $checkin->toDateString(),
                        'checkout' => $checkout->toDateString(),
                        'total_price' => $total,
                        'commission_amount' => $commission,
                        'owner_amount' => $total - $commission,
                        'promo_code' => $promoCode,
                        'discount_amount' => $discount,
                        'payment_status' => 'menunggu pembayaran',
                        'booking_status' => Booking::STATUS_NEW,
                    ]);
                } catch (UniqueConstraintViolationException $e) {
                    // kode bentrok, coba lagi dengan suffix baru
                }
            }

            throw new BookingException('Gagal membuat kode booking. Silakan coba lagi.');
        });
    }

    /** Batalkan booking belum dibayar yang melewati batas waktu. Mengembalikan jumlah yang dibatalkan. */
    public function expirePending(): int
    {
        $cutoff = now()->subMinutes(config('likehotel.pending_expiry_minutes'));

        return Booking::where('booking_status', Booking::STATUS_NEW)
            ->where('created_at', '<', $cutoff)
            ->update(['booking_status' => Booking::STATUS_CANCELLED, 'payment_status' => 'kedaluwarsa']);
    }
}
