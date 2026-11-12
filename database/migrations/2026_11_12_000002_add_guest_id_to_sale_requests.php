<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengajuan titip jual kini berasal dari akun terdaftar, bukan formulir anonim.
        Schema::table('sale_requests', function (Blueprint $table) {
            $table->foreignId('guest_id')->nullable()->after('id')->constrained('guests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guest_id');
        });
    }
};
