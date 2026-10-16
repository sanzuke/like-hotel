<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 50)->unique();
            $table->string('invoice_number', 80)->nullable()->unique();
            // Token acak untuk akses invoice publik; kode booking saja tidak cukup.
            $table->string('access_token', 64)->unique();
            $table->foreignId('apartment_id')->constrained('apartments');
            $table->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->string('guest_name', 150);
            $table->string('guest_phone', 50);
            $table->string('guest_email', 150);
            $table->date('checkin');
            $table->date('checkout');
            $table->decimal('total_price', 12, 2);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('owner_amount', 12, 2)->default(0);
            $table->string('promo_code', 50)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->text('snap_token')->nullable();
            $table->string('payment_status', 80)->default('menunggu pembayaran');
            $table->string('booking_status', 50)->default('baru');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->mediumText('midtrans_response')->nullable();
            $table->timestamps();

            $table->index(['apartment_id', 'checkin', 'checkout']);
            $table->index('booking_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
