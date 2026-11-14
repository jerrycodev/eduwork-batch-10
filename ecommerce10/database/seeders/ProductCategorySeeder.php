<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    /**
     * Seed development product categories.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'development'])) {
            return;
        }

        $categories = [
            'Headphone',
            'Earbuds',
            'Speaker',
            'Smartwatch',
            'Keyboard',
        ];

        foreach ($categories as $name) {
            ProductCategory::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }
    }
}
