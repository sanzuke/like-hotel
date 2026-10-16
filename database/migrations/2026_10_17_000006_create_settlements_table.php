<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('bank_name', 100);
            $table->string('bank_account_name', 150);
            $table->string('bank_account_number', 100);
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('status', 30)->default('pending')->index(); // pending|approved|paid|rejected
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
