<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_requests', function (Blueprint $t) {
            $t->dateTime('survey_at')->nullable();
            $t->decimal('last_offer', 15, 2)->nullable();
            $t->unsignedBigInteger('sale_unit_id')->nullable();
            $t->unsignedBigInteger('sale_commission_id')->nullable();
        });

        // Timeline aktivitas per lead (status, catatan, survei, negosiasi, foto, publish, closing).
        Schema::create('sale_request_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_request_id')->constrained('sale_requests')->cascadeOnDelete();
            $t->string('type', 30);
            $t->text('body')->nullable();
            $t->decimal('amount', 15, 2)->nullable();
            $t->unsignedBigInteger('admin_id')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['sale_request_id', 'id']);
        });

        Schema::create('sale_request_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_request_id')->constrained('sale_requests')->cascadeOnDelete();
            $t->string('image');
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_request_photos');
        Schema::dropIfExists('sale_request_events');
        Schema::table('sale_requests', function (Blueprint $t) {
            $t->dropColumn(['survey_at', 'last_offer', 'sale_unit_id', 'sale_commission_id']);
        });
    }
};
