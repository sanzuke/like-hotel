<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Promo;
use App\Models\SaleCommission;
use App\Models\SaleRequest;
use App\Models\SaleUnit;
use App\Models\Settlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFixtures;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use CreatesFixtures, RefreshDatabase;

    public function test_panel_pages_redirect_guests_to_their_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
        $this->get('/admin/bookings')->assertRedirect(route('admin.login'));
        $this->get('/owner/dashboard')->assertRedirect(route('owner.login'));
        $this->post('/admin/promos', [])->assertRedirect(route('admin.login'));
    }

    public function test_owner_session_cannot_open_admin_area(): void
    {
        $this->actingAs($this->owner(), 'owner')->get('/admin/dashboard')->assertRedirect(route('admin.login'));
    }

    public function test_admin_session_cannot_open_owner_area(): void
    {
        $this->actingAs($this->admin(), 'admin')->get('/owner/dashboard')->assertRedirect(route('owner.login'));
    }

    public function test_admin_login_uses_database_hash_and_rejects_bad_password(): void
    {
        $admin = $this->admin();

        $this->post('/admin/login', ['login' => $admin->email, 'password' => 'salah'])->assertSessionHasErrors('login');
        $this->post('/admin/login', ['login' => 'admin@likehotelsewa.com', 'password' => 'admin123'])->assertSessionHasErrors('login');
        $this->post('/admin/login', ['login' => $admin->username, 'password' => 'secret-pass-1'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNotNull($admin->fresh()->last_login_at);
    }

    public function test_new_owner_is_pending_and_cannot_login_until_approved(): void
    {
        $this->post('/owner/register', [
            'name' => 'Baru', 'phone' => '0811', 'email' => 'baru@owner.test', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('owner.login'));

        $owner = Owner::where('email', 'baru@owner.test')->firstOrFail();
        $this->assertSame('pending', $owner->status);

        $this->post('/owner/login', ['email' => 'baru@owner.test', 'password' => 'password123'])->assertSessionHasErrors('email');
        $this->assertGuest('owner');

        $this->actingAs($this->admin(), 'admin')->post(route('admin.owners.status', $owner), ['status' => 'aktif'])->assertSessionHas('status');
        auth('admin')->logout();

        $this->post('/owner/login', ['email' => 'baru@owner.test', 'password' => 'password123'])->assertRedirect(route('owner.dashboard'));
        $this->assertAuthenticatedAs($owner->fresh(), 'owner');
    }

    public function test_duplicate_owner_email_gets_friendly_error_and_weak_password_rejected(): void
    {
        $this->owner(['email' => 'dup@owner.test']);

        $this->post('/owner/register', ['name' => 'X', 'phone' => '1', 'email' => 'dup@owner.test', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertSessionHasErrors(['email' => 'Email ini sudah terdaftar. Silakan login.']);
        $this->post('/owner/register', ['name' => 'X', 'phone' => '1', 'email' => 'new@owner.test', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
    }

    public function test_state_changing_admin_routes_reject_get(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $b = $this->booking($this->apartment());
        $p = Promo::create(['code' => 'X1', 'type' => 'fixed', 'value' => 1000, 'status' => 'aktif']);

        $this->get("/admin/bookings/{$b->id}/paid")->assertStatus(405);
        $this->get("/admin/bookings/{$b->id}/cancel")->assertStatus(405);
        $this->get("/admin/promos/{$p->id}/toggle")->assertStatus(405);
        $this->get("/admin/promos/{$p->id}")->assertStatus(405);
        $this->assertSame('baru', $b->fresh()->booking_status);
        $this->assertSame(1, Promo::count());
    }

    public function test_csrf_is_enforced_on_admin_mutations_but_not_on_midtrans_callback(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $b = $this->booking($this->apartment());

        // Middleware CSRF dilewati saat environment "testing"; simulasikan produksi untuk pengecekan ini.
        $this->app['env'] = 'production';

        $this->post("/admin/bookings/{$b->id}/paid")->assertStatus(419);
        $this->assertSame('baru', $b->fresh()->booking_status);

        $this->postJson('/midtrans/notification', ['order_id' => 'X'])->assertStatus(403); // bukan 419
    }

    public function test_admin_marks_paid_and_cancels_with_audit_log(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $b = $this->booking($this->apartment());

        $this->post(route('admin.bookings.paid', $b))->assertSessionHas('status');
        $this->assertSame('dibayar', $b->fresh()->booking_status);
        $this->post(route('admin.bookings.paid', $b))->assertSessionHas('error');

        $this->post(route('admin.bookings.cancel', $b));
        $this->assertSame('batal', $b->fresh()->booking_status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'booking.mark_paid', 'subject_id' => $b->id, 'actor_type' => 'admin']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'booking.cancel', 'subject_id' => $b->id]);
    }

    public function test_every_admin_and_owner_page_renders(): void
    {
        $owner = $this->owner();
        $apt = $this->apartment($owner);
        $b = $this->booking($apt, ['booking_status' => 'dibayar', 'payment_status' => 'lunas']);
        SaleUnit::create(['title' => 'T', 'apartment_name' => 'A', 'location' => 'L', 'price' => 1000, 'status' => 'aktif']);
        SaleRequest::create(['owner_name' => 'P', 'owner_phone' => '0812', 'apartment_name' => 'A', 'location' => 'L', 'status' => 'baru']);
        $sc = SaleCommission::create(['seller_name' => 'S', 'apartment_name' => 'A', 'deal_price' => 1000, 'commission_type' => 'percent', 'commission_value' => 10, 'commission_amount' => 100, 'seller_net_amount' => 900]);
        Settlement::create(['owner_id' => $owner->id, 'amount' => 1, 'bank_name' => 'b', 'bank_account_name' => 'n', 'bank_account_number' => '1']);
        $unit = SaleUnit::first();

        $this->actingAs($this->admin(), 'admin');
        foreach (['dashboard', 'apartments', 'owners', 'bookings', 'commission', 'reports', 'settlements', 'promos', 'sale-units', 'sale-requests', 'sale-commissions'] as $r) {
            $this->get(route('admin.'.$r))->assertOk();
        }
        $this->get(route('admin.apartments.edit', $apt))->assertOk();
        $this->get(route('admin.sale-units.edit', $unit))->assertOk();
        $this->get(route('admin.sale-commissions.edit', $sc))->assertOk();
        $this->get(route('admin.sale-commissions.invoice', $sc))->assertOk()->assertSee('CLS00001');
        $this->get(route('admin.reports.export'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($owner, 'owner');
        foreach (['dashboard', 'apartments', 'bookings', 'settlement'] as $r) {
            $this->get(route('owner.'.$r))->assertOk();
        }

        foreach ([route('home'), route('apartments.index'), route('apartments.show', $apt), route('booking.create', $apt), route('invoice.show', $b),
            route('booking.notify', $b), route('payment.show', $this->booking($apt)), route('sale.index'), route('sale.show', $unit), route('sale.request'), route('booking.lookup')] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_sale_request_form_and_commission_math(): void
    {
        $this->post(route('sale.request.store'), [
            'owner_name' => 'Fahri', 'owner_phone' => '0812', 'apartment_name' => 'Easton', 'location' => 'Jatinangor', 'expected_price' => 350000000,
        ])->assertRedirect(route('sale.request'));
        $this->assertDatabaseHas('sale_requests', ['owner_name' => 'Fahri', 'status' => 'baru']);

        $this->actingAs($this->admin(), 'admin');
        $this->post(route('admin.sale-commissions.store'), [
            'seller_name' => 'Test', 'apartment_name' => 'Easton', 'deal_price' => 325000000, 'commission_type' => 'percent', 'commission_value' => 15,
        ]);
        $c = SaleCommission::firstOrFail();
        $this->assertEquals(48750000, $c->commission_amount);
        $this->assertEquals(276250000, $c->seller_net_amount); // sama dengan data di sistem lama
    }
}
