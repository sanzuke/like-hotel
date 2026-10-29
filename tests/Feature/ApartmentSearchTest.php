<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApartmentSearchTest extends TestCase
{
    use RefreshDatabase;

    private function apt(string $name, string $location, string $type, int $price, string $status = 'aktif'): Apartment
    {
        return Apartment::create([
            'owner_name' => 'Like Hotel', 'name' => $name, 'location' => $location,
            'type' => $type, 'price_per_night' => $price, 'status' => $status,
        ]);
    }

    public function test_filters_by_keyword_type_price_and_sort(): void
    {
        $this->apt('Easton Park', 'Jatinangor', 'Studio', 350000);
        $this->apt('Taman Melati', 'Yogyakarta', '1 Bedroom', 275000);
        $this->apt('Grand Kamala', 'Bekasi', '2 Bedroom', 450000);
        $this->apt('Tersembunyi', 'Jatinangor', 'Studio', 100000, 'nonaktif');

        $this->get(route('apartments.index', ['q' => 'jatinangor']))->assertOk()
            ->assertSee('Easton Park')->assertDontSee('Taman Melati')->assertDontSee('Tersembunyi');
        $this->get(route('apartments.index', ['type' => '2 Bedroom']))->assertSee('Grand Kamala')->assertDontSee('Easton Park');
        $this->get(route('apartments.index', ['max_price' => 300000]))->assertSee('Taman Melati')->assertDontSee('Grand Kamala');
        $this->get(route('apartments.index', ['q' => '%']))->assertOk()->assertDontSee('Easton Park'); // wildcard tidak lolos

        $html = $this->get(route('apartments.index', ['sort' => 'termurah']))->getContent();
        $this->assertLessThan(strpos($html, 'Easton Park'), strpos($html, 'Taman Melati'));
        $this->assertLessThan(strpos($html, 'Grand Kamala'), strpos($html, 'Easton Park'));
    }

    public function test_date_search_excludes_booked_units_but_allows_back_to_back(): void
    {
        $busy = $this->apt('Unit Sibuk', 'Bandung', 'Studio', 300000);
        $this->apt('Unit Kosong', 'Bandung', 'Studio', 300000);
        $cancelled = $this->apt('Unit Batal', 'Bandung', 'Studio', 300000);

        $mk = fn (Apartment $a, string $status) => Booking::create([
            'booking_code' => 'LH'.$a->id, 'access_token' => str_repeat((string) $a->id, 40), 'apartment_id' => $a->id,
            'guest_name' => 'T', 'guest_phone' => '081234567890', 'guest_email' => 't@x.id',
            'checkin' => now()->addDays(10)->toDateString(), 'checkout' => now()->addDays(12)->toDateString(),
            'total_price' => 600000, 'booking_status' => $status,
        ]);
        $mk($busy, 'dibayar');
        $mk($cancelled, 'batal');

        $overlap = ['checkin' => now()->addDays(11)->toDateString(), 'checkout' => now()->addDays(13)->toDateString()];
        $this->get(route('apartments.index', $overlap))->assertOk()
            ->assertDontSee('Unit Sibuk')->assertSee('Unit Kosong')->assertSee('Unit Batal');

        // Check-in tepat di hari check-out booking lain masih tersedia.
        $backToBack = ['checkin' => now()->addDays(12)->toDateString(), 'checkout' => now()->addDays(13)->toDateString()];
        $this->get(route('apartments.index', $backToBack))->assertSee('Unit Sibuk');
    }

    public function test_invalid_dates_are_rejected_and_dates_prefill_booking(): void
    {
        $a = $this->apt('Unit', 'Bandung', 'Studio', 300000);
        $this->loginGuest();

        $this->get(route('apartments.index', ['checkin' => now()->addDays(3)->toDateString(), 'checkout' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('checkout');
        $this->get(route('apartments.index', ['checkin' => now()->addDays(3)->toDateString()]))->assertSessionHasErrors('checkout');

        $this->flushSession();
        $in = now()->addDays(4)->toDateString();
        $out = now()->addDays(6)->toDateString();
        $html = $this->get(route('booking.create', ['apartment' => $a, 'checkin' => $in, 'checkout' => $out]))->getContent();
        $this->assertStringContainsString('value="'.$in.'"', $html);
        $this->assertStringContainsString('value="'.$out.'"', $html);
    }
}
