<?php

namespace Tests\Feature;

use App\Models\SaleCommission;
use App\Models\SaleRequest;
use App\Models\SaleUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesFixtures;
use Tests\TestCase;

class CrmWorkflowTest extends TestCase
{
    use CreatesFixtures, RefreshDatabase;

    private function lead(array $o = []): SaleRequest
    {
        return SaleRequest::create($o + [
            'owner_name' => 'Fahri', 'owner_phone' => '0812', 'apartment_name' => 'Easton Park', 'location' => 'Jatinangor',
            'unit_type' => '1 Bedroom', 'size' => '34 m2', 'expected_price' => 350000000, 'notes' => 'Full furnished', 'status' => 'baru',
        ]);
    }

    public function test_public_form_creates_lead_with_intake_event(): void
    {
        $this->post(route('sale.request.store'), ['owner_name' => 'A', 'owner_phone' => '08', 'apartment_name' => 'X', 'location' => 'Y']);

        $r = SaleRequest::firstOrFail();
        $this->assertSame('masuk', $r->events()->first()->type);
    }

    public function test_status_change_is_logged_and_detail_page_renders(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $r = $this->lead();

        $this->post(route('admin.sale-requests.status', $r), ['status' => 'follow up', 'note' => 'Sudah ditelepon'])->assertSessionHas('status');
        $this->assertSame('follow up', $r->fresh()->status);
        $this->assertStringContainsString('Sudah ditelepon', $r->events()->first()->body);

        $this->get(route('admin.sale-requests.show', $r))->assertOk()->assertSee('Timeline Aktivitas')->assertSee('Sudah ditelepon');
        $this->post(route('admin.sale-requests.status', $r), ['status' => 'ngawur'])->assertSessionHasErrors('status');
    }

    public function test_survey_offer_and_note_advance_status_and_validate(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $r = $this->lead();

        $this->post(route('admin.sale-requests.survey', $r), ['survey_at' => now()->subDay()->format('Y-m-d H:i')])->assertSessionHasErrors('survey_at');
        $this->post(route('admin.sale-requests.survey', $r), ['survey_at' => now()->addDays(2)->format('Y-m-d H:i')])->assertSessionHasNoErrors();
        $r->refresh();
        $this->assertSame('survey', $r->status);
        $this->assertNotNull($r->survey_at);

        $this->post(route('admin.sale-requests.offer', $r), ['amount' => 0])->assertSessionHasErrors('amount');
        $this->post(route('admin.sale-requests.offer', $r), ['amount' => 330000000, 'note' => 'Pembeli A']);
        $r->refresh();
        $this->assertSame('negosiasi', $r->status);
        $this->assertEquals(330000000, $r->last_offer);

        $this->post(route('admin.sale-requests.note', $r), ['note' => 'Pemilik fleksibel'])->assertSessionHas('status');
        $this->assertSame('catatan', $r->events()->first()->type);
    }

    public function test_photos_upload_validate_and_publish_copies_them_to_a_sale_unit_once(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin(), 'admin');
        $r = $this->lead();

        $this->post(route('admin.sale-requests.photos', $r), ['photos' => [UploadedFile::fake()->create('x.php', 10, 'application/x-php')]])->assertSessionHasErrors('photos.0');
        $this->post(route('admin.sale-requests.photos', $r), ['photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')]])->assertSessionHasNoErrors();
        $this->assertSame(2, $r->photos()->count());
        $this->assertSame('foto unit', $r->fresh()->status);

        $this->post(route('admin.sale-requests.publish', $r), ['price' => 345000000])->assertSessionHasNoErrors();
        $r->refresh();
        $unit = SaleUnit::findOrFail($r->sale_unit_id);
        $this->assertSame('listing', $r->status);
        $this->assertEquals(345000000, $unit->price);
        $this->assertSame('Fahri', $unit->seller_name);
        $this->assertSame(2, $unit->images()->count());
        Storage::disk('public')->assertExists('sale_units/'.$unit->image);
        $this->get(route('sale.show', $unit))->assertOk();

        // tidak boleh dipublikasikan dua kali
        $this->post(route('admin.sale-requests.publish', $r), ['price' => 1000])->assertSessionHasErrors('crm');
        $this->assertSame(1, SaleUnit::count());
    }

    public function test_closing_creates_commission_marks_unit_sold_and_cannot_repeat(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin(), 'admin');
        $r = $this->lead();
        $this->post(route('admin.sale-requests.publish', $r), ['price' => 350000000]);

        $this->post(route('admin.sale-requests.close', $r), [
            'deal_price' => 325000000, 'commission_type' => 'percent', 'commission_value' => 15, 'other_cost' => 0,
        ])->assertSessionHasNoErrors();

        $r->refresh();
        $c = $r->saleCommission;
        $this->assertEquals(48750000, $c->commission_amount);
        $this->assertEquals(276250000, $c->seller_net_amount);
        $this->assertSame($r->id, $c->sale_request_id);
        $this->assertSame('closing', $r->status);
        $this->assertSame('terjual', SaleUnit::first()->status);
        $this->get(route('sale.show', SaleUnit::first()))->assertNotFound(); // tidak tampil publik lagi

        $this->post(route('admin.sale-requests.close', $r), ['deal_price' => 1, 'commission_type' => 'fixed', 'commission_value' => 0])->assertSessionHasErrors('crm');
        $this->assertSame(1, SaleCommission::count());

        $this->post(route('admin.sale-requests.status', $r), ['status' => 'selesai']);
        $this->post(route('admin.sale-requests.publish', $r), ['price' => 1000])->assertSessionHasErrors('crm');
    }

    public function test_cancelled_lead_cannot_be_closed_and_filter_search_work(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $a = $this->lead(['owner_name' => 'Andi', 'status' => 'batal']);
        $b = $this->lead(['owner_name' => 'Budi', 'apartment_name' => 'Taman Melati']);

        $this->post(route('admin.sale-requests.close', $a), ['deal_price' => 1000, 'commission_type' => 'fixed', 'commission_value' => 1])->assertSessionHasErrors('crm');

        $this->get(route('admin.sale-requests', ['status' => 'batal']))->assertSee('Andi')->assertDontSee('Budi');
        $this->get(route('admin.sale-requests', ['q' => 'melati']))->assertSee('Budi')->assertDontSee('Andi');
    }

    public function test_crm_routes_reject_get_mutations_and_guests(): void
    {
        $r = $this->lead();
        $this->get(route('admin.sale-requests.show', $r))->assertRedirect(route('admin.login'));
        $this->post(route('admin.sale-requests.close', $r), [])->assertRedirect(route('admin.login'));

        $this->actingAs($this->admin(), 'admin');
        $this->get(route('admin.sale-requests.publish', $r))->assertStatus(405);
    }
}
