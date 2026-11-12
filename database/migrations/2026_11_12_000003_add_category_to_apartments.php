<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kategori unit: "Hotel" atau "Apartemen". Tipe (Studio, 1 Bedroom, dst.)
        // tetap disimpan di kolom type.
        Schema::table('apartments', function (Blueprint $table) {
            $table->string('category', 20)->default('apartment')->after('type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
