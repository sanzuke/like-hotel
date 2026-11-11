<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Owner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OwnerApartmentDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function owner(string $email = 'a@example.com'): Owner
    {
        return Owner::create(['name' => 'Owner A', 'phone' => '081', 'email' => $email, 'password' => 'password123', 'status' => 'aktif']);
    }

    private function apartment(Owner $owner, array $o = []): Apartment
    {
        return Apartment::create($o + [
            'owner_id' => $owner->id, 'owner_name' => $owner->name, 'name' => 'Unit Uji', 'location' => 'Bandung',
            'type' => 'Studio', 'price_per_night' => 300000, 'status' => 'nonaktif',
        ]);
    }

    public function test_guest_cannot_delete_and_owner_sees_delete_button(): void
    {
        $owner = $this->owner();
        $a = $this->apartment($owner);

        $this->delete(route('owner.apartments.destroy', $a))->assertRedirect(route('owner.login'));
        $this->assertDatabaseHas('apartments', ['id' => $a->id]);

        $this->actingAs($owner, 'owner')->get(route('owner.apartments.index'))
            ->assertOk()->assertSee(route('owner.apartments.destroy', $a))->assertSee('Hapus');
    }

    public function test_owner_deletes_own_unit_with_photos_and_it_is_audited(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('apartments/cover.jpg', 'x');

        $owner = $this->owner();
        $a = $this->apartment($owner, ['image' => 'cover.jpg']);
        $a->images()->create(['image' => 'cover.jpg']);

        $this->actingAs($owner, 'owner')->delete(route('owner.apartments.destroy', $a))
            ->assertRedirect(route('owner.apartments.index'))->assertSessionHas('success');

        $this->assertDatabaseMissing('apartments', ['id' => $a->id]);
        Storage::disk('public')->assertMissing('apartments/cover.jpg');
        $this->assertDatabaseHas('audit_logs', ['action' => 'apartment.deleted', 'actor_type' => 'owner', 'subject_id' => $a->id]);
    }

    public function test_owner_cannot_delete_another_owners_unit(): void
    {
        $other = $this->apartment($this->owner('b@example.com'));

        $this->actingAs($this->owner(), 'owner')->delete(route('owner.apartments.destroy', $other))->assertNotFound();

        $this->assertDatabaseHas('apartments', ['id' => $other->id]);
    }

    public function test_unit_with_booking_history_is_not_deleted(): void
    {
        $owner = $this->owner();
        $a = $this->apartment($owner);

        Booking::create([
            'booking_code' => 'LH-UJI-2', 'invoice_number' => 'INV-UJI-2', 'access_token' => Str::random(40),
            'apartment_id' => $a->id, 'owner_id' => $owner->id, 'guest_name' => 'Tamu', 'guest_phone' => '08123456789',
            'guest_email' => 'tamu@example.com', 'checkin' => '2026-12-01', 'checkout' => '2026-12-03', 'total_price' => 600000,
            'commission_amount' => 60000, 'owner_amount' => 540000, 'discount_amount' => 0,
            'payment_status' => 'paid', 'booking_status' => 'confirmed',
        ]);

        $this->actingAs($owner, 'owner')->from(route('owner.apartments.index'))
            ->delete(route('owner.apartments.destroy', $a))
            ->assertRedirect(route('owner.apartments.index'))->assertSessionHas('error');

        $this->assertDatabaseHas('apartments', ['id' => $a->id]);
    }
}
