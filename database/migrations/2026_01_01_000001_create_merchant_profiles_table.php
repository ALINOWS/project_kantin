<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('stall_name');
            $table->text('description')->nullable();
            $table->string('opening_hours')->nullable();
            $table->string('qris_image')->nullable();
            $table->string('status')->default('menunggu_verifikasi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_profiles');
    }
};
