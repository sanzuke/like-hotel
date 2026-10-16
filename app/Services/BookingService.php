<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Promo;
use App\Support\Audit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /** Tanggal (Y-m-d) yang sudah terisi untuk kalender booking. */
    public function bookedDates(Apartment $apartment): array
    {
        $dates = [];

        $apartment->bookings()->blocking()->get(['checkin', 'checkout'])->each(function ($b) use (&$dates) {
            for ($d = $b->checkin->copy(); $d->lt($b->checkout); $d->addDay()) {
                $dates[] = $d->toDateString();
            }
        });

        return array_values(array_unique($dates));
    }

    /**
     * Buat booking. Cek bentrok dilakukan dalam transaksi dengan lock pada
     * baris apartment, sehingga dua request bersamaan tidak bisa lolos keduanya.
     */
    public function create(Apartment $apartment, array $data): Booking
    {
        $checkin = Carbon::parse($data['checkin'])->startOfDay();
        $checkout = Carbon::parse($data['checkout'])->startOfDay();

        return DB::transaction(function () use ($apartment, $data, $checkin, $checkout) {
            $locked = Apartment::whereKey($apartment->id)->lockForUpdate()->first();

            if ($locked->status !== 'aktif') {
                throw ValidationException::withMessages(['checkin' => 'Unit ini sedang tidak tersedia untuk dibooking.']);
            }

            $conflict = Booking::where('apartment_id', $locked->id)
                ->blocking()
                ->overlapping($checkin, $checkout)
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'checkin' => 'Mohon maaf, unit ini sudah dipesan pada periode yang Anda pilih. Silakan pilih tanggal lain.',
                ]);
            }

            $nights = max(1, $checkin->diffInDays($checkout));
            $subtotal = $nights * (float) $locked->price_per_night;
            $discount = 0.0;
            $promoCode = null;

            if (! empty($data['promo_code'])) {
                $promo = Promo::findValid($data['promo_code']);

                if (! $promo) {
                    throw ValidationException::withMessages(['promo_code' => 'Kode promo tidak valid atau sudah tidak berlaku.']);
                }

                $discount = $promo->discountFor($subtotal);
                $promoCode = $promo->code;
            }

            $total = $subtotal - $discount;
            $commission = round($total * config('likehotel.commission_rate'));
            $code = $this->uniqueCode();

            $booking = Booking::create([
                'booking_code' => $code,
                'invoice_number' => 'INV-'.$code,
                'access_token' => Str::random(40),
                'apartment_id' => $locked->id,
                'owner_id' => $locked->owner_id,
                'guest_name' => $data['guest_name'],
                'guest_phone' => $data['guest_phone'],
                'guest_email' => $data['guest_email'],
                'checkin' => $checkin,
                'checkout' => $checkout,
                'total_price' => $total,
                'commission_amount' => $commission,
                'owner_amount' => $total - $commission,
                'promo_code' => $promoCode,
                'discount_amount' => $discount,
                'payment_status' => 'menunggu pembayaran',
                'booking_status' => 'baru',
                'expires_at' => now()->addHours(config('likehotel.pending_expiry_hours')),
            ]);

            Audit::log('booking.created', "Booking {$booking->booking_code} dibuat oleh tamu {$booking->guest_name}", $booking, ['total' => $total, 'promo' => $promoCode], ['type' => 'guest', 'name' => $booking->guest_name]);

            return $booking;
        });
    }

    /** Batalkan booking pending yang melewati batas waktu pembayaran. */
    public function expirePending(): int
    {
        $expired = Booking::where('booking_status', 'baru')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());

        $codes = (clone $expired)->pluck('booking_code');

        if ($codes->isEmpty()) {
            return 0;
        }

        $count = $expired->update(['booking_status' => 'batal', 'payment_status' => 'kedaluwarsa']);
        Audit::system('booking.expired', "{$count} booking kedaluwarsa dibatalkan otomatis", null, ['codes' => $codes->all()]);

        return $count;
    }

    private function uniqueCode(): string
    {
        do {
            $code = 'LH'.now()->format('ymd').strtoupper(Str::random(6));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}
