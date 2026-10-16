<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_requests', function (Blueprint $table) {
            // Penawaran pembeli terakhir.
            $table->decimal('last_offer', 15, 2)->nullable()->after('agreed_price');
        });

        Schema::table('sale_request_notes', function (Blueprint $table) {
            // Nominal untuk entri penawaran / publikasi / closing.
            $table->decimal('amount', 15, 2)->nullable()->after('body');
        });

        Schema::create('sale_request_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_request_id')->constrained('sale_requests')->cascadeOnDelete();
            $table->string('image');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_request_photos');

        Schema::table('sale_request_notes', function (Blueprint $table) {
            $table->dropColumn('amount');
        });

        Schema::table('sale_requests', function (Blueprint $table) {
            $table->dropColumn('last_offer');
        });
    }
};
