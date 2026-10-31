<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Makanan', 'slug' => 'makanan'],
            ['name' => 'Minuman', 'slug' => 'minuman'],
            ['name' => 'Camilan', 'slug' => 'camilan'],
        ] as $category) {
            Category::query()->firstOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name'], 'is_active' => true],
            );
        }
    }
}
