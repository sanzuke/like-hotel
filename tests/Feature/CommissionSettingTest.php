<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Apartment;
use App\Services\BookingService;
use App\Support\Commission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionSettingTest extends TestCase
{
    use RefreshDatabase;

    private function book(Apartment $a, int $offset): \App\Models\Booking
    {
        return app(BookingService::class)->create($a, [
            'checkin' => now()->addDays($offset)->toDateString(), 'checkout' => now()->addDays($offset + 2)->toDateString(),
            'guest_name' => 'T', 'guest_phone' => '081', 'guest_email' => 't@x.com',
        ]);
    }

    public function test_admin_changes_rates_new_bookings_use_them_and_old_ones_stay(): void
    {
        $a = Apartment::create(['owner_name' => 'LH', 'name' => 'U', 'location' => 'B', 'type' => 'Studio', 'price_per_night' => 300000, 'status' => 'aktif']);

        $this->assertEquals(15.0, Commission::rentPercent());
        $old = $this->book($a, 3);
        $this->assertEquals(90000, $old->commission_amount);

        $admin = Admin::create(['name' => 'A', 'username' => 'adm', 'password' => 'password123']);
        $this->actingAs($admin, 'admin')->put(route('admin.commission.update'), ['rent_percent' => '12.5', 'sale_percent' => '4'])
            ->assertRedirect(route('admin.commission'))->assertSessionHas('success');

        $this->assertEquals(12.5, Commission::rentPercent());
        $this->assertEquals(4.0, Commission::salePercent());

        $new = $this->book($a, 10);
        $this->assertEquals(75000, $new->commission_amount);
        $this->assertEquals(525000, $new->owner_amount);
        $this->assertEquals(90000, $old->fresh()->commission_amount);

        $this->get(route('admin.commission'))->assertOk()->assertSee('12.5')->assertSee('name="sale_percent"', false);
        $this->assertDatabaseHas('audit_logs', ['action' => 'commission.settings_updated']);
    }

    public function test_invalid_rates_rejected_and_guests_cannot_change(): void
    {
        $this->put(route('admin.commission.update'), ['rent_percent' => 10, 'sale_percent' => 5])->assertRedirect(route('admin.login'));

        $admin = Admin::create(['name' => 'A', 'username' => 'adm', 'password' => 'password123']);
        $this->actingAs($admin, 'admin')->put(route('admin.commission.update'), ['rent_percent' => 101, 'sale_percent' => -1])
            ->assertSessionHasErrors(['rent_percent', 'sale_percent']);
        $this->assertEquals(15.0, Commission::rentPercent());
    }
}
