<?php

namespace Tests\Feature;

use App\Models\MidtransLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFixtures;
use Tests\TestCase;

class MidtransNotificationTest extends TestCase
{
    use CreatesFixtures, RefreshDatabase;

    private const KEY = 'SB-Mid-server-test';

    protected function setUp(): void
    {
        parent::setUp();
        config(['likehotel.midtrans.server_key' => self::KEY, 'likehotel.midtrans.client_key' => 'SB-Mid-client-test']);
    }

    private function notif($booking, array $o = []): array
    {
        $n = $o + [
            'order_id' => $booking->booking_code, 'status_code' => '200', 'gross_amount' => number_format($booking->total_price, 2, '.', ''),
            'transaction_status' => 'settlement', 'fraud_status' => 'accept',
        ];
        $n['signature_key'] = $o['signature_key'] ?? hash('sha512', $n['order_id'].$n['status_code'].$n['gross_amount'].self::KEY);

        return $n;
    }

    public function test_valid_settlement_marks_booking_paid_and_logs(): void
    {
        $b = $this->booking($this->apartment());

        $this->postJson('/midtrans/notification', $this->notif($b))->assertOk();

        $b->refresh();
        $this->assertSame('dibayar', $b->booking_status);
        $this->assertSame('lunas', $b->payment_status);
        $this->assertSame('processed', MidtransLog::first()->result);
    }

    public function test_empty_or_wrong_signature_is_rejected(): void
    {
        $b = $this->booking($this->apartment());

        $this->postJson('/midtrans/notification', $this->notif($b, ['signature_key' => '']))->assertForbidden();
        $this->postJson('/midtrans/notification', $this->notif($b, ['signature_key' => 'abc']))->assertForbidden();

        $n = $this->notif($b);
        unset($n['signature_key']);
        $this->postJson('/midtrans/notification', $n)->assertForbidden();

        $this->assertSame('baru', $b->fresh()->booking_status);
        $this->assertSame(3, MidtransLog::where('result', 'rejected')->count());
    }

    public function test_rejected_when_server_key_not_configured(): void
    {
        config(['likehotel.midtrans.server_key' => null]);
        $b = $this->booking($this->apartment());

        $this->postJson('/midtrans/notification', $this->notif($b, ['signature_key' => hash('sha512', $b->booking_code.'200'.'300000.00')]))->assertForbidden();
    }

    public function test_amount_mismatch_and_unknown_order_are_rejected(): void
    {
        $b = $this->booking($this->apartment());

        $this->postJson('/midtrans/notification', $this->notif($b, ['gross_amount' => '1000.00']))->assertStatus(400);
        $this->assertSame('baru', $b->fresh()->booking_status);

        $ghost = $this->notif($b, ['order_id' => 'LHNOPE']);
        $this->postJson('/midtrans/notification', $ghost)->assertNotFound();
    }

    public function test_late_expire_cannot_downgrade_a_paid_booking_and_is_idempotent(): void
    {
        $b = $this->booking($this->apartment());
        $this->postJson('/midtrans/notification', $this->notif($b))->assertOk();
        $this->postJson('/midtrans/notification', $this->notif($b))->assertOk();
        $this->postJson('/midtrans/notification', $this->notif($b, ['transaction_status' => 'expire']))->assertOk();

        $this->assertSame('dibayar', $b->fresh()->booking_status);
    }

    public function test_expire_cancels_unpaid_booking_and_pending_updates_status(): void
    {
        $b = $this->booking($this->apartment());
        $this->postJson('/midtrans/notification', $this->notif($b, ['transaction_status' => 'pending']))->assertOk();
        $this->assertSame('pending', $b->fresh()->payment_status);

        $this->postJson('/midtrans/notification', $this->notif($b, ['transaction_status' => 'expire']))->assertOk();
        $this->assertSame('batal', $b->fresh()->booking_status);
    }

    public function test_success_and_pending_pages_never_change_status(): void
    {
        $b = $this->booking($this->apartment());

        $this->get(route('payment.success', $b))->assertRedirect(route('invoice.show', $b));
        $this->get(route('payment.pending', $b))->assertRedirect(route('invoice.show', $b));

        $b->refresh();
        $this->assertSame('baru', $b->booking_status);
        $this->assertSame('menunggu pembayaran', $b->payment_status);
    }

    public function test_garbage_body_returns_400(): void
    {
        $this->call('POST', '/midtrans/notification', [], [], [], [], 'not json')->assertStatus(400);
    }
}
