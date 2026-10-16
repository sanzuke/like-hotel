<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Apartment;
use App\Models\Owner;
use App\Models\Promo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin awal. Password dari .env (SEED_ADMIN_PASSWORD) atau acak dan ditampilkan sekali.
        $password = env('SEED_ADMIN_PASSWORD') ?: Str::password(16, symbols: false);

        Admin::firstOrCreate(
            ['username' => env('SEED_ADMIN_USERNAME', 'admin')],
            [
                'name' => 'Admin Like Hotel',
                'email' => env('SEED_ADMIN_EMAIL', 'admin@likehotelsewa.com'),
                'password' => $password,
                'status' => 'aktif',
            ],
        );

        if (! env('SEED_ADMIN_PASSWORD')) {
            $this->command?->warn("Password admin yang dibuat (simpan sekarang): {$password}");
        }

        // Data demo hanya untuk lingkungan lokal.
        if (! app()->environment('local')) {
            return;
        }

        $owner = Owner::firstOrCreate(
            ['email' => 'owner@demo.com'],
            ['name' => 'Demo Owner', 'phone' => '082129248953', 'password' => Str::password(16), 'status' => 'aktif'],
        );

        if (Apartment::count() === 0) {
            Apartment::insert([
                ['owner_id' => $owner->id, 'owner_name' => $owner->name, 'name' => 'Easton Park Residence Jatinangor', 'location' => 'Jatinangor', 'type' => 'Studio', 'price_per_night' => 350000, 'facilities' => 'AC, WiFi, TV, Kolam Renang, Parkir', 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()],
                ['owner_id' => null, 'owner_name' => 'Like Hotel', 'name' => 'Taman Melati Apartment', 'location' => 'Yogyakarta', 'type' => '1 Bedroom', 'price_per_night' => 275000, 'facilities' => 'AC, WiFi, Dapur Mini', 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        Promo::firstOrCreate(['code' => 'HEMAT10'], ['type' => 'percent', 'value' => 10, 'status' => 'aktif']);
    }
}
