<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GuestAccountTest extends TestCase
{
    use RefreshDatabase;

    private function apartment(): Apartment
    {
        return Apartment::create(['owner_name' => 'LH', 'name' => 'Unit Uji', 'location' => 'Bandung', 'type' => 'Studio', 'price_per_night' => 200000, 'status' => 'aktif']);
    }

    private function bookingPayload(): array
    {
        return [
            'guest_name' => 'Tamu Uji', 'guest_phone' => '081234567890', 'guest_email' => 'tamu@example.com',
            'checkin' => now()->addDays(3)->toDateString(), 'checkout' => now()->addDays(5)->toDateString(),
        ];
    }

    public function test_booking_requires_login_and_redirects_to_guest_login(): void
    {
        $a = $this->apartment();

        $this->get(route('booking.create', $a))->assertRedirect(route('guest.login'));
        $this->post(route('booking.store', $a), $this->bookingPayload())->assertRedirect(route('guest.login'));
        $this->assertSame(0, Booking::count());
    }

    public function test_login_page_shows_booking_hint_and_returns_to_booking_after_login(): void
    {
        $a = $this->apartment();
        Guest::create(['name' => 'Siti', 'email' => 'siti@example.com', 'phone' => '0811111111', 'password' => 'password123']);

        $this->get(route('booking.create', $a));
        $this->get(route('guest.login'))->assertOk()->assertSee('Masuk atau daftar dulu untuk melanjutkan booking');

        $this->post(route('guest.login.submit'), ['email' => 'siti@example.com', 'password' => 'password123'])
            ->assertRedirect(route('booking.create', $a));
        $this->assertAuthenticated('guest');
    }

    public function test_register_logs_in_and_wrong_password_is_rejected(): void
    {
        $this->post(route('guest.register.submit'), [
            'name' => 'Budi', 'phone' => '081299998888', 'email' => 'budi@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('guest.bookings'));
        $this->assertAuthenticated('guest');
        $this->assertSame('081299998888', Guest::first()->phone);

        auth('guest')->logout();
        $this->post(route('guest.login.submit'), ['email' => 'budi@example.com', 'password' => 'salah'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('guest');

        $this->post(route('guest.register.submit'), [
            'name' => 'Budi 2', 'phone' => '081299998888', 'email' => 'budi@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_booking_form_is_prefilled_and_booking_is_linked_to_account(): void
    {
        $a = $this->apartment();
        $g = $this->loginGuest(['name' => 'Siti Aminah', 'email' => 'siti@example.com', 'phone' => '08123456789']);

        $this->get(route('booking.create', $a))->assertOk()
            ->assertSee('value="Siti Aminah"', false)
            ->assertSee('value="08123456789"', false)
            ->assertSee('value="siti@example.com"', false);

        $this->post(route('booking.store', $a), $this->bookingPayload())->assertRedirect();
        $this->assertSame($g->id, Booking::firstOrFail()->guest_id);
    }

    public function test_phone_is_saved_to_account_after_first_booking_when_missing(): void
    {
        $g = $this->loginGuest(['phone' => null]);

        $this->post(route('booking.store', $this->apartment()), $this->bookingPayload())->assertRedirect();
        $this->assertSame('081234567890', $g->fresh()->phone);
    }

    public function test_my_bookings_lists_only_own_bookings_with_invoice_link(): void
    {
        $a = $this->apartment();
        $me = $this->loginGuest();
        $other = Guest::create(['name' => 'Lain', 'email' => 'lain@example.com', 'password' => 'password123']);

        $this->post(route('booking.store', $a), $this->bookingPayload())->assertRedirect();
        $mine = Booking::firstOrFail();
        $theirs = Booking::create([
            'booking_code' => 'LH-LAIN', 'invoice_number' => 'INV-LH-LAIN', 'access_token' => 'tokenlain', 'apartment_id' => $a->id,
            'guest_id' => $other->id, 'guest_name' => 'Lain', 'guest_phone' => '0811', 'guest_email' => 'lain@example.com',
            'checkin' => now()->addDays(20), 'checkout' => now()->addDays(22), 'total_price' => 1, 'payment_status' => 'menunggu pembayaran', 'booking_status' => 'baru',
        ]);

        $this->get(route('guest.bookings'))->assertOk()
            ->assertSee($mine->booking_code)
            ->assertSee(route('invoice.show', $mine->access_token), false)
            ->assertDontSee($theirs->booking_code);
    }

    public function test_my_bookings_requires_login_and_profile_can_be_updated(): void
    {
        $this->get(route('guest.bookings'))->assertRedirect(route('guest.login'));

        $g = $this->loginGuest();
        $this->put(route('guest.profile'), ['name' => 'Nama Baru', 'phone' => '082100000000'])->assertSessionHas('success');
        $this->assertSame('Nama Baru', $g->fresh()->name);
    }

    public function test_logout_clears_session(): void
    {
        $this->loginGuest();
        $this->post(route('guest.logout'))->assertRedirect(route('home'));
        $this->assertGuest('guest');
    }

    public function test_header_shows_login_button_for_visitors_and_name_for_members(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('>Masuk</a>', false);

        $this->loginGuest(['name' => 'Siti Aminah']);
        $this->get(route('home'))->assertOk()->assertSee('Siti')->assertSee(route('guest.bookings'), false);
    }

    private function googleCallback(array $profile)
    {
        config(['services.google.client_id' => 'cid', 'services.google.client_secret' => 'sec']);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'tok']),
            'openidconnect.googleapis.com/*' => Http::response($profile),
        ]);

        return $this->withSession(['guest_google_state' => 'abc'])
            ->get(route('guest.google.callback', ['code' => 'c', 'state' => 'abc']));
    }

    public function test_google_creates_active_account_and_links_old_bookings_by_email(): void
    {
        $a = $this->apartment();
        $old = Booking::create([
            'booking_code' => 'LH-OLD', 'invoice_number' => 'INV-LH-OLD', 'access_token' => 'tokenold', 'apartment_id' => $a->id,
            'guest_name' => 'Tamu', 'guest_phone' => '0811', 'guest_email' => 'baru@gmail.com',
            'checkin' => now()->addDays(2), 'checkout' => now()->addDays(3), 'total_price' => 1, 'payment_status' => 'menunggu pembayaran', 'booking_status' => 'baru',
        ]);

        $this->googleCallback(['sub' => 'g1', 'email' => 'Baru@Gmail.com', 'email_verified' => true, 'name' => 'Tamu Google'])
            ->assertRedirect(route('guest.bookings'));

        $g = Guest::where('email', 'baru@gmail.com')->firstOrFail();
        $this->assertSame('g1', $g->google_id);
        $this->assertAuthenticatedAs($g, 'guest');
        $this->assertSame($g->id, $old->fresh()->guest_id);
    }

    public function test_google_does_not_link_bookings_to_password_account_with_same_email(): void
    {
        $a = $this->apartment();
        $pre = Guest::create(['name' => 'Penyusup', 'email' => 'korban@gmail.com', 'password' => 'password123']);
        $old = Booking::create([
            'booking_code' => 'LH-OLD2', 'invoice_number' => 'INV-LH-OLD2', 'access_token' => 'tokenold2', 'apartment_id' => $a->id,
            'guest_name' => 'Korban', 'guest_phone' => '0811', 'guest_email' => 'korban@gmail.com',
            'checkin' => now()->addDays(2), 'checkout' => now()->addDays(3), 'total_price' => 1, 'payment_status' => 'menunggu pembayaran', 'booking_status' => 'baru',
        ]);

        $this->googleCallback(['sub' => 'g2', 'email' => 'korban@gmail.com', 'email_verified' => true])->assertRedirect();

        $this->assertNull($old->fresh()->guest_id);
        $this->assertSame('g2', $pre->fresh()->google_id);
    }

    public function test_google_rejects_bad_state_and_unverified_email(): void
    {
        config(['services.google.client_id' => 'cid', 'services.google.client_secret' => 'sec']);
        $this->withSession(['guest_google_state' => 'abc'])
            ->get(route('guest.google.callback', ['code' => 'c', 'state' => 'salah']))
            ->assertRedirect(route('guest.login'))->assertSessionHasErrors('email');

        $this->googleCallback(['sub' => 'g3', 'email' => 'x@gmail.com', 'email_verified' => false])
            ->assertRedirect(route('guest.login'))->assertSessionHasErrors('email');
        $this->assertGuest('guest');
    }

    public function test_google_button_follows_configuration_and_forms_have_turnstile(): void
    {
        config(['services.google.client_id' => null]);
        $this->get(route('guest.login'))->assertOk()->assertDontSee('Masuk dengan Google');

        config(['services.google.client_id' => 'cid', 'services.google.client_secret' => 'sec',
            'services.turnstile.site_key' => 'sk', 'services.turnstile.secret_key' => 'secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        foreach (['guest.login', 'guest.register'] as $r) {
            $this->get(route($r))->assertOk()->assertSee('cf-turnstile', false)->assertSee(route('guest.google.redirect'), false);
        }

        Guest::create(['name' => 'S', 'email' => 's@example.com', 'password' => 'password123']);
        $this->post(route('guest.login.submit'), ['email' => 's@example.com', 'password' => 'password123', 'cf-turnstile-response' => 'x'])
            ->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest('guest');
    }
}
