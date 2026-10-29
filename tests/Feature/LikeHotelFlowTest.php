<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Owner;
use App\Models\Promo;
use App\Models\Settlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeHotelFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginGuest();
        config(['likehotel.midtrans.server_key' => 'test-server-key']);
    }

    private function owner(string $email = 'o@x.id', string $status = 'aktif'): Owner
    {
        return Owner::create(['name' => 'Owner A', 'phone' => '081', 'email' => $email, 'password' => 'password123', 'status' => $status]);
    }

    private function admin(): Admin
    {
        return Admin::create(['name' => 'A', 'username' => 'adm', 'password' => 'password123']);
    }

    private function apartment(?Owner $owner = null): Apartment
    {
        return Apartment::create([
            'owner_id' => $owner?->id, 'owner_name' => $owner?->name ?? 'Like Hotel', 'name' => 'Unit Uji',
            'location' => 'Bandung', 'type' => 'Studio', 'price_per_night' => 300000, 'status' => 'aktif',
        ]);
    }

    private function payload(array $over = []): array
    {
        return $over + [
            'guest_name' => 'Tamu Uji', 'guest_phone' => '081234567890', 'guest_email' => 'tamu@example.com',
            'checkin' => now()->addDays(5)->toDateString(), 'checkout' => now()->addDays(7)->toDateString(),
        ];
    }

    private function makeBooking(Apartment $a): Booking
    {
        $this->post(route('booking.store', $a), $this->payload())->assertRedirect();

        return Booking::latest('id')->firstOrFail();
    }

    private function notification(Booking $b, array $over = []): array
    {
        $n = $over + [
            'order_id' => $b->booking_code,
            'status_code' => '200',
            'gross_amount' => number_format((float) $b->total_price, 2, '.', ''),
            'transaction_status' => 'settlement',
        ];

        if (! array_key_exists('signature_key', $n)) {
            $n['signature_key'] = hash('sha512', $n['order_id'].$n['status_code'].$n['gross_amount'].'test-server-key');
        }

        return $n;
    }

    public function test_booking_calculates_price_commission_and_blocks_overlap(): void
    {
        $owner = $this->owner();
        $a = $this->apartment($owner);

        $b = $this->makeBooking($a);
        $this->assertEquals(600000, $b->total_price);
        $this->assertEquals(60000, $b->commission_amount);
        $this->assertEquals(540000, $b->owner_amount);
        $this->assertSame($owner->id, $b->owner_id);

        $this->post(route('booking.store', $a), $this->payload([
            'checkin' => now()->addDays(6)->toDateString(), 'checkout' => now()->addDays(8)->toDateString(),
        ]))->assertSessionHasErrors('checkin');
        $this->assertSame(1, Booking::count());

        // Check-in tepat di hari check-out booking sebelumnya diperbolehkan.
        $this->post(route('booking.store', $a), $this->payload([
            'checkin' => now()->addDays(7)->toDateString(), 'checkout' => now()->addDays(8)->toDateString(),
        ]))->assertRedirect();
        $this->assertSame(2, Booking::count());
    }

    public function test_invalid_dates_and_promo(): void
    {
        $a = $this->apartment();
        $this->post(route('booking.store', $a), $this->payload(['checkout' => now()->addDays(5)->toDateString()]))->assertSessionHasErrors('checkout');
        $this->post(route('booking.store', $a), $this->payload(['checkin' => now()->subDay()->toDateString()]))->assertSessionHasErrors('checkin');
        $this->post(route('booking.store', $a), $this->payload(['promo_code' => 'NGAWUR']))->assertSessionHasErrors('promo_code');
        $this->assertSame(0, Booking::count());

        Promo::create(['code' => 'HEMAT10', 'type' => 'percent', 'value' => 10]);
        $this->post(route('booking.store', $a), $this->payload(['promo_code' => 'hemat10']))->assertRedirect();
        $b = Booking::firstOrFail();
        $this->assertEquals(540000, $b->total_price);
        $this->assertEquals(60000, $b->discount_amount);
    }

    public function test_invoice_requires_token_and_check_requires_phone(): void
    {
        $b = $this->makeBooking($this->apartment());

        $this->get('/b/'.$b->booking_code.'/invoice')->assertNotFound();
        $this->get(route('invoice.show', $b->access_token))->assertOk()->assertSee($b->booking_code);

        $this->post(route('booking.check.submit'), ['code' => $b->booking_code, 'phone' => '089999999999'])->assertSessionHasErrors('code');
        $this->post(route('booking.check.submit'), ['code' => $b->booking_code, 'phone' => '+62 812-3456-7890'])
            ->assertRedirect(route('invoice.show', $b->access_token));
    }

    public function test_payment_result_page_never_marks_paid_and_notification_is_verified(): void
    {
        $b = $this->makeBooking($this->apartment());

        $this->get(route('payment.result', [$b->access_token, 'result' => 'success']))->assertRedirect();
        $this->assertSame('baru', $b->fresh()->booking_status);

        $valid = $this->notification($b);
        $this->postJson('/midtrans/notification', array_diff_key($valid, ['signature_key' => 1]))->assertForbidden();
        $this->postJson('/midtrans/notification', ['signature_key' => ''] + $valid)->assertForbidden();
        $this->postJson('/midtrans/notification', ['signature_key' => 'salah'] + $valid)->assertForbidden();
        $this->postJson('/midtrans/notification', $this->notification($b, ['gross_amount' => '1.00']))->assertStatus(400);
        $this->postJson('/midtrans/notification', $this->notification($b, ['order_id' => 'TIDAKADA']))->assertNotFound();
        $this->assertSame('baru', $b->fresh()->booking_status);

        $this->postJson('/midtrans/notification', $valid)->assertOk();
        $this->assertSame('dibayar', $b->fresh()->booking_status);
        $this->assertNotNull($b->fresh()->paid_at);

        // Notifikasi expire susulan tidak boleh menurunkan booking yang sudah lunas.
        $this->postJson('/midtrans/notification', $this->notification($b, ['transaction_status' => 'expire']))->assertOk();
        $this->assertSame('dibayar', $b->fresh()->booking_status);
    }

    public function test_expired_pending_bookings_are_cancelled(): void
    {
        $b = $this->makeBooking($this->apartment());
        $b->update(['expires_at' => now()->subMinute()]);

        $this->artisan('likehotel:expire-bookings')->assertSuccessful();
        $this->assertSame('batal', $b->fresh()->booking_status);
    }

    public function test_settlement_flow_and_balance_rules(): void
    {
        $owner = $this->owner();
        $b = $this->makeBooking($this->apartment($owner));
        $this->postJson('/midtrans/notification', $this->notification($b))->assertOk(); // hak owner 540.000
        $this->assertEquals(540000, $owner->availableBalance());

        $bank = ['bank_name' => 'BCA', 'bank_account_name' => 'Owner A', 'bank_account_number' => '123'];

        $this->post(route('owner.settlements.store'), $bank + ['amount' => 1000])->assertRedirect(route('owner.login'));

        $this->actingAs($owner, 'owner');
        $this->post(route('owner.settlements.store'), $bank + ['amount' => 600000])->assertSessionHasErrors('amount');
        $this->post(route('owner.settlements.store'), $bank + ['amount' => 500000])->assertRedirect();
        $this->assertEquals(40000, $owner->fresh()->availableBalance());
        $this->post(route('owner.settlements.store'), $bank + ['amount' => 100000])->assertSessionHasErrors('amount');

        $s = Settlement::firstOrFail();
        $this->get(route('admin.settlements.index'))->assertRedirect(route('admin.login'));

        $this->actingAs($this->admin(), 'admin');
        $this->post(route('admin.settlements.approve', $s))->assertRedirect();
        $this->post(route('admin.settlements.paid', $s))->assertRedirect();
        $this->assertSame('paid', $s->fresh()->status);

        $this->post(route('admin.settlements.reject', $s))->assertSessionHas('error');
        $this->assertSame('paid', $s->fresh()->status);

        $this->post(route('admin.settlements.pay-owner', $owner))->assertSessionHas('success');
        $this->post(route('admin.settlements.pay-owner', $owner))->assertSessionHas('error');
        $this->assertEquals(0, $owner->fresh()->availableBalance());
        $this->assertEquals(540000, $owner->fresh()->totalPaid());
    }

    public function test_admin_pages_render_and_actions_need_post(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $b = $this->makeBooking($this->apartment());

        foreach (['dashboard', 'apartments.index', 'owners.index', 'bookings.index', 'promos.index', 'commission', 'settlements.index', 'reports.index', 'sale-units.index', 'sale-requests.index', 'sale-commissions.index'] as $r) {
            $this->get(route('admin.'.$r))->assertOk();
        }
        $this->get(route('admin.reports.export'))->assertOk();
        $this->get('/admin/bookings/'.$b->id.'/paid')->assertStatus(405);

        $this->post(route('admin.bookings.paid', $b))->assertRedirect();
        $this->assertSame('dibayar', $b->fresh()->booking_status);
        $this->post(route('admin.bookings.cancel', $b))->assertSessionHas('error');
    }

    public function test_owner_registration_pending_until_approved(): void
    {
        $reg = ['name' => 'Baru', 'phone' => '081', 'email' => 'baru@x.id', 'password' => 'password123', 'password_confirmation' => 'password123'];

        $this->post(route('owner.register.submit'), $reg)->assertRedirect(route('owner.login'));
        $this->post(route('owner.register.submit'), $reg)->assertSessionHasErrors('email');

        $this->post(route('owner.login.submit'), ['email' => 'baru@x.id', 'password' => 'password123'])->assertSessionHasErrors('email');
        $this->assertGuest('owner');

        Owner::where('email', 'baru@x.id')->update(['status' => 'aktif']);
        $this->post(route('owner.login.submit'), ['email' => 'baru@x.id', 'password' => 'password123'])->assertRedirect(route('owner.dashboard'));
        $this->assertAuthenticated('owner');
    }

    public function test_public_and_owner_pages_render(): void
    {
        $owner = $this->owner();
        $a = $this->apartment($owner);

        foreach ([route('home'), route('apartments.index'), route('apartments.show', $a), route('booking.create', $a), route('booking.check'), route('sale-units.index'), route('sale-request.create'), route('admin.login'), route('owner.login'), route('owner.register')] as $url) {
            $this->get($url)->assertOk();
        }

        $b = $this->makeBooking($a);
        $this->get(route('booking.notify', $b->access_token))->assertOk();
        $this->get(route('payment.show', $b->access_token))->assertOk();

        $this->actingAs($owner, 'owner');
        foreach (['dashboard', 'apartments.index', 'bookings.index', 'settlements.index'] as $r) {
            $this->get(route('owner.'.$r))->assertOk();
        }
    }
}
