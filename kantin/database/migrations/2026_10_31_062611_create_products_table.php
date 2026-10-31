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
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('merchant_id')
                    ->constrained('merchant_profiles')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('category_id')
                    ->constrained('categories')->restrictOnDelete()->cascadeOnUpdate();
                $table->string('name', 150);
                $table->string('slug', 180)->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 12, 2)->unsigned();
                $table->string('image')->nullable();
                $table->boolean('is_available')->default(true);
                $table->timestamps();
                $table->index('is_available', 'idx_products_available');
                $table->index('category_id', 'idx_products_category');
                $table->index('merchant_id', 'idx_products_merchant');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
