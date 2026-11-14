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

        // Setiap produk memiliki URL gambar utama sendiri, bukan gambar bersama kategori.
        $images = [
            'Sony WH-1000XM4 Wireless Headphones' => 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?q=80&w=1465&auto=format&fit=crop',
            'Bose QuietComfort Ultra' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=1465&auto=format&fit=crop',
            'Audio-Technica ATH-M50xBT2' => 'https://images.unsplash.com/photo-1593078166035-9b9e6c7c3a15?q=80&w=1465&auto=format&fit=crop',
            'Sennheiser HD 450BT Wireless' => 'https://images.unsplash.com/photo-1484704849700-f032a568e944?q=80&w=1465&auto=format&fit=crop',
            'Apple AirPods Pro 2nd Gen' => 'https://images.unsplash.com/photo-1603351154351-5e2d0600bb77?q=80&w=1465&auto=format&fit=crop',
            'Samsung Galaxy Buds3 Pro' => 'https://images.unsplash.com/photo-1608156639585-b3a032ef9689?q=80&w=1465&auto=format&fit=crop',
            'Marshall Motif II A.N.C.' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?q=80&w=1465&auto=format&fit=crop',
            'JBL Charge 6' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?q=80&w=1465&auto=format&fit=crop',
            'Marshall Emberton III' => 'https://images.unsplash.com/photo-1605648916361-9bc12ad6a569?q=80&w=1465&auto=format&fit=crop',
            'Sonos Roam 2' => 'https://images.unsplash.com/photo-1589003077984-894e133dabab?q=80&w=1465&auto=format&fit=crop',
            'Apple Watch Series 10' => 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?q=80&w=1465&auto=format&fit=crop',
            'Samsung Galaxy Watch7' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=1465&auto=format&fit=crop',
            'Garmin Forerunner 165' => 'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?q=80&w=1465&auto=format&fit=crop',
            'Keychron K8 Pro' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?q=80&w=1465&auto=format&fit=crop',
            'Logitech MX Mechanical' => 'https://images.unsplash.com/photo-1595225476474-87563907a212?q=80&w=1465&auto=format&fit=crop',
            'Royal Kludge RK84' => 'https://images.unsplash.com/photo-1595044426077-d36d9236d54a?q=80&w=1465&auto=format&fit=crop',
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
                        'image' => $images[$name],
                        'price' => $price,
                        'stock' => 10,
                        'weight_gram' => $categoryWeights[$categorySlug],
                        'category_id' => $category->id,
                    ],
                );

                $product->update([
                    'image' => $images[$name],
                    'weight_gram' => $categoryWeights[$categorySlug],
                ]);

                // Keep the seeded product's own primary image in the gallery table.
                $product->images()->update(['is_primary' => false]);
                $product->images()->updateOrCreate(
                    ['sort_order' => 0],
                    ['path' => $images[$name], 'is_primary' => true],
                );
            }
        }
    }
}
