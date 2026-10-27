<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Owner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthTurnstileTest extends TestCase
{
    use RefreshDatabase;

    private bool $cfOk = true;

    private function enable(bool $ok = true): void
    {
        $this->cfOk = $ok;
        config(['services.turnstile.site_key' => 'sk', 'services.turnstile.secret_key' => 'secret']);
        Http::fake(['challenges.cloudflare.com/*' => fn () => Http::response(['success' => $this->cfOk])]);
    }

    public function test_widget_is_rendered_on_all_auth_forms_when_enabled(): void
    {
        $this->enable();
        foreach (['admin.login', 'owner.login', 'owner.register', 'sale-request.create'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('cf-turnstile', false);
        }
    }

    public function test_admin_login_requires_valid_token(): void
    {
        Admin::create(['name' => 'A', 'username' => 'adm', 'password' => 'password123']);
        $this->enable();

        $this->post(route('admin.login.submit'), ['login' => 'adm', 'password' => 'password123'])
            ->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest('admin');

        $this->enable(false);
        $this->post(route('admin.login.submit'), ['login' => 'adm', 'password' => 'password123', 'cf-turnstile-response' => 'x'])
            ->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest('admin');

        $this->enable();
        $this->post(route('admin.login.submit'), ['login' => 'adm', 'password' => 'password123', 'cf-turnstile-response' => 'ok'])
            ->assertRedirect();
        $this->assertAuthenticated('admin');
    }

    public function test_owner_login_and_register_require_token(): void
    {
        Owner::create(['name' => 'O', 'phone' => '0812', 'email' => 'o@x.com', 'password' => 'password123', 'status' => 'aktif']);
        $this->enable();

        $this->post(route('owner.login.submit'), ['email' => 'o@x.com', 'password' => 'password123'])
            ->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest('owner');

        $reg = ['name' => 'Baru', 'phone' => '0813', 'email' => 'baru@x.com', 'password' => 'password123', 'password_confirmation' => 'password123'];
        $this->post(route('owner.register.submit'), $reg)->assertSessionHasErrors('cf-turnstile-response');
        $this->assertDatabaseMissing('owners', ['email' => 'baru@x.com']);

        $this->post(route('owner.register.submit'), $reg + ['cf-turnstile-response' => 'ok'])->assertRedirect(route('owner.login'));
        $this->assertDatabaseHas('owners', ['email' => 'baru@x.com', 'status' => 'pending']);

        $this->post(route('owner.login.submit'), ['email' => 'o@x.com', 'password' => 'password123', 'cf-turnstile-response' => 'ok'])
            ->assertRedirect();
        $this->assertAuthenticated('owner');
    }

    public function test_forms_work_without_turnstile_when_not_configured(): void
    {
        Admin::create(['name' => 'A', 'username' => 'adm', 'password' => 'password123']);
        $this->get(route('admin.login'))->assertOk()->assertDontSee('cf-turnstile', false);
        $this->post(route('admin.login.submit'), ['login' => 'adm', 'password' => 'password123'])->assertRedirect();
        $this->assertAuthenticated('admin');
    }
}
