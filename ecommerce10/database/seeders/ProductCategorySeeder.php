<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Headphone',
            'Earbuds',
            'Speaker',
            'Smartwatch',
            'Keyboard',
        ];

        foreach ($categories as $category) {
            ProductCategory::query()->firstOrCreate(
                ['slug' => Str::slug($category)],
                ['name' => $category],
            );
        }
    }
}
