<?php

namespace Tests\Feature;

use App\Models\Owner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OwnerGoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'cid', 'services.google.client_secret' => 'sec']);
    }

    private function callbackWith(array $profile, string $state = 'abc')
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'tok']),
            'openidconnect.googleapis.com/*' => Http::response($profile),
        ]);

        return $this->withSession(['owner_google_state' => 'abc'])
            ->get(route('owner.google.callback', ['code' => 'c', 'state' => $state]));
    }

    public function test_button_hidden_when_not_configured_and_shown_when_configured(): void
    {
        $this->get(route('owner.register'))->assertOk()->assertSee('Daftar dengan Google');

        config(['services.google.client_id' => null]);
        $this->get(route('owner.register'))->assertOk()->assertDontSee('Daftar dengan Google');
    }

    public function test_redirect_goes_to_google_with_state(): void
    {
        $res = $this->get(route('owner.google.redirect'));

        $res->assertRedirectContains('https://accounts.google.com/o/oauth2/v2/auth');
        $this->assertNotEmpty(session('owner_google_state'));
    }

    public function test_new_google_user_registers_as_pending_and_cannot_login_yet(): void
    {
        $this->callbackWith(['sub' => 'g1', 'email' => 'Baru@Gmail.com', 'email_verified' => true, 'name' => 'Owner Baru'])
            ->assertRedirect(route('owner.login'))
            ->assertSessionHas('success');

        $o = Owner::where('email', 'baru@gmail.com')->firstOrFail();
        $this->assertSame('pending', $o->status);
        $this->assertSame('g1', $o->google_id);
        $this->assertNull($o->password);
        $this->assertGuest('owner');

        // login ulang selagi pending ditolak
        $this->callbackWith(['sub' => 'g1', 'email' => 'baru@gmail.com', 'email_verified' => true])
            ->assertRedirect(route('owner.login'))->assertSessionHasErrors('email');
        $this->assertGuest('owner');
    }

    public function test_active_owner_logs_in_and_existing_email_account_is_linked(): void
    {
        $o = Owner::create(['name' => 'Lama', 'phone' => '0812', 'email' => 'lama@gmail.com', 'password' => 'password123', 'status' => 'aktif']);

        $this->callbackWith(['sub' => 'g2', 'email' => 'lama@gmail.com', 'email_verified' => true])
            ->assertRedirect(route('owner.dashboard'));

        $this->assertAuthenticatedAs($o->fresh(), 'owner');
        $this->assertSame('g2', $o->fresh()->google_id);
    }

    public function test_unverified_email_state_mismatch_and_inactive_are_rejected(): void
    {
        $this->callbackWith(['sub' => 'g3', 'email' => 'x@gmail.com', 'email_verified' => false])
            ->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('owners', ['email' => 'x@gmail.com']);

        $this->callbackWith(['sub' => 'g3', 'email' => 'x@gmail.com', 'email_verified' => true], 'salah')
            ->assertSessionHasErrors('email');
        $this->assertGuest('owner');

        Owner::create(['name' => 'Off', 'email' => 'off@gmail.com', 'status' => 'nonaktif']);
        $this->callbackWith(['sub' => 'g4', 'email' => 'off@gmail.com', 'email_verified' => true])
            ->assertSessionHasErrors('email');
        $this->assertGuest('owner');
    }

    public function test_google_only_account_cannot_login_with_empty_password(): void
    {
        Owner::create(['name' => 'G', 'email' => 'g@gmail.com', 'google_id' => 'g5', 'status' => 'aktif']);

        $this->post(route('owner.login.submit'), ['email' => 'g@gmail.com', 'password' => 'apapun'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('owner');
    }
}
