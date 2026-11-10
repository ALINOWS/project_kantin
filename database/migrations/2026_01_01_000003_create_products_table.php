<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchant_profiles')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('kategori')->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('image')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->unique(['merchant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
