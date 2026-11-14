<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dukungan iklan berbayar: prioritas tampil teratas (0 = tanpa iklan, 3 = tertinggi),
        // aktif sampai ad_until (kosong = tanpa batas). Ditetapkan admin.
        Schema::table('apartments', function (Blueprint $table) {
            $table->unsignedTinyInteger('ad_priority')->default(0)->after('status');
            $table->date('ad_until')->nullable()->after('ad_priority');
        });
    }

    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn(['ad_priority', 'ad_until']);
        });
    }
};
