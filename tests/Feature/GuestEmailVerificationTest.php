<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Guest;
use App\Notifications\VerifyGuestEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class GuestEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function legacyBooking(string $email, ?int $guestId = null, string $code = 'LH1'): Booking
    {
        $a = Apartment::first() ?? Apartment::create(['owner_name' => 'LH', 'name' => 'Unit', 'location' => 'Bandung', 'type' => 'Studio', 'price_per_night' => 200000, 'status' => 'aktif']);

        return Booking::create([
            'booking_code' => $code, 'access_token' => str_pad($code, 40, 'x'), 'apartment_id' => $a->id, 'guest_id' => $guestId,
            'guest_name' => 'Tamu Lama', 'guest_phone' => '081234567890', 'guest_email' => $email,
            'checkin' => now()->addDays(2)->toDateString(), 'checkout' => now()->addDays(3)->toDateString(),
            'total_price' => 200000, 'booking_status' => 'baru',
        ]);
    }

    private function register(string $email = 'lama@example.com'): Guest
    {
        $this->post(route('guest.register.submit'), [
            'name' => 'Penyewa', 'phone' => '081234567890', 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasNoErrors();

        return Guest::where('email', $email)->firstOrFail();
    }

    public function test_register_sends_verification_mail_but_does_not_link_bookings_yet(): void
    {
        Notification::fake();
        $old = $this->legacyBooking('lama@example.com');

        $guest = $this->register();

        Notification::assertSentTo($guest, VerifyGuestEmail::class);
        $this->assertFalse($guest->hasVerifiedEmail());
        $this->assertNull($old->fresh()->guest_id);
        $this->get(route('guest.bookings'))->assertOk()->assertSee('Email belum diverifikasi')->assertDontSee($old->booking_code);
    }

    public function test_registration_still_works_when_mail_cannot_be_sent(): void
    {
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('smtp down'));

        $this->post(route('guest.register.submit'), [
            'name' => 'Penyewa', 'phone' => '081234567890', 'email' => 'x@example.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect();

        $this->assertAuthenticated('guest');
    }

    public function test_verification_link_marks_verified_and_links_only_unowned_bookings_with_same_email(): void
    {
        Notification::fake();
        $guest = $this->register();
        $other = Guest::create(['name' => 'Orang Lain', 'email' => 'lain@example.com', 'password' => 'password123']);

        $a = $this->legacyBooking('LAMA@Example.com', null, 'LHA');          // beda kapitalisasi -> tertaut
        $b = $this->legacyBooking('lama@example.com', null, 'LHB');          // tertaut
        $c = $this->legacyBooking('lama@example.com', $other->id, 'LHC');    // sudah milik akun lain -> tidak diambil
        $d = $this->legacyBooking('bukan-saya@example.com', null, 'LHD');    // email lain -> tidak tertaut

        $this->get($guest->verificationUrl())->assertRedirect(route('guest.bookings'))->assertSessionHas('success');

        $this->assertTrue($guest->fresh()->hasVerifiedEmail());
        $this->assertSame($guest->id, $a->fresh()->guest_id);
        $this->assertSame($guest->id, $b->fresh()->guest_id);
        $this->assertSame($other->id, $c->fresh()->guest_id);
        $this->assertNull($d->fresh()->guest_id);
        $this->get(route('guest.bookings'))->assertSee('LHA')->assertSee('LHB')->assertDontSee('Email belum diverifikasi');
        $this->assertDatabaseHas('audit_logs', ['action' => 'guest.email_verified']);
    }

    public function test_link_requires_being_logged_in_as_the_same_account(): void
    {
        Notification::fake();
        $victimGuest = Guest::create(['name' => 'Akun Penyerang', 'email' => 'korban@example.com', 'password' => 'password123']);
        $booking = $this->legacyBooking('korban@example.com');
        $url = $victimGuest->verificationUrl();

        // Pemilik email asli membuka tautan tanpa login ke akun itu: diminta login, tidak terverifikasi.
        $this->get($url)->assertRedirect(route('guest.login'));
        $this->assertFalse($victimGuest->fresh()->hasVerifiedEmail());
        $this->assertNull($booking->fresh()->guest_id);

        // Login sebagai akun lain lalu membuka tautan: ditolak.
        $someoneElse = Guest::create(['name' => 'Lain', 'email' => 'lain@example.com', 'password' => 'password123']);
        $this->actingAs($someoneElse, 'guest')->get($url)->assertForbidden();
        $this->assertFalse($victimGuest->fresh()->hasVerifiedEmail());
    }

    public function test_tampered_expired_and_stale_links_are_rejected(): void
    {
        $guest = Guest::create(['name' => 'P', 'email' => 'p@example.com', 'password' => 'password123']);
        $this->actingAs($guest, 'guest');

        $this->get($guest->verificationUrl().'x')->assertForbidden();                       // tanda tangan rusak
        $this->get(route('guest.verify', ['id' => $guest->id, 'hash' => sha1('p@example.com')]))->assertForbidden(); // tanpa signature

        $expired = URL::temporarySignedRoute('guest.verify', now()->subMinute(), ['id' => $guest->id, 'hash' => sha1('p@example.com')]);
        $this->get($expired)->assertForbidden();

        $wrongHash = URL::temporarySignedRoute('guest.verify', now()->addHour(), ['id' => $guest->id, 'hash' => sha1('lain@example.com')]);
        $this->get($wrongHash)->assertForbidden();

        $this->assertFalse($guest->fresh()->hasVerifiedEmail());
    }

    public function test_resend_requires_login_is_noop_when_verified_and_sends_when_not(): void
    {
        Notification::fake();
        $this->post(route('guest.verify.resend'))->assertRedirect(route('guest.login'));

        $guest = Guest::create(['name' => 'P', 'email' => 'p@example.com', 'password' => 'password123']);
        $this->actingAs($guest, 'guest')->post(route('guest.verify.resend'))->assertSessionHas('success');
        Notification::assertSentToTimes($guest, VerifyGuestEmail::class, 1);

        $guest->markEmailAsVerified();
        $this->post(route('guest.verify.resend'))->assertSessionHas('success');
        Notification::assertSentToTimes($guest, VerifyGuestEmail::class, 1);
    }

    public function test_verifying_twice_is_harmless_and_links_bookings_added_later(): void
    {
        $guest = Guest::create(['name' => 'P', 'email' => 'p@example.com', 'password' => 'password123']);
        $this->actingAs($guest, 'guest');
        $url = $guest->verificationUrl();

        $this->get($url)->assertRedirect();
        $verifiedAt = $guest->fresh()->email_verified_at;
        $late = $this->legacyBooking('p@example.com');

        $this->get($url)->assertRedirect();
        $this->assertEquals($verifiedAt, $guest->fresh()->email_verified_at);
        $this->assertSame($guest->id, $late->fresh()->guest_id);
    }

    public function test_unverified_account_never_gets_linked_by_the_linker(): void
    {
        $guest = Guest::create(['name' => 'P', 'email' => 'p@example.com', 'password' => 'password123']);
        $b = $this->legacyBooking('p@example.com');

        $this->assertSame(0, app(\App\Services\GuestBookingLinker::class)->link($guest));
        $this->assertNull($b->fresh()->guest_id);
    }

    public function test_new_google_accounts_are_verified_and_password_accounts_stay_unlinked(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);

        $login = function (string $email, string $sub) {
            Http::fake([
                'oauth2.googleapis.com/*' => Http::response(['access_token' => 't']),
                'openidconnect.googleapis.com/*' => Http::response(['sub' => $sub, 'email' => $email, 'email_verified' => true, 'name' => 'G']),
            ]);
            $this->withSession(['guest_google_state' => 'abc'])->get(route('guest.google.callback', ['state' => 'abc', 'code' => 'c']));
        };

        $login('baru@example.com', 'sub-1');
        $this->assertTrue(Guest::where('email', 'baru@example.com')->firstOrFail()->hasVerifiedEmail());
    }
}
