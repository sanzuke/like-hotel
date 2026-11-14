<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Owner;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OwnerManageUnitTest extends TestCase
{
    use RefreshDatabase;

    private function owner(string $email): Owner
    {
        return Owner::create(['name' => 'O', 'phone' => '081', 'email' => $email, 'password' => 'password123', 'status' => 'aktif']);
    }

    private function unit(Owner $o, array $a = []): Apartment
    {
        return Apartment::create($a + ['owner_id' => $o->id, 'owner_name' => $o->name, 'name' => 'U', 'location' => 'Bandung', 'type' => 'Studio', 'price_per_night' => 300000, 'status' => 'aktif']);
    }

    public function test_owner_updates_price_and_cannot_touch_ads_or_others_units(): void
    {
        $a = $this->owner('a@x.com');
        $b = $this->owner('b@x.com');
        $u = $this->unit($a);

        $this->actingAs($a, 'owner')->put(route('owner.apartments.update', $u), [
            'name' => 'U', 'location' => 'Bandung', 'category' => 'apartment', 'type' => 'Studio',
            'price_per_night' => 450000, 'status' => 'maintenance', 'ad_priority' => 3,
        ])->assertRedirect();

        $u->refresh();
        $this->assertSame(450000, (int) $u->price_per_night);
        $this->assertSame('maintenance', $u->status);
        $this->assertSame(0, (int) $u->ad_priority);

        $this->actingAs($b, 'owner')->get(route('owner.apartments.edit', $u))->assertNotFound();
    }

    public function test_pending_unit_cannot_be_self_activated(): void
    {
        $a = $this->owner('a@x.com');
        $u = $this->unit($a, ['status' => 'nonaktif']);

        $this->actingAs($a, 'owner')->put(route('owner.apartments.update', $u), [
            'name' => 'U', 'location' => 'Bandung', 'category' => 'apartment', 'type' => 'Studio',
            'price_per_night' => 300000, 'status' => 'aktif',
        ])->assertRedirect();

        $this->assertSame('nonaktif', $u->fresh()->status);
    }

    public function test_blocked_dates_prevent_booking_and_search(): void
    {
        $a = $this->owner('a@x.com');
        $u = $this->unit($a);
        $in = now()->addDays(10)->toDateString();
        $out = now()->addDays(12)->toDateString();

        $this->actingAs($a, 'owner')->post(route('owner.apartments.blocks.store', $u), ['date_from' => $in, 'date_to' => $out])->assertSessionHasNoErrors();

        $this->assertNotContains($u->id, Apartment::active()->freeBetween($in, $out)->pluck('id')->all());

        $this->expectException(ValidationException::class);
        app(BookingService::class)->create($u, ['checkin' => $in, 'checkout' => $out, 'guest_name' => 'T', 'guest_phone' => '081', 'guest_email' => 't@x.com']);
    }

    public function test_cannot_block_dates_with_existing_booking(): void
    {
        $a = $this->owner('a@x.com');
        $u = $this->unit($a);
        $in = now()->addDays(5)->toDateString();
        $out = now()->addDays(7)->toDateString();
        app(BookingService::class)->create($u, ['checkin' => $in, 'checkout' => $out, 'guest_name' => 'T', 'guest_phone' => '081', 'guest_email' => 't@x.com']);

        $this->actingAs($a, 'owner')->post(route('owner.apartments.blocks.store', $u), ['date_from' => $in, 'date_to' => $out])->assertSessionHasErrors('date_from');
        $this->assertSame(0, $u->blocks()->count());
    }

    public function test_commission_is_15_percent_and_sponsored_units_rank_first(): void
    {
        $a = $this->owner('a@x.com');
        $plain = $this->unit($a, ['name' => 'Plain']);
        $ad = $this->unit($a, ['name' => 'Ad', 'ad_priority' => 2]);
        $this->unit($a, ['name' => 'Newest']);
        $expired = $this->unit($a, ['name' => 'Expired', 'ad_priority' => 3, 'ad_until' => now()->subDay()->toDateString()]);

        $b = app(BookingService::class)->create($plain, ['checkin' => now()->addDays(3)->toDateString(), 'checkout' => now()->addDays(5)->toDateString(), 'guest_name' => 'T', 'guest_phone' => '081', 'guest_email' => 't@x.com']);
        $this->assertSame(600000 * 0.15, (float) $b->commission_amount);

        $names = Apartment::active()->ranked()->pluck('name')->all();
        $this->assertSame('Ad', $names[0]);
        $this->assertSame(['Expired', 'Newest', 'Plain'], array_slice($names, 1));
    }
}
