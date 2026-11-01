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
        if (Schema::hasTable('merchant_profiles') && ! Schema::hasColumn('merchant_profiles', 'qris_image')) {
            Schema::table('merchant_profiles', function (Blueprint $table): void {
                $table->string('qris_image')->nullable();
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('orders', 'merchant_id')) {
                    $table->foreignId('merchant_id')->nullable()
                        ->constrained('merchant_profiles')->nullOnDelete();
                }
                if (! Schema::hasColumn('orders', 'unique_code')) {
                    $table->unsignedSmallInteger('unique_code')->nullable();
                }
                if (! Schema::hasColumn('orders', 'paid_claimed_at')) {
                    $table->timestamp('paid_claimed_at')->nullable();
                }
                if (! Schema::hasColumn('orders', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable();
                }
                if (! Schema::hasColumn('orders', 'pickup_code')) {
                    $table->string('pickup_code', 32)->nullable()->unique();
                }
                if (! Schema::hasColumn('orders', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable();
                }
                if (! Schema::hasColumn('orders', 'notes')) {
                    $table->string('notes', 500)->nullable();
                }
            });

            if (Schema::hasColumn('orders', 'merchant_id')
                && Schema::hasColumn('orders', 'unique_code')
                && ! Schema::hasIndex('orders', ['merchant_id', 'unique_code', 'payment_status'])) {
                Schema::table('orders', function (Blueprint $table): void {
                    $table->unique(['merchant_id', 'unique_code', 'payment_status'], 'uq_merchant_unique_payment_code');
                });
            }
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table): void {
                if (! Schema::hasColumn('payments', 'claimed_at')) {
                    $table->timestamp('claimed_at')->nullable();
                }
                if (! Schema::hasColumn('payments', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table): void {
                foreach (['claimed_at', 'expires_at'] as $column) {
                    if (Schema::hasColumn('payments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropUnique('uq_merchant_unique_payment_code');
                foreach (['merchant_id', 'unique_code', 'paid_claimed_at', 'expires_at'] as $column) {
                    if (Schema::hasColumn('orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('merchant_profiles') && Schema::hasColumn('merchant_profiles', 'qris_image')) {
            Schema::table('merchant_profiles', function (Blueprint $table): void {
                $table->dropColumn('qris_image');
            });
        }
    }
};
