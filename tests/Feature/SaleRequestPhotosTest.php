<?php

namespace Tests\Feature;

use App\Models\SaleRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SaleRequestPhotosTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return $extra + [
            'owner_name' => 'Pak Fahri', 'owner_phone' => '081234567890', 'apartment_name' => 'Easton Park',
            'location' => 'Jatinangor', 'unit_type' => 'Studio', 'size' => '24 m2', 'expected_price' => 350000000,
        ];
    }

    public function test_multiple_photos_are_saved_with_the_request(): void
    {
        Storage::fake('public');
        $this->loginGuest();

        $this->get(route('sale-request.create'))->assertOk()->assertSee('name="photos[]"', false)->assertSee('multipart/form-data', false);

        $this->post(route('sale-request.store'), $this->payload([
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png'), UploadedFile::fake()->image('c.jpg')],
        ]))->assertRedirect(route('account.sale-requests'))->assertSessionHasNoErrors();

        $lead = SaleRequest::firstOrFail();
        $this->assertSame(3, $lead->photos()->count());
        foreach ($lead->photos as $p) {
            Storage::disk('public')->assertExists('sale_requests/'.$p->image);
        }
    }

    public function test_photos_are_optional_and_limited(): void
    {
        Storage::fake('public');
        $this->loginGuest();

        $this->post(route('sale-request.store'), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(0, SaleRequest::firstOrFail()->photos()->count());

        $many = array_map(fn ($i) => UploadedFile::fake()->image("p$i.jpg"), range(1, 11));
        $this->post(route('sale-request.store'), $this->payload(['photos' => $many]))->assertSessionHasErrors('photos');
        $this->post(route('sale-request.store'), $this->payload(['photos' => [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]]))->assertSessionHasErrors('photos.0');
        $this->assertSame(1, SaleRequest::count());
    }
}
