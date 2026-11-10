<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('subtotal', 12, 2);
            $table->unsignedSmallInteger('unique_code');
            $table->decimal('total', 12, 2);
            $table->string('order_status')->default('menunggu_pembayaran');
            $table->string('payment_status')->default('belum_bayar');
            $table->string('pickup_code')->unique();
            $table->timestamp('paid_claimed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
