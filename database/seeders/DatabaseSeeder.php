<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /** Membuat akun admin awal dari .env — tanpa password hard-coded. */
    public function run(): void
    {
        $email = env('ADMIN_SEED_EMAIL', 'admin@likehotelsewa.com');

        if (Admin::where('email', $email)->exists()) {
            $this->command?->info("Admin {$email} sudah ada, dilewati.");

            return;
        }

        $password = env('ADMIN_SEED_PASSWORD') ?: Str::password(14, symbols: false);

        Admin::create([
            'name' => env('ADMIN_SEED_NAME', 'Admin Like Hotel'),
            'username' => env('ADMIN_SEED_USERNAME', 'admin'),
            'email' => $email,
            'password' => $password,
            'status' => 'aktif',
        ]);

        $this->command?->info("Admin dibuat. Email: {$email}");

        if (! env('ADMIN_SEED_PASSWORD')) {
            $this->command?->warn("Password acak: {$password}  (simpan sekarang, tidak ditampilkan lagi)");
        }
    }
}
