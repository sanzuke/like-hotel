<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tanggal yang ditutup owner (tidak bisa dibooking). date_to eksklusif, sama seperti checkout.
        Schema::create('apartment_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained('apartments')->cascadeOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->string('note', 150)->nullable();
            $table->timestamps();
            $table->index(['apartment_id', 'date_from', 'date_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartment_blocks');
    }
};
