<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MerchantProfile;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merchantProfile = MerchantProfile::query()->where('status', 'active')->first();
        $category = Category::query()->where('is_active', true)->first();

        if ($merchantProfile === null || $category === null) {
            return;
        }

        Product::query()->firstOrCreate(
            ['slug' => 'contoh-menu-kantin'],
            [
                'merchant_id' => $merchantProfile->id,
                'category_id' => $category->id,
                'name' => 'Contoh Menu Kantin',
                'description' => 'Contoh menu yang dapat disesuaikan.',
                'price' => 12000,
                'is_available' => true,
            ],
        );
    }
}
