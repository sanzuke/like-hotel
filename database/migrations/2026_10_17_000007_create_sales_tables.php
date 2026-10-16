<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_requests', function (Blueprint $table) {
            $table->id();
            $table->string('owner_name', 150);
            $table->string('owner_phone', 50);
            $table->string('apartment_name', 150);
            $table->string('location', 150);
            $table->string('unit_type', 100)->nullable();
            $table->string('size', 50)->nullable();
            $table->decimal('expected_price', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('baru')->index();
            $table->timestamps();
        });

        Schema::create('sale_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->string('title', 150);
            $table->string('apartment_name', 150);
            $table->string('location', 150);
            $table->string('unit_type', 100)->nullable();
            $table->string('floor', 50)->nullable();
            $table->string('size', 50)->nullable();
            $table->string('certificate', 100)->nullable();
            $table->string('furnishing', 100)->nullable();
            $table->decimal('price', 15, 2);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('seller_name', 150)->nullable();
            $table->string('seller_phone', 50)->nullable();
            $table->string('status', 30)->default('aktif')->index();
            $table->timestamps();
        });

        Schema::create('sale_unit_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_unit_id')->constrained('sale_units')->cascadeOnDelete();
            $table->string('image');
            $table->timestamps();
        });

        Schema::create('sale_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_request_id')->nullable()->constrained('sale_requests')->nullOnDelete();
            $table->foreignId('sale_unit_id')->nullable()->constrained('sale_units')->nullOnDelete();
            $table->string('seller_name', 150)->nullable();
            $table->string('apartment_name', 150);
            $table->decimal('deal_price', 15, 2);
            $table->string('commission_type', 20)->default('percent');
            $table->decimal('commission_value', 12, 2);
            $table->decimal('commission_amount', 15, 2);
            $table->decimal('other_cost', 15, 2)->default(0);
            $table->decimal('seller_net_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('closing');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_commissions');
        Schema::dropIfExists('sale_unit_images');
        Schema::dropIfExists('sale_units');
        Schema::dropIfExists('sale_requests');
    }
};
