<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_requests', function (Blueprint $table) {
            $table->dateTime('survey_at')->nullable()->after('status');
            $table->decimal('agreed_price', 15, 2)->nullable()->after('expected_price');
            // Unit dijual hasil publikasi lead ini (mencegah publikasi ganda).
            $table->foreignId('sale_unit_id')->nullable()->after('status')->constrained('sale_units')->nullOnDelete();
        });

        Schema::create('sale_request_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_request_id')->constrained('sale_requests')->cascadeOnDelete();
            // catatan | follow up | survey | foto unit | negosiasi | sistem
            $table->string('type', 30)->default('catatan');
            $table->text('body');
            $table->string('author', 150)->nullable();
            $table->timestamps();

            $table->index(['sale_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_request_notes');

        Schema::table('sale_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_unit_id');
            $table->dropColumn(['survey_at', 'agreed_price']);
        });
    }
};
