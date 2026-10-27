<?php

namespace Tests\Feature;

use App\Models\SaleRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TitipJualTurnstileTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return $extra + [
            'owner_name' => 'Pak Fahri', 'owner_phone' => '081234567890', 'apartment_name' => 'Easton Park',
            'location' => 'Jatinangor', 'unit_type' => 'Studio', 'size' => '24 m2', 'expected_price' => 350000000,
        ];
    }

    private function enable(): void
    {
        config(['services.turnstile.site_key' => 'sk', 'services.turnstile.secret_key' => 'secret']);
    }

    public function test_widget_hidden_and_submit_works_when_not_configured(): void
    {
        $this->get(route('sale-request.create'))->assertOk()->assertDontSee('cf-turnstile');
        $this->post(route('sale-request.store'), $this->payload())->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, SaleRequest::count());
    }

    public function test_widget_shown_when_configured(): void
    {
        $this->enable();
        $this->get(route('sale-request.create'))->assertOk()->assertSee('cf-turnstile', false)->assertSee('challenges.cloudflare.com/turnstile', false);
    }

    public function test_missing_or_failed_token_is_rejected(): void
    {
        $this->enable();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        $this->post(route('sale-request.store'), $this->payload())->assertSessionHasErrors('cf-turnstile-response');
        $this->post(route('sale-request.store'), $this->payload(['cf-turnstile-response' => 'bad']))->assertSessionHasErrors('cf-turnstile-response');
        $this->assertSame(0, SaleRequest::count());
    }

    public function test_valid_token_is_accepted_and_verified_with_secret(): void
    {
        $this->enable();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $this->post(route('sale-request.store'), $this->payload(['cf-turnstile-response' => 'good']))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, SaleRequest::count());
        Http::assertSent(fn ($r) => $r['secret'] === 'secret' && $r['response'] === 'good');
    }

    public function test_cloudflare_outage_blocks_submission_with_friendly_error(): void
    {
        $this->enable();
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('down'));

        $this->post(route('sale-request.store'), $this->payload(['cf-turnstile-response' => 'x']))
            ->assertSessionHasErrors('cf-turnstile-response');
        $this->assertSame(0, SaleRequest::count());
    }
}
