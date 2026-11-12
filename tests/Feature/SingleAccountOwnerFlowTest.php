<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Guest;
use App\Models\Owner;
use App\Models\SaleRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur akun tunggal: daftar sebagai penyewa, aktifkan profil pemilik,
 * dan ajukan titip jual tanpa membuat akun kedua.
 */
class SingleAccountOwnerFlowTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return $extra + [
            'owner_name' => 'Pak Fahri', 'owner_phone' => '081234567890',
            'apartment_name' => 'Easton Park', 'location' => 'Jatinangor',
            'unit_type' => 'Studio', 'size' => '24 m2', 'expected_price' => 350000000,
        ];
    }

    public function test_guest_can_activate_owner_profile_and_open_dashboard(): void
    {
        $guest = $this->loginGuest();

        $this->get(route('account.index'))->assertOk()->assertSee('Mode Pemilik');
        $this->get(route('owner.activate'))->assertOk()->assertSee('Aktifkan Mode Pemilik');

        $this->post(route('owner.activate.store'), [
            'name' => 'Tamu Uji', 'phone' => '081234567890',
        ])->assertRedirect(route('owner.dashboard'))->assertSessionHas('success');

        $guest->refresh();
        $this->assertNotNull($guest->owner_id);
        $this->assertDatabaseHas('owners', [
            'id' => $guest->owner_id, 'email' => $guest->email, 'status' => 'aktif',
        ]);

        $this->get(route('owner.dashboard'))->assertOk()->assertSee('Dashboard Owner');
        $this->get(route('owner.apartments.index'))->assertOk()->assertSee('Tambah Unit');
    }

    public function test_owner_area_redirects_guest_without_profile_to_activation(): void
    {
        $this->loginGuest();

        $this->get(route('owner.dashboard'))->assertRedirect(route('owner.activate'));
        $this->post(route('owner.settlements.store'), [
            'bank_name' => 'BCA', 'bank_account_name' => 'X', 'bank_account_number' => '1', 'amount' => 1000,
        ])->assertRedirect(route('owner.activate'));
    }

    public function test_activating_owner_profile_is_idempotent(): void
    {
        $guest = $this->loginGuest();
        $owner = Owner::create(['name' => 'Lama', 'phone' => '081', 'email' => $guest->email, 'status' => 'aktif']);
        $guest->owner()->associate($owner);
        $guest->save();

        $this->get(route('owner.activate'))->assertRedirect(route('owner.dashboard'));
        $this->post(route('owner.activate.store'), ['name' => 'X', 'phone' => '081234567890'])
            ->assertRedirect(route('owner.dashboard'));
        $this->assertSame(1, Owner::count());
    }

    public function test_titip_jual_requires_login(): void
    {
        $this->get(route('sale-request.create'))->assertRedirect(route('guest.login'));
        $this->post(route('sale-request.store'), $this->payload())->assertRedirect(route('guest.login'));
        $this->assertSame(0, SaleRequest::count());
    }

    public function test_titip_jual_is_linked_to_account_and_only_visible_to_owner(): void
    {
        $guest = $this->loginGuest();

        $this->post(route('sale-request.store'), $this->payload())
            ->assertRedirect(route('account.sale-requests'))->assertSessionHas('success');

        $lead = SaleRequest::firstOrFail();
        $this->assertSame($guest->id, $lead->guest_id);

        $this->get(route('account.sale-requests'))->assertOk()->assertSee('Easton Park');

        // Akun lain tidak boleh melihat pengajuan ini.
        auth('guest')->logout();
        $other = Guest::create([
            'name' => 'Orang Lain', 'email' => 'lain@example.com', 'phone' => '0812', 'password' => 'password123',
        ]);
        $this->actingAs($other, 'guest')
            ->get(route('account.sale-requests'))
            ->assertOk()->assertDontSee('Easton Park');
    }

    public function test_apartment_category_filter(): void
    {
        Apartment::create(['owner_name' => 'LH', 'name' => 'Hotel Nusantara', 'location' => 'Bandung', 'category' => 'hotel', 'type' => 'Deluxe', 'price_per_night' => 200000, 'status' => 'aktif']);
        Apartment::create(['owner_name' => 'LH', 'name' => 'Apartemen Sejuk', 'location' => 'Bandung', 'category' => 'apartment', 'type' => 'Studio', 'price_per_night' => 200000, 'status' => 'aktif']);

        $this->get(route('apartments.index', ['category' => 'hotel']))
            ->assertOk()->assertSee('Hotel Nusantara')->assertDontSee('Apartemen Sejuk');

        $this->get(route('apartments.index'))
            ->assertOk()->assertSee('Hotel Nusantara')->assertSee('Apartemen Sejuk');
    }
}
