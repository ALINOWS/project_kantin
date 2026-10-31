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
        if (! Schema::hasTable('merchant_profiles')) {
            Schema::create('merchant_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()
                    ->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
                $table->string('stall_name', 150);
                $table->text('description')->nullable();
                $table->json('opening_hours')->nullable();
                $table->string('qris_image')->nullable();
                $table->string('status', 20)->default('pending')->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_profiles');
    }
};
