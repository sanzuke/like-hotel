<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\MidtransLog;
use App\Models\SaleCommission;
use App\Models\SaleRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortedFromMainTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create(['name' => 'Admin', 'username' => 'adm', 'password' => 'password123']);
    }

    private function lead(array $over = []): SaleRequest
    {
        return SaleRequest::create($over + [
            'owner_name' => 'Pak Fahri', 'owner_phone' => '081234567890', 'apartment_name' => 'Easton Park',
            'location' => 'Jatinangor', 'unit_type' => 'Studio', 'size' => '24 m2', 'expected_price' => 350000000, 'status' => 'baru',
        ]);
    }

    private function closing(string $date, string $apt, float $deal, float $comm, string $status = 'closing', float $other = 0): SaleCommission
    {
        $c = new SaleCommission([
            'seller_name' => 'S', 'apartment_name' => $apt, 'deal_price' => $deal, 'commission_type' => 'fixed', 'commission_value' => $comm,
            'commission_amount' => $comm, 'other_cost' => $other, 'seller_net_amount' => $deal - $comm - $other, 'status' => $status,
        ]);
        $c->created_at = $date; // bukan mass-assignable
        $c->save();

        return $c;
    }

    // --- Log Midtrans -----------------------------------------------------

    public function test_every_midtrans_notification_is_logged_with_its_result(): void
    {
        config(['likehotel.midtrans.server_key' => 'k']);
        $a = Apartment::create(['owner_name' => 'LH', 'name' => 'U', 'location' => 'L', 'type' => 'S', 'price_per_night' => 100000, 'status' => 'aktif']);
        $this->loginGuest();
        $this->post(route('booking.store', $a), [
            'guest_name' => 'T', 'guest_phone' => '081234567890', 'guest_email' => 't@x.id',
            'checkin' => now()->addDay()->toDateString(), 'checkout' => now()->addDays(2)->toDateString(),
        ]);
        $b = Booking::firstOrFail();

        $n = ['order_id' => $b->booking_code, 'status_code' => '200', 'gross_amount' => '100000.00', 'transaction_status' => 'settlement'];
        $sign = fn (array $x) => hash('sha512', $x['order_id'].$x['status_code'].$x['gross_amount'].'k');

        $this->postJson('/midtrans/notification', $n + ['signature_key' => 'palsu'])->assertForbidden();
        $this->postJson('/midtrans/notification', ['gross_amount' => '1.00'] + $n + ['signature_key' => $sign(['gross_amount' => '1.00'] + $n)])->assertStatus(400);
        $this->postJson('/midtrans/notification', $n + ['signature_key' => $sign($n)])->assertOk();
        $this->postJson('/midtrans/notification', $n + ['signature_key' => $sign($n)])->assertOk(); // duplikat

        $this->assertSame(
            ['rejected', 'rejected', 'processed', 'ignored'],
            MidtransLog::orderBy('id')->pluck('result')->all(),
        );
        $this->assertSame('signature tidak valid', MidtransLog::orderBy('id')->first()->note);
        $this->assertSame($b->booking_code, MidtransLog::latest('id')->first()->order_id);

        $this->actingAs($this->admin(), 'admin');
        $this->get(route('admin.midtrans-logs.index'))->assertOk()->assertSee($b->booking_code);
        $this->get(route('admin.midtrans-logs.index', ['result' => 'rejected']))->assertOk()->assertSee('signature tidak valid');
        $this->get(route('admin.midtrans-logs.index', ['result' => 'ngawur']))->assertSessionHasErrors('result');
    }

    public function test_midtrans_log_page_requires_admin(): void
    {
        $this->get(route('admin.midtrans-logs.index'))->assertRedirect(route('admin.login'));
    }

    // --- CRM: foto & penawaran -------------------------------------------

    public function test_offer_is_recorded_and_advances_early_leads_only(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $lead = $this->lead();

        $this->post(route('admin.sale-requests.offer', $lead), ['amount' => 0])->assertSessionHasErrors('amount');
        $this->post(route('admin.sale-requests.offer', $lead), ['amount' => 310000000, 'note' => 'Pembeli Budi'])->assertRedirect();

        $lead->refresh();
        $this->assertEquals(310000000, $lead->last_offer);
        $this->assertSame('negosiasi', $lead->status);
        $note = $lead->timeline()->where('type', 'penawaran')->firstOrFail();
        $this->assertEquals(310000000, $note->amount);
        $this->assertStringContainsString('Pembeli Budi', $note->body);

        // Lead yang sudah closing tidak ditarik mundur.
        $closed = $this->lead(['status' => 'closing']);
        $this->post(route('admin.sale-requests.offer', $closed), ['amount' => 1000])->assertRedirect();
        $this->assertSame('closing', $closed->fresh()->status);

        $this->get(route('admin.sale-requests.show', $lead))->assertOk()->assertSee('Penawaran Pembeli');
    }

    public function test_photos_upload_validate_advance_and_are_copied_on_publish(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin(), 'admin');
        $lead = $this->lead();

        $this->post(route('admin.sale-requests.photos', $lead), ['photos' => [UploadedFile::fake()->create('x.php', 10, 'application/x-php')]])
            ->assertSessionHasErrors('photos.0');
        $this->post(route('admin.sale-requests.photos', $lead), [])->assertSessionHasErrors('photos');
        $this->assertSame(0, $lead->photos()->count());

        $this->post(route('admin.sale-requests.photos', $lead), ['photos' => [
            UploadedFile::fake()->image('a.jpg', 600, 400), UploadedFile::fake()->image('b.png', 600, 400),
        ]])->assertRedirect();

        $lead->refresh();
        $this->assertSame(2, $lead->photos()->count());
        $this->assertSame('foto unit', $lead->status);
        foreach ($lead->photos as $p) {
            Storage::disk('public')->assertExists('sale_requests/'.$p->image);
        }

        // Publikasi menyalin foto ke unit dijual.
        $this->post(route('admin.sale-requests.publish', $lead))->assertRedirect();
        $unit = $lead->fresh()->saleUnit;
        $this->assertSame(2, $unit->images()->count());
        $this->assertNotNull($unit->image);
        foreach ($unit->images as $img) {
            Storage::disk('public')->assertExists('sale_units/'.$img->image);
        }

        // Hapus satu foto lead: file ikut terhapus, salinan di unit tetap ada.
        $photo = $lead->photos()->first();
        $this->delete(route('admin.sale-requests.photos.destroy', [$lead, $photo]))->assertRedirect();
        Storage::disk('public')->assertMissing('sale_requests/'.$photo->image);
        $this->assertSame(2, $unit->images()->count());

        // Foto milik lead lain tidak bisa dihapus lewat URL lead ini.
        $other = $this->lead();
        $this->delete(route('admin.sale-requests.photos.destroy', [$other, $lead->photos()->first()]))->assertNotFound();

        // Hapus lead membersihkan file fotonya.
        $remaining = $lead->photos()->first();
        $this->delete(route('admin.sale-requests.destroy', $lead))->assertRedirect();
        Storage::disk('public')->assertMissing('sale_requests/'.$remaining->image);
    }

    // --- Laporan penjualan -----------------------------------------------

    public function test_sales_report_aggregates_by_period_month_and_apartment(): void
    {
        $this->closing('2026-01-10', 'Easton', 300000000, 30000000);
        $this->closing('2026-01-20', 'Easton', 200000000, 10000000, 'selesai', 1000000);
        $this->closing('2026-02-05', 'Melati', 100000000, 5000000);
        $this->closing('2026-02-06', 'Melati', 999000000, 99000000, 'batal');
        $this->closing('2025-12-31', 'Easton', 50000000, 1000000); // di luar periode

        $this->actingAs($this->admin(), 'admin');
        $res = $this->get(route('admin.sales-report.index', ['from' => '2026-01-01', 'to' => '2026-02-28']))->assertOk();

        $t = $res->viewData('total');
        $this->assertSame(3, $t['count']);
        $this->assertEquals(600000000, $t['deal']);
        $this->assertEquals(45000000, $t['commission']);
        $this->assertEquals(1000000, $t['other']);
        $this->assertEquals(554000000, $t['net']);
        $this->assertEquals(7.5, $t['rate']);

        $months = $res->viewData('byMonth');
        $this->assertSame(['2026-01', '2026-02'], $months->pluck('label')->all());
        $this->assertSame(2, $months[0]['count']);

        $apts = $res->viewData('byApartment');
        $this->assertSame('Easton', $apts[0]['label']);
        $this->assertEquals(500000000, $apts[0]['deal']);

        $withCancelled = $this->get(route('admin.sales-report.index', ['from' => '2026-01-01', 'to' => '2026-02-28', 'include_cancelled' => 1]))->viewData('total');
        $this->assertSame(4, $withCancelled['count']);
    }

    public function test_sales_report_empty_period_validation_export_and_auth(): void
    {
        $this->get(route('admin.sales-report.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.sales-report.export'))->assertRedirect(route('admin.login'));

        $this->actingAs($this->admin(), 'admin');
        $this->get(route('admin.sales-report.index', ['from' => '2030-01-01', 'to' => '2030-01-31']))->assertOk()->assertSee('Tidak ada closing');
        $this->get(route('admin.sales-report.index', ['from' => '2026-02-01', 'to' => '2026-01-01']))->assertSessionHasErrors('to');

        $c = $this->closing('2026-03-01', 'Easton', 100000000, 5000000);
        $res = $this->get(route('admin.sales-report.export', ['from' => '2026-03-01', 'to' => '2026-03-31']));
        $res->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $res->streamedContent();
        $this->assertStringContainsString('"Jumlah Closing",1', $csv);
        $this->assertStringContainsString('Rekap per Bulan', $csv);
        $this->assertStringContainsString($c->number(), $csv);
    }
}
