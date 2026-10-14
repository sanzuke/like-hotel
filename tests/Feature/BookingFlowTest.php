<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Promo;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFixtures;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use CreatesFixtures, RefreshDatabase;

    private function payload(array $o = []): array
    {
        return $o + [
            'guest_name' => 'Budi', 'guest_phone' => '081111', 'guest_email' => 'budi@example.com',
            'checkin' => now()->addDays(10)->toDateString(), 'checkout' => now()->addDays(12)->toDateString(),
        ];
    }

    public function test_booking_is_created_with_commission_and_secret_invoice_token(): void
    {
        $owner = $this->owner();
        $apt = $this->apartment($owner);

        $res = $this->post(route('booking.store', $apt), $this->payload());

        $b = Booking::firstOrFail();
        $res->assertRedirect(route('invoice.show', $b));
        $this->assertEquals(600000, $b->total_price);
        $this->assertEquals(60000, $b->commission_amount);
        $this->assertEquals(540000, $b->owner_amount);
        $this->assertEquals($owner->id, $b->owner_id);
        $this->assertSame(40, strlen($b->access_token));
        $this->assertMatchesRegularExpression('/^LH\d{14}[A-Z0-9]{4}$/', $b->booking_code);
    }

    public function test_overlapping_booking_is_rejected_but_back_to_back_is_allowed(): void
    {
        $apt = $this->apartment();
        $this->post(route('booking.store', $apt), $this->payload())->assertSessionHasNoErrors();

        // bentrok sebagian
        $this->post(route('booking.store', $apt), $this->payload([
            'checkin' => now()->addDays(11)->toDateString(), 'checkout' => now()->addDays(13)->toDateString(),
        ]))->assertSessionHasErrors('booking');

        // check-in tepat di hari check-out booking lain: boleh
        $this->post(route('booking.store', $apt), $this->payload([
            'checkin' => now()->addDays(12)->toDateString(), 'checkout' => now()->addDays(13)->toDateString(),
        ]))->assertSessionHasNoErrors();

        $this->assertSame(2, Booking::count());
    }

    public function test_past_dates_and_inverted_range_are_rejected(): void
    {
        $apt = $this->apartment();

        $this->post(route('booking.store', $apt), $this->payload(['checkin' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('checkin');
        $this->post(route('booking.store', $apt), $this->payload(['checkout' => now()->addDays(10)->toDateString()]))
            ->assertSessionHasErrors('checkout');
        $this->assertSame(0, Booking::count());
    }

    public function test_inactive_apartment_cannot_be_booked(): void
    {
        $apt = $this->apartment(null, ['status' => 'maintenance']);

        $this->get(route('booking.create', $apt))->assertNotFound();
        $this->post(route('booking.store', $apt), $this->payload())->assertSessionHasErrors('booking');
    }

    public function test_promo_percent_fixed_expired_and_invalid(): void
    {
        $apt = $this->apartment();
        Promo::create(['code' => 'HEMAT10', 'type' => 'percent', 'value' => 10, 'status' => 'aktif', 'expired_at' => now()->addDay()->toDateString()]);
        Promo::create(['code' => 'OLD', 'type' => 'percent', 'value' => 10, 'status' => 'aktif', 'expired_at' => now()->subDay()->toDateString()]);
        Promo::create(['code' => 'BIG', 'type' => 'fixed', 'value' => 9999999, 'status' => 'aktif']);

        $this->post(route('booking.store', $apt), $this->payload(['promo_code' => 'hemat10']));
        $b = Booking::latest('id')->first();
        $this->assertEquals(540000, $b->total_price);
        $this->assertEquals(60000, $b->discount_amount);
        $this->assertSame('HEMAT10', $b->promo_code);

        $this->post(route('booking.store', $apt), $this->payload(['promo_code' => 'OLD', 'checkin' => now()->addDays(20)->toDateString(), 'checkout' => now()->addDays(21)->toDateString()]))
            ->assertSessionHasErrors('booking');
        $this->post(route('booking.store', $apt), $this->payload(['promo_code' => 'NOPE', 'checkin' => now()->addDays(20)->toDateString(), 'checkout' => now()->addDays(21)->toDateString()]))
            ->assertSessionHasErrors('booking');

        // diskon nominal tidak boleh melebihi subtotal
        $this->post(route('booking.store', $apt), $this->payload(['promo_code' => 'BIG', 'checkin' => now()->addDays(30)->toDateString(), 'checkout' => now()->addDays(31)->toDateString()]));
        $this->assertEquals(0, Booking::latest('id')->first()->total_price);
    }

    public function test_unpaid_booking_expires_and_frees_the_calendar(): void
    {
        $apt = $this->apartment();
        $stale = $this->booking($apt, ['created_at' => now()->subHours(5)]);
        $fresh = $this->booking($apt, ['checkin' => now()->addDays(40)->toDateString(), 'checkout' => now()->addDays(41)->toDateString()]);
        $paid = $this->booking($apt, ['booking_status' => 'dibayar', 'created_at' => now()->subDays(9), 'checkin' => now()->addDays(50)->toDateString(), 'checkout' => now()->addDays(51)->toDateString()]);

        $svc = app(BookingService::class);
        $dates = $svc->bookedDates($apt);
        $this->assertNotContains($stale->checkin->toDateString(), $dates, 'booking kedaluwarsa tidak memblokir');
        $this->assertContains($fresh->checkin->toDateString(), $dates);
        $this->assertContains($paid->checkin->toDateString(), $dates, 'booking lunas lama tetap memblokir');

        $this->artisan('bookings:expire')->assertSuccessful();
        $this->assertSame('batal', $stale->fresh()->booking_status);
        $this->assertSame('baru', $fresh->fresh()->booking_status);
        $this->assertSame('dibayar', $paid->fresh()->booking_status);
    }

    public function test_listing_filters_by_availability_and_location(): void
    {
        $a = $this->apartment(null, ['name' => 'Alpha', 'location' => 'Bandung']);
        $b = $this->apartment(null, ['name' => 'Beta', 'location' => 'Jakarta']);
        $this->booking($a, ['checkin' => '2030-01-10', 'checkout' => '2030-01-12']);

        $this->get('/apartments?checkin=2030-01-11&checkout=2030-01-13')->assertDontSee('Alpha')->assertSee('Beta');
        $this->get('/apartments?q=bandung')->assertSee('Alpha')->assertDontSee('Beta');
    }

    public function test_invoice_requires_secret_token_and_lookup_requires_matching_contact(): void
    {
        $b = $this->booking($this->apartment());

        $this->get('/invoice/'.$b->booking_code)->assertNotFound();
        $this->get(route('invoice.show', $b))->assertOk()->assertSee($b->invoice_number);

        $this->post(route('booking.lookup'), ['code' => $b->booking_code, 'contact' => 'orang@lain.com'])->assertSessionHasErrors('code');
        $this->post(route('booking.lookup'), ['code' => $b->booking_code, 'contact' => 'TAMU@example.com'])->assertRedirect(route('invoice.show', $b));
        $this->post(route('booking.lookup'), ['code' => $b->invoice_number, 'contact' => '0821-2924-8953'])->assertRedirect(route('invoice.show', $b));
    }
}
