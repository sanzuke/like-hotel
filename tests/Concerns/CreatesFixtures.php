<?php

namespace Tests\Concerns;

use App\Models\Admin;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Owner;
use Illuminate\Support\Str;

trait CreatesFixtures
{
    protected function owner(array $o = []): Owner
    {
        return Owner::create($o + [
            'name' => 'Owner Test', 'phone' => '081234567890',
            'email' => Str::random(6).'@owner.test', 'password' => 'password123', 'status' => 'aktif',
        ]);
    }

    protected function admin(): Admin
    {
        return Admin::create([
            'name' => 'Admin', 'username' => 'admin'.Str::random(4), 'email' => Str::random(6).'@admin.test',
            'password' => 'secret-pass-1', 'status' => 'aktif',
        ]);
    }

    protected function apartment(?Owner $owner = null, array $o = []): Apartment
    {
        return Apartment::create($o + [
            'owner_id' => $owner?->id, 'owner_name' => $owner?->name ?? 'Like Hotel',
            'name' => 'Unit Test', 'location' => 'Jatinangor', 'type' => 'Studio',
            'price_per_night' => 300000, 'facilities' => 'AC, WiFi', 'status' => 'aktif',
        ]);
    }

    protected function booking(Apartment $apt, array $o = []): Booking
    {
        return Booking::create($o + [
            'booking_code' => 'LH'.Str::upper(Str::random(12)), 'invoice_number' => 'INV-'.Str::random(8),
            'access_token' => Str::random(40), 'apartment_id' => $apt->id, 'owner_id' => $apt->owner_id,
            'guest_name' => 'Tamu', 'guest_phone' => '082129248953', 'guest_email' => 'tamu@example.com',
            'checkin' => now()->addDays(5)->toDateString(), 'checkout' => now()->addDays(6)->toDateString(),
            'total_price' => 300000, 'commission_amount' => 30000, 'owner_amount' => 270000,
            'payment_status' => 'menunggu pembayaran', 'booking_status' => 'baru', 'discount_amount' => 0,
        ]);
    }
}
