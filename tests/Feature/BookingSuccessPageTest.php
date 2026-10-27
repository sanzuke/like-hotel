<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Owner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSuccessPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(): Booking
    {
        $owner = Owner::create(['name' => 'O', 'phone' => '0812', 'email' => 'o@x.com', 'password' => 'password123', 'status' => 'aktif']);
        $apt = Apartment::create([
            'owner_id' => $owner->id, 'owner_name' => 'O', 'name' => 'Easton', 'location' => 'Jatinangor', 'type' => 'Studio',
            'price_per_night' => 300000, 'facilities' => 'AC', 'status' => 'aktif',
        ]);
        $this->post(route('booking.store', $apt), [
            'guest_name' => 'Tamu', 'guest_phone' => '081234567890', 'guest_email' => 't@x.com',
            'checkin' => '2026-10-27', 'checkout' => '2026-10-31',
        ])->assertRedirect();

        return Booking::firstOrFail();
    }

    public function test_pending_booking_shows_countdown_total_dates_and_payment_cta(): void
    {
        $b = $this->makeBooking();
        $html = $this->get(route('booking.notify', $b->access_token))->assertOk()->getContent();

        $this->assertStringContainsString('Selesaikan Pembayaran Anda', $html);
        $this->assertStringContainsString('data-expire="', $html);
        $this->assertStringContainsString($b->booking_code, $html);
        $this->assertStringContainsString('Selasa, 27 Okt 2026', $html);
        $this->assertStringContainsString('Sabtu, 31 Okt 2026', $html);
        $this->assertStringContainsString('Rp1.200.000', $html);
        $this->assertStringContainsString(route('payment.show', $b->access_token), $html);
        $this->assertStringNotContainsString('Kirim ke Admin', $html);
        $this->assertStringNotContainsString('Kirim Notifikasi WhatsApp', $html);
    }

    public function test_paid_booking_shows_thanks_and_no_payment_cta(): void
    {
        $b = $this->makeBooking();
        $b->update(['payment_status' => 'lunas', 'booking_status' => 'dibayar', 'paid_at' => now()]);
        $html = $this->get(route('booking.notify', $b->access_token))->assertOk()->getContent();

        $this->assertStringContainsString('Pembayaran Lunas', $html);
        $this->assertStringNotContainsString('data-expire=', $html);
        $this->assertStringNotContainsString('Lanjut ke Pembayaran Online', $html);
    }
}
