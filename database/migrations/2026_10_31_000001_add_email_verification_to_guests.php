<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });

        // Akun yang daftar lewat Google sudah terverifikasi oleh Google.
        DB::table('guests')->whereNotNull('google_id')->whereNull('password')
            ->update(['email_verified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
};
