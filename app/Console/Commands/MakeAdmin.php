<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeAdmin extends Command
{
    protected $signature = 'likehotel:admin
        {username=admin : Username admin}
        {--name= : Nama tampilan (hanya untuk admin baru)}
        {--email= : Email (hanya untuk admin baru)}
        {--password= : Password; kosongkan agar dibuatkan acak}';

    protected $description = 'Buat admin baru atau reset password admin yang sudah ada';

    public function handle(): int
    {
        $password = $this->option('password') ?: Str::password(14, symbols: false);

        if (strlen($password) < 8) {
            $this->error('Password minimal 8 karakter.');

            return self::FAILURE;
        }

        $admin = Admin::where('username', $this->argument('username'))->first();

        if ($admin) {
            $admin->update(['password' => $password, 'status' => 'aktif']);
            $this->info("Password admin \"{$admin->username}\" direset.");
        } else {
            $admin = Admin::create([
                'username' => $this->argument('username'),
                'name' => $this->option('name') ?: 'Admin Like Hotel',
                'email' => $this->option('email') ?: null,
                'password' => $password,
                'status' => 'aktif',
            ]);
            $this->info("Admin \"{$admin->username}\" dibuat.");
        }

        $this->line("Username : {$admin->username}");
        $this->line("Password : {$password}");

        return self::SUCCESS;
    }
}
