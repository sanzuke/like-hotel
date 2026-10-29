<?php

namespace Tests;

use App\Models\Guest;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Login sebagai penyewa; booking wajib akun. */
    protected function loginGuest(array $over = []): Guest
    {
        $guest = Guest::create($over + ['name' => 'Tamu Uji', 'email' => 'tamu@example.com', 'phone' => '081234567890', 'password' => 'password123']);
        $this->actingAs($guest, 'guest');

        return $guest;
    }
}
