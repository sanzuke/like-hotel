<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Log mentah setiap notifikasi Midtrans (audit paket B).
        Schema::create('midtrans_logs', function (Blueprint $t) {
            $t->id();
            $t->string('order_id', 80)->nullable()->index();
            $t->string('transaction_status', 40)->nullable();
            $t->string('result', 40); // processed | ignored | rejected
            $t->string('note')->nullable();
            $t->mediumText('payload')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        // Audit log perubahan status penting (booking, settlement, owner).
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->string('actor_type', 20)->nullable(); // admin | owner | system
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->string('action', 80);
            $t->string('subject_type', 40)->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->text('meta')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['subject_type', 'subject_id']);
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('midtrans_logs');
    }
};
