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

        foreach ([
            'Atasan',
            'Bawahan',
            'Outerwear',
            'Dress',
            'Sepatu',
            'Tas',
        ] as $name) {
            ProductCategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }
    }
}
