<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class ProductSeeder extends Seeder
{
    /**
     * Seed sample products for local development and tests.
     *
     * These records are intentionally not seeded in production.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'development'])) {
            return;
        }

        $this->call(ProductCategorySeeder::class);

        $images = [
            'headphone' => 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?q=80&w=1465&auto=format&fit=crop',
            'earbuds' => 'https://images.unsplash.com/photo-1608156639585-b3a032ef9689?q=80&w=765&auto=format&fit=crop',
            'speaker' => 'https://plus.unsplash.com/premium_photo-1683141496040-eeef9702269f?q=80&w=880&auto=format&fit=crop',
            'smartwatch' => 'https://images.unsplash.com/photo-1660844817855-3ecc7ef21f12?q=80&w=786&auto=format&fit=crop',
            'keyboard' => 'https://images.unsplash.com/photo-1617096819670-6de2869bbd2e?q=80&w=627&auto=format&fit=crop',
        ];

        // Berat dibuat tetap per kategori agar hasil simulasi ongkir konsisten.
        $categoryWeights = [
            'headphone' => 350,
            'earbuds' => 80,
            'speaker' => 750,
            'smartwatch' => 100,
            'keyboard' => 1000,
        ];

        $products = [
            'headphone' => [
                ['Sony WH-1000XM4 Wireless Headphones', 'Headphone premium dengan fitur Active Noise Cancelling (ANC) dan kualitas suara resolusi tinggi.', 3499000],
                ['Bose QuietComfort Ultra', 'Menghadirkan kenyamanan berpadu dengan teknologi spatial audio untuk pengalaman mendengarkan yang imersif.', 5299000],
                ['Audio-Technica ATH-M50xBT2', 'Headphone monitor studio nirkabel dengan kejernihan suara profesional dan bass yang presisi.', 2850000],
                ['Sennheiser HD 450BT Wireless', 'Headphone perjalanan dengan daya tahan baterai hingga 30 jam dan konektivitas nirkabel.', 2199000],
            ],
            'earbuds' => [
                ['Apple AirPods Pro 2nd Gen', 'Earbuds dengan Active Noise Cancelling, mode Transparansi, dan audio spasial personal.', 3799000],
                ['Samsung Galaxy Buds3 Pro', 'Earbuds premium dengan suara Hi-Fi 24-bit, ANC adaptif, dan desain ringan.', 2999000],
                ['Marshall Motif II A.N.C.', 'Earbuds bergaya klasik dengan noise cancelling aktif dan daya tahan baterai hingga 30 jam bersama case.', 3299000],
            ],
            'speaker' => [
                ['JBL Charge 6', 'Speaker portabel tahan air dengan bass bertenaga dan power bank bawaan.', 2699000],
                ['Marshall Emberton III', 'Speaker Bluetooth ringkas dengan suara 360 derajat dan daya tahan baterai hingga 32 jam.', 2499000],
                ['Sonos Roam 2', 'Speaker portabel dengan koneksi Wi-Fi dan Bluetooth, tahan air, serta dukungan kontrol suara.', 3199000],
            ],
            'smartwatch' => [
                ['Apple Watch Series 10', 'Smartwatch dengan layar besar, pemantauan aktivitas, dan fitur keselamatan.', 5999000],
                ['Samsung Galaxy Watch7', 'Smartwatch Wear OS dengan pemantauan tidur dan aktivitas olahraga.', 4299000],
                ['Garmin Forerunner 165', 'Jam olahraga dengan GPS dan pemantauan aktivitas untuk pelari.', 3899000],
            ],
            'keyboard' => [
                ['Keychron K8 Pro', 'Keyboard mekanikal wireless layout tenkeyless dengan hot-swappable switch.', 1899000],
                ['Logitech MX Mechanical', 'Keyboard mekanikal nirkabel low-profile dengan pencahayaan pintar.', 2299000],
                ['Royal Kludge RK84', 'Keyboard mekanikal 75 persen dengan koneksi triple mode dan hot-swappable switch.', 899000],
            ],
        ];

        foreach ($products as $categorySlug => $items) {
            $category = ProductCategory::query()
                ->where('slug', $categorySlug)
                ->first();

            if (! $category) {
                throw new RuntimeException("Product category [{$categorySlug}] was not created.");
            }

            foreach ($items as [$name, $description, $price]) {
                // firstOrCreate preserves other edits; weight is synchronized for shipping tests.
                $product = Product::query()->firstOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'name' => $name,
                        'description' => $description,
                        'image' => $images[$categorySlug],
                        'price' => $price,
                        'stock' => 10,
                        'weight_gram' => $categoryWeights[$categorySlug],
                        'category_id' => $category->id,
                    ],
                );

                if ($product->weight_gram !== $categoryWeights[$categorySlug]) {
                    $product->update(['weight_gram' => $categoryWeights[$categorySlug]]);
                }
            }
        }
    }
}
