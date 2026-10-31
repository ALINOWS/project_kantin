<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\MerchantProfile;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'merchant_id' => MerchantProfile::factory(),
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->randomFloat(2, 1000, 100000),
            'image' => null,
            'is_available' => true,
        ];
    }
}
