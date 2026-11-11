<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Apartment;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminApartmentDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create(['name' => 'A', 'username' => 'adm', 'password' => 'password123']);
    }

    private function apartment(array $o = []): Apartment
    {
        return Apartment::create($o + [
            'owner_name' => 'Like Hotel', 'name' => 'Unit Uji', 'location' => 'Bandung',
            'type' => 'Studio', 'price_per_night' => 300000, 'status' => 'aktif',
        ]);
    }

    public function test_guest_cannot_delete_and_list_shows_delete_button(): void
    {
        $a = $this->apartment();

        $this->delete(route('admin.apartments.destroy', $a))->assertRedirect(route('admin.login'));
        $this->assertDatabaseHas('apartments', ['id' => $a->id]);

        $this->actingAs($this->admin(), 'admin')->get(route('admin.apartments.index'))
            ->assertOk()->assertSee(route('admin.apartments.destroy', $a))->assertSee('Hapus');
    }

    public function test_admin_deletes_apartment_with_its_photos_and_it_is_audited(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('apartments/cover.jpg', 'x');
        Storage::disk('public')->put('apartments/lain.jpg', 'x');

        $a = $this->apartment(['image' => 'cover.jpg']);
        $a->images()->create(['image' => 'cover.jpg']);
        $a->images()->create(['image' => 'lain.jpg']);

        $this->actingAs($this->admin(), 'admin')->delete(route('admin.apartments.destroy', $a))
            ->assertRedirect(route('admin.apartments.index'))->assertSessionHas('success');

        $this->assertDatabaseMissing('apartments', ['id' => $a->id]);
        $this->assertDatabaseMissing('apartment_images', ['apartment_id' => $a->id]);
        Storage::disk('public')->assertMissing('apartments/cover.jpg');
        Storage::disk('public')->assertMissing('apartments/lain.jpg');
        $this->assertDatabaseHas('audit_logs', ['action' => 'apartment.deleted', 'subject_id' => $a->id]);
    }

    public function test_apartment_with_booking_history_is_not_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('apartments/cover.jpg', 'x');
        $a = $this->apartment(['image' => 'cover.jpg']);

        Booking::create([
            'booking_code' => 'LH-UJI-1', 'invoice_number' => 'INV-UJI-1', 'access_token' => Str::random(40),
            'apartment_id' => $a->id, 'guest_name' => 'Tamu', 'guest_phone' => '08123456789', 'guest_email' => 'tamu@example.com',
            'checkin' => '2026-12-01', 'checkout' => '2026-12-03', 'total_price' => 600000,
            'commission_amount' => 60000, 'owner_amount' => 540000, 'discount_amount' => 0,
            'payment_status' => 'paid', 'booking_status' => 'confirmed',
        ]);

        $this->actingAs($this->admin(), 'admin')->from(route('admin.apartments.index'))
            ->delete(route('admin.apartments.destroy', $a))
            ->assertRedirect(route('admin.apartments.index'))->assertSessionHas('error');

        $this->assertDatabaseHas('apartments', ['id' => $a->id]);
        Storage::disk('public')->assertExists('apartments/cover.jpg');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'apartment.deleted']);
    }
}
