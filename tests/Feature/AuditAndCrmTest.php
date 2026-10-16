<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\SaleRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditAndCrmTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create(['name' => 'Budi Admin', 'username' => 'adm', 'password' => 'password123']);
    }

    private function lead(): SaleRequest
    {
        $this->post(route('sale-request.store'), [
            'owner_name' => 'Pak Fahri', 'owner_phone' => '081234567890', 'apartment_name' => 'Easton Park',
            'location' => 'Jatinangor', 'unit_type' => 'Studio', 'size' => '24 m2', 'expected_price' => 350000000,
        ])->assertRedirect();

        return SaleRequest::firstOrFail();
    }

    public function test_booking_lifecycle_is_audited_with_actor(): void
    {
        config(['likehotel.midtrans.server_key' => 'k']);
        $a = Apartment::create(['owner_name' => 'Like Hotel', 'name' => 'U', 'location' => 'L', 'type' => 'S', 'price_per_night' => 100000, 'status' => 'aktif']);

        $this->post(route('booking.store', $a), [
            'guest_name' => 'Tamu', 'guest_phone' => '081234567890', 'guest_email' => 't@x.id',
            'checkin' => now()->addDay()->toDateString(), 'checkout' => now()->addDays(2)->toDateString(),
        ]);
        $b = Booking::firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['action' => 'booking.created', 'actor_type' => 'guest', 'subject_id' => $b->id]);

        $n = ['order_id' => $b->booking_code, 'status_code' => '200', 'gross_amount' => '100000.00', 'transaction_status' => 'settlement'];
        $n['signature_key'] = hash('sha512', $n['order_id'].$n['status_code'].$n['gross_amount'].'k');
        $this->postJson('/midtrans/notification', $n)->assertOk();
        $this->postJson('/midtrans/notification', $n)->assertOk(); // idempotent
        $this->assertSame(1, AuditLog::where('action', 'booking.paid_midtrans')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'booking.paid_midtrans', 'actor_type' => 'system']);

        $this->actingAs($this->admin(), 'admin');
        $this->post(route('admin.bookings.cancel', $b))->assertSessionHas('error');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'booking.cancelled_manual']);
    }

    public function test_failed_admin_login_and_admin_actions_are_audited(): void
    {
        $this->admin();
        $this->post(route('admin.login.submit'), ['login' => 'adm', 'password' => 'salah'])->assertSessionHasErrors('login');
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.login_failed', 'actor_type' => 'guest']);

        $this->post(route('admin.login.submit'), ['login' => 'adm', 'password' => 'password123'])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.login', 'actor_name' => 'Budi Admin']);

        $this->get(route('admin.audit-logs.index'))->assertOk()->assertSee('admin.login');
        $this->get(route('admin.audit-logs.index', ['actor_type' => 'guest', 'q' => 'adm']))->assertOk();
    }

    public function test_crm_full_workflow(): void
    {
        $lead = $this->lead();
        $this->assertSame('baru', $lead->status);
        $this->assertSame(1, $lead->timeline()->count());

        $this->actingAs($this->admin(), 'admin');
        $this->get(route('admin.sale-requests.show', $lead))->assertOk()->assertSee('Pak Fahri');

        // Catatan negosiasi + harga disepakati.
        $this->post(route('admin.sale-requests.notes', $lead), ['type' => 'negosiasi', 'body' => 'Sepakat turun', 'agreed_price' => 325000000])->assertRedirect();
        $this->assertEquals(325000000, $lead->fresh()->agreed_price);

        // Jadwal survei harus di masa depan dan memindahkan status ke survey.
        $this->post(route('admin.sale-requests.survey', $lead), ['survey_at' => now()->subDay()->toDateTimeString()])->assertSessionHasErrors('survey_at');
        $this->post(route('admin.sale-requests.survey', $lead), ['survey_at' => now()->addDays(2)->format('Y-m-d\TH:i')])->assertRedirect();
        $this->assertSame('survey', $lead->fresh()->status);

        // Status tidak valid ditolak.
        $this->patch(route('admin.sale-requests.status', $lead), ['status' => 'ngawur'])->assertSessionHasErrors('status');

        // Publikasi menjadi draf nonaktif, hanya sekali.
        $this->post(route('admin.sale-requests.publish', $lead))->assertRedirect();
        $lead->refresh();
        $this->assertNotNull($lead->sale_unit_id);
        $this->assertSame('nonaktif', $lead->saleUnit->status);
        $this->assertEquals(325000000, $lead->saleUnit->price);
        $this->assertSame('listing', $lead->status);
        $this->post(route('admin.sale-requests.publish', $lead))->assertSessionHas('error');
        $this->get(route('sale-units.show', $lead->saleUnit))->assertNotFound(); // draf belum tampil publik

        // Closing: komisi 2,5% dari 325 jt, unit jadi terjual, tidak bisa ganda.
        $this->post(route('admin.sale-requests.close', $lead), ['deal_price' => 320000000, 'commission_type' => 'percent', 'commission_value' => 2.5, 'other_cost' => 1000000])->assertRedirect();
        $c = $lead->fresh()->commission()->firstOrFail();
        $this->assertEquals(8000000, $c->commission_amount);
        $this->assertEquals(311000000, $c->seller_net_amount);
        $this->assertSame('terjual', $lead->fresh()->saleUnit->status);
        $this->assertSame('closing', $lead->fresh()->status);
        $this->post(route('admin.sale-requests.close', $lead), ['deal_price' => 1, 'commission_type' => 'fixed', 'commission_value' => 0])->assertSessionHas('error');

        $this->assertDatabaseHas('audit_logs', ['action' => 'crm.published']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'sale_commission.created']);
        $this->get(route('admin.sale-requests.show', $lead))->assertOk()->assertSee('Closing dicatat');
    }
}
