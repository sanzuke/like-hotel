<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Setiap notifikasi Midtrans yang masuk dicatat, termasuk yang ditolak.
        Schema::create('midtrans_logs', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 80)->nullable()->index();
            $table->string('transaction_status', 40)->nullable();
            $table->string('result', 20)->index(); // processed | ignored | rejected
            $table->string('note')->nullable();
            $table->mediumText('payload')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('midtrans_logs');
    }
};
