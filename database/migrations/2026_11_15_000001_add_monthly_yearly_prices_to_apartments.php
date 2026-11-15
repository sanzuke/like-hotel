<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Harga sewa bulanan dan tahunan (opsional), ditampilkan di detail unit.
        Schema::table('apartments', function (Blueprint $table) {
            $table->decimal('price_per_month', 12, 2)->nullable()->after('price_per_night');
            $table->decimal('price_per_year', 14, 2)->nullable()->after('price_per_month');
        });
    }

    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn(['price_per_month', 'price_per_year']);
        });
    }
};
