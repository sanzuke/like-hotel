<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skema inti — kolom & nama tabel sama dengan database lama (likehotel_db),
 * sehingga data dari dump SQL lama dapat diimpor langsung setelah migrate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $t) {
            $t->id();
            $t->string('name', 150);
            $t->string('username', 100)->unique();
            $t->string('email', 150)->nullable()->unique();
            $t->string('password');
            $t->string('status', 30)->default('aktif');
            $t->dateTime('last_login_at')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('owners', function (Blueprint $t) {
            $t->id();
            $t->string('name', 150);
            $t->string('phone', 50);
            $t->string('email', 150)->unique();
            $t->string('password');
            $t->string('status', 30)->default('pending'); // pending | aktif | nonaktif
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('apartments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $t->string('owner_name', 150);
            $t->string('name', 180);
            $t->string('location', 180);
            $t->string('type', 80);
            $t->decimal('price_per_night', 12, 2);
            $t->text('facilities')->nullable();
            $t->string('image')->nullable();
            $t->string('status', 30)->default('aktif')->index(); // aktif | maintenance | nonaktif
            $t->timestamp('created_at')->useCurrent();
            $t->text('description')->nullable();
        });

        Schema::create('apartment_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('apartment_id')->constrained('apartments')->cascadeOnDelete();
            $t->string('image');
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('promos', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('type', 20); // percent | fixed
            $t->decimal('value', 12, 2);
            $t->string('status', 20)->default('aktif');
            $t->date('expired_at')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->string('booking_code', 50)->unique();
            $t->string('invoice_number', 80)->nullable()->unique();
            $t->string('access_token', 64)->nullable()->unique(); // token rahasia untuk URL invoice publik
            $t->foreignId('apartment_id')->constrained('apartments');
            $t->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $t->string('guest_name', 150);
            $t->string('guest_phone', 50);
            $t->string('guest_email', 150);
            $t->date('checkin');
            $t->date('checkout');
            $t->decimal('total_price', 12, 2);
            $t->decimal('commission_amount', 12, 2)->default(0);
            $t->decimal('owner_amount', 12, 2)->default(0);
            $t->text('snap_token')->nullable();
            $t->string('payment_status', 80)->default('menunggu pembayaran');
            $t->string('booking_status', 50)->default('baru'); // baru | dibayar | batal
            $t->mediumText('midtrans_response')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->string('promo_code', 50)->nullable();
            $t->decimal('discount_amount', 12, 2)->default(0);

            $t->index(['apartment_id', 'checkin', 'checkout'], 'idx_bookings_apartment_dates');
            $t->index('owner_id', 'idx_bookings_owner');
            $t->index(['booking_status', 'payment_status'], 'idx_bookings_status');
        });

        Schema::create('settlements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_id')->constrained('owners');
            $t->decimal('amount', 12, 2);
            $t->string('bank_name', 100);
            $t->string('bank_account_name', 150);
            $t->string('bank_account_number', 100);
            $t->text('notes')->nullable();
            $t->text('admin_notes')->nullable();
            $t->string('status', 30)->default('pending')->index(); // pending | approved | paid | rejected
            $t->dateTime('paid_at')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('sale_units', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('owner_id')->nullable();
            $t->string('title', 150);
            $t->string('apartment_name', 150);
            $t->string('location', 150);
            $t->string('unit_type', 100)->nullable();
            $t->string('floor', 50)->nullable();
            $t->string('size', 50)->nullable();
            $t->string('certificate', 100)->nullable();
            $t->string('furnishing', 100)->nullable();
            $t->decimal('price', 15, 2);
            $t->text('description')->nullable();
            $t->string('image')->nullable();
            $t->string('seller_name', 150)->nullable();
            $t->string('seller_phone', 50)->nullable();
            $t->string('status', 30)->default('aktif'); // aktif | terjual | nonaktif
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('sale_unit_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_unit_id')->constrained('sale_units')->cascadeOnDelete();
            $t->string('image');
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('sale_requests', function (Blueprint $t) {
            $t->id();
            $t->string('owner_name', 150);
            $t->string('owner_phone', 50);
            $t->string('apartment_name', 150);
            $t->string('location', 150);
            $t->string('unit_type', 100)->nullable();
            $t->string('size', 50)->nullable();
            $t->decimal('expected_price', 15, 2)->nullable();
            $t->text('notes')->nullable();
            $t->string('status', 30)->default('baru');
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('sale_commissions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('sale_request_id')->nullable();
            $t->unsignedBigInteger('sale_unit_id')->nullable();
            $t->string('seller_name', 150)->nullable();
            $t->string('apartment_name', 150);
            $t->decimal('deal_price', 15, 2);
            $t->string('commission_type', 20)->default('percent');
            $t->decimal('commission_value', 12, 2);
            $t->decimal('commission_amount', 15, 2);
            $t->decimal('other_cost', 15, 2)->default(0);
            $t->decimal('seller_net_amount', 15, 2);
            $t->text('notes')->nullable();
            $t->string('status', 30)->default('closing');
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['sale_commissions', 'sale_requests', 'sale_unit_images', 'sale_units', 'settlements',
            'bookings', 'promos', 'apartment_images', 'apartments', 'owners', 'admins'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
