<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Owner;
use App\Support\Geo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NearbySearchTest extends TestCase
{
    use RefreshDatabase;

    private function unit(string $name, ?float $lat, ?float $lng, array $o = []): Apartment
    {
        return Apartment::create($o + ['owner_name' => 'LH', 'name' => $name, 'location' => 'Bandung', 'type' => 'Studio', 'price_per_night' => 300000, 'status' => 'aktif', 'lat' => $lat, 'lng' => $lng]);
    }

    public function test_haversine_distance_is_reasonable(): void
    {
        // ITB ke Jatinangor kira-kira 19-21 km.
        $km = Geo::distanceKm(-6.8915, 107.6107, -6.9275, 107.7710);
        $this->assertGreaterThan(16, $km);
        $this->assertLessThan(20, $km);
        $this->assertSame('850 m', Geo::label(0.85));
        $this->assertSame('2,3 km', Geo::label(2.3));
    }

    public function test_user_location_sorts_nearest_first_with_sponsored_on_top_and_no_coords_last(): void
    {
        $far = $this->unit('Jauh', -6.9275, 107.7710);
        $near = $this->unit('Dekat', -6.8920, 107.6110);
        $none = $this->unit('TanpaKoordinat', null, null);
        $ad = $this->unit('Beriklan', -6.9275, 107.7710, ['ad_priority' => 1]);

        $res = $this->get(route('apartments.index', ['lat' => -6.8915, 'lng' => 107.6107]));
        $res->assertOk();
        $names = $res->viewData('apartments')->pluck('name')->all();

        $this->assertSame(['Beriklan', 'Dekat', 'Jauh', 'TanpaKoordinat'], $names);
        $res->assertSee('dari lokasi Anda');
    }

    public function test_landmark_filter_and_validation(): void
    {
        $this->unit('Dekat Jatinangor', -6.9270, 107.7700);
        $this->unit('Dekat ITB', -6.8920, 107.6110);

        $names = $this->get(route('apartments.index', ['near' => 'jatinangor']))->viewData('apartments')->pluck('name')->all();
        $this->assertSame(['Dekat Jatinangor', 'Dekat ITB'], $names);

        $this->get(route('apartments.index', ['near' => 'tidak-ada']))->assertSessionHasErrors('near');
        $this->get(route('apartments.index', ['lat' => -6.9]))->assertSessionHasErrors('lng');
    }

    public function test_owner_saves_coordinates_and_both_are_required_together(): void
    {
        $o = Owner::create(['name' => 'O', 'phone' => '081', 'email' => 'o@x.com', 'password' => 'password123', 'status' => 'aktif']);
        $u = $this->unit('U', null, null, ['owner_id' => $o->id]);
        $base = ['name' => 'U', 'location' => 'Bandung', 'category' => 'apartment', 'type' => 'Studio', 'price_per_night' => 300000, 'status' => 'aktif'];

        $this->actingAs($o, 'owner')->put(route('owner.apartments.update', $u), $base + ['lat' => '-6.9100000', 'lng' => '107.6500000'])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(-6.91, $u->fresh()->lat, 0.0001);

        $this->put(route('owner.apartments.update', $u), $base + ['lat' => '-6.9'])->assertSessionHasErrors('lng');
    }

    public function test_map_picker_renders_on_owner_pages(): void
    {
        $o = Owner::create(['name' => 'O', 'phone' => '081', 'email' => 'o2@x.com', 'password' => 'password123', 'status' => 'aktif']);
        $u = $this->unit('U', -6.91, 107.65, ['owner_id' => $o->id]);

        $this->actingAs($o, 'owner')->get(route('owner.apartments.edit', $u))->assertOk()->assertSee('name="lat"', false)->assertSee('value="-6.91"', false);
        $this->get(route('owner.apartments.index'))->assertOk()->assertSee('leaflet.js', false);
    }
}
