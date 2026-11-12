<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu akun untuk semua peran. Profil pemilik (owner) ditautkan ke akun utama ini
        // saat pengguna mengaktifkan mode pemilik, sehingga tidak perlu daftar dua kali.
        Schema::table('guests', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('password')->constrained('owners')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
        });
    }
};
