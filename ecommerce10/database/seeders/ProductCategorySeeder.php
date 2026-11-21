<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    /**
     * Seed the fashion storefront categories used by the demo catalog.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'development'])) {
            return;
        }

        $categories = [
            [
                'name' => 'Atasan',
                'image' => 'seeders/categories/atasan.jpg',
            ],
            [
                'name' => 'Bawahan',
                'image' => 'seeders/categories/bawahan.jpg',
            ],
            [
                'name' => 'Outerwear',
                'image' => 'seeders/categories/outerwear.jpg',
            ],
            [
                'name' => 'Dress',
                'image' => 'seeders/categories/dress.jpg',
            ],
            [
                'name' => 'Sepatu',
                'image' => 'seeders/categories/sepatu.jpg',
            ],
            [
                'name' => 'Tas',
                'image' => 'seeders/categories/tas.jpg',
            ],
        ];

        foreach ($categories as $category) {
            ProductCategory::query()->updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'image' => $category['image'],
                ],
            );
        }
    }
}
