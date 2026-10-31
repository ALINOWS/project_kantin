<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('qr_codes')) {
            Schema::create('qr_codes', function (Blueprint $table) {
                $table->id();
                $table->string('token', 128)->unique();
                $table->string('type', 32)->default('merchant_menu');
                $table->foreignId('merchant_id')->nullable()->constrained('merchant_profiles')->nullOnDelete();
                $table->string('target_url', 500);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('is_active', 'idx_qr_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
