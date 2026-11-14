<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Komisi sewa khusus owner (persen). NULL = ikut pengaturan komisi umum.
        Schema::table('owners', function (Blueprint $table) {
            $table->decimal('commission_percent', 5, 2)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn('commission_percent');
        });
    }
};
