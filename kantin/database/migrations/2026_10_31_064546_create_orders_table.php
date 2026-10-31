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
        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number', 40)->unique();
                $table->foreignId('user_id')->constrained()->restrictOnDelete();
                $table->decimal('subtotal', 12, 2)->unsigned();
                $table->decimal('total', 12, 2)->unsigned();
                $table->string('order_status', 32)->default('pending_payment')->index();
                $table->string('payment_status', 32)->default('pending')->index();
                $table->string('pickup_code', 32)->nullable()->unique();
                $table->string('notes', 500)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('paid_claimed_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->index('created_at', 'idx_orders_created');
                $table->index(['order_status', 'payment_status'], 'idx_orders_status');
                $table->index('user_id', 'idx_orders_user');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
