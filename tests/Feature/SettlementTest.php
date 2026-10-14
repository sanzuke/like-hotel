<?php

namespace Tests\Feature;

use App\Models\Settlement;
use App\Services\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFixtures;
use Tests\TestCase;

class SettlementTest extends TestCase
{
    use CreatesFixtures, RefreshDatabase;

    private function ownerWithBalance(float $amount = 270000)
    {
        $owner = $this->owner();
        $apt = $this->apartment($owner);
        $this->booking($apt, ['booking_status' => 'dibayar', 'owner_amount' => $amount]);
        $this->booking($apt, ['booking_status' => 'baru', 'owner_amount' => 999999]); // tidak dihitung

        return $owner;
    }

    private function bank(array $o = []): array
    {
        return $o + ['bank_name' => 'BCA', 'bank_account_name' => 'Owner', 'bank_account_number' => '123'];
    }

    public function test_balance_counts_only_paid_bookings_minus_requests(): void
    {
        $owner = $this->ownerWithBalance();
        $svc = app(SettlementService::class);
        $this->assertEquals(270000, $svc->balance($owner->id)['available']);

        Settlement::create(['owner_id' => $owner->id, 'amount' => 100000, 'status' => 'pending'] + $this->bank());
        Settlement::create(['owner_id' => $owner->id, 'amount' => 50000, 'status' => 'rejected'] + $this->bank());

        $this->assertEquals(170000, $svc->balance($owner->id)['available']);
    }

    public function test_owner_cannot_request_more_than_available(): void
    {
        $owner = $this->ownerWithBalance();

        $this->actingAs($owner, 'owner')
            ->post(route('owner.settlement.store'), $this->bank(['amount' => 270001]))
            ->assertSessionHasErrors('amount');

        $this->post(route('owner.settlement.store'), $this->bank(['amount' => 270000]))->assertSessionHasNoErrors();
        $this->assertSame(1, Settlement::count());

        // saldo sudah habis -> pengajuan kedua gagal
        $this->post(route('owner.settlement.store'), $this->bank(['amount' => 1]))->assertSessionHasErrors('amount');
    }

    public function test_paid_settlement_cannot_be_rejected_or_approved_again(): void
    {
        $owner = $this->ownerWithBalance();
        $s = Settlement::create(['owner_id' => $owner->id, 'amount' => 1000, 'status' => 'pending'] + $this->bank());
        $svc = app(SettlementService::class);

        $this->assertTrue($svc->transition($s, 'approved'));
        $this->assertTrue($svc->transition($s->fresh(), 'paid'));
        $this->assertNotNull($s->fresh()->paid_at);
        $this->assertFalse($svc->transition($s->fresh(), 'rejected'));
        $this->assertFalse($svc->transition($s->fresh(), 'approved'));
        $this->assertSame('paid', $s->fresh()->status);
    }

    public function test_admin_actions_require_post_and_work(): void
    {
        $owner = $this->ownerWithBalance();
        $s = Settlement::create(['owner_id' => $owner->id, 'amount' => 1000, 'status' => 'pending'] + $this->bank());
        $this->actingAs($this->admin(), 'admin');

        $this->get(route('admin.settlements.transition', [$s, 'paid']))->assertStatus(405);
        $this->assertSame('pending', $s->fresh()->status);

        $this->post(route('admin.settlements.transition', [$s, 'reject']))->assertSessionHas('status');
        $this->assertSame('rejected', $s->fresh()->status);

        $this->post(route('admin.settlements.pay-owner', $owner))->assertSessionHas('status');
        $paid = Settlement::where('status', 'paid')->first();
        $this->assertEquals(270000, $paid->amount);
        $this->assertEquals(0, app(SettlementService::class)->balance($owner->id)['available']);

        $this->post(route('admin.settlements.pay-owner', $owner))->assertSessionHas('error');
    }
}
