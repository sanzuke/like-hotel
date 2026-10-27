<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Owner;
use App\Support\Fmt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DateFormatTest extends TestCase
{
    use RefreshDatabase;

    public function test_fmt_date_helpers_use_dd_mmm_yyyy(): void
    {
        $this->assertSame('27 Okt 2026', Fmt::date('2026-10-27'));
        $this->assertSame('5 Mei 2026', Fmt::date(Carbon::parse('2026-05-05')));
        $this->assertSame('1 Des 2026 02:55', Fmt::dateTime(Carbon::parse('2026-12-01 02:55:09')));
        $this->assertSame('1 Des 2026 02:55:09', Fmt::dateTime(Carbon::parse('2026-12-01 02:55:09'), true));
        $this->assertSame('-', Fmt::date(null));
    }

    public function test_invoice_and_booking_notify_show_readable_dates_not_iso(): void
    {
        $owner = Owner::create(['name' => 'O', 'phone' => '0812', 'email' => 'o@x.com', 'password' => 'password123', 'status' => 'aktif']);
        $apt = Apartment::create([
            'owner_id' => $owner->id, 'owner_name' => 'O', 'name' => 'Easton', 'location' => 'Jatinangor', 'type' => 'Studio',
            'price_per_night' => 300000, 'facilities' => 'AC', 'status' => 'aktif',
        ]);

        $this->post(route('booking.store', $apt), [
            'guest_name' => 'Tamu', 'guest_phone' => '081234567890', 'guest_email' => 't@x.com',
            'checkin' => '2026-10-27', 'checkout' => '2026-10-31',
        ])->assertRedirect();

        $b = Booking::firstOrFail();
        foreach ([route('invoice.show', $b->access_token), route('booking.notify', $b->access_token)] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('27 Okt 2026', $html);
            $this->assertStringContainsString('31 Okt 2026', $html);
            $this->assertStringNotContainsString('2026-10-27', $html);
        }
    }
}
