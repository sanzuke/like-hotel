<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Owner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LongTermPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sets_monthly_and_yearly_price_and_detail_shows_them(): void
    {
        $o = Owner::create(['name' => 'O', 'phone' => '081', 'email' => 'o@x.com', 'password' => 'password123', 'status' => 'aktif']);
        $u = Apartment::create(['owner_id' => $o->id, 'owner_name' => 'O', 'name' => 'Unit Bulanan', 'location' => 'Bandung', 'type' => 'Studio', 'price_per_night' => 300000, 'status' => 'aktif']);

        $this->get(route('apartments.show', $u))->assertOk()->assertDontSee('class="pd-longterm"', false);

        $this->actingAs($o, 'owner')->put(route('owner.apartments.update', $u), [
            'name' => 'Unit Bulanan', 'location' => 'Bandung', 'category' => 'apartment', 'type' => 'Studio',
            'price_per_night' => 300000, 'price_per_month' => 4500000, 'price_per_year' => 48000000, 'status' => 'aktif',
        ])->assertSessionHasNoErrors();

        $u->refresh();
        $this->assertSame(4500000, (int) $u->price_per_month);
        $this->assertSame(48000000, (int) $u->price_per_year);

        $this->get(route('apartments.show', $u))->assertOk()
            ->assertSee('Sewa jangka panjang')->assertSee('Per bulan')->assertSee('Per tahun')->assertSee('Rp4.500.000');

        // Dikosongkan lagi: harga jangka panjang hilang dari detail.
        $this->put(route('owner.apartments.update', $u), [
            'name' => 'Unit Bulanan', 'location' => 'Bandung', 'category' => 'apartment', 'type' => 'Studio',
            'price_per_night' => 300000, 'price_per_month' => '', 'price_per_year' => '', 'status' => 'aktif',
        ])->assertSessionHasNoErrors();
        $this->assertNull($u->fresh()->price_per_month);
        $this->get(route('apartments.show', $u))->assertDontSee('class="pd-longterm"', false);

        $this->put(route('owner.apartments.update', $u), [
            'name' => 'Unit Bulanan', 'location' => 'Bandung', 'category' => 'apartment', 'type' => 'Studio',
            'price_per_night' => 300000, 'price_per_month' => -1, 'status' => 'aktif',
        ])->assertSessionHasErrors('price_per_month');
    }

    public function test_home_shows_corporate_and_group_offer_cta(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('Harga Spesial Corporate')->assertSee('Booking Group')->assertSee('wa.me/6289654231118', false);
    }
}
