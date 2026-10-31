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
        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
                $table->string('provider', 80)->default('static_qris');
                $table->string('provider_reference', 190)->nullable()->unique();
                $table->string('payment_method', 60)->default('QRIS');
                $table->decimal('amount', 12, 2)->unsigned();
                $table->string('status', 32)->default('pending');
                $table->string('checkout_url', 1000)->nullable();
                $table->timestamp('claimed_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('expired_at')->nullable();
                $table->timestamps();
                $table->index(['order_id', 'status'], 'idx_payments_order_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
