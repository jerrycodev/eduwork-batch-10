<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(ProductCategorySeeder::class);

        $categories = ProductCategory::query()->pluck('id', 'slug');

        $image = 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?q=80&w=1465&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=NHwxMjA3fDA%3D';

        $products = [
            'headphone' => [
                [
                    'title' => 'Sony WH-1000XM4 Wireless Headphones',
                    'description' => 'Headphone premium dengan fitur Active Noise Cancelling (ANC) terbaik dan kualitas suara resolusi tinggi yang memanjakan telinga.',
                    'price' => 3499000,
                ],
                [
                    'title' => 'Bose QuietComfort Ultra',
                    'description' => 'Menghadirkan kenyamanan legendaris berpadu dengan teknologi spatial audio untuk pengalaman mendengarkan musik yang lebih imersif.',
                    'price' => 5299000,
                ],
                [
                    'title' => 'Audio-Technica ATH-M50xBT2',
                    'description' => 'Versi nirkabel dari headphone monitor studio legendaris, menawarkan kejernihan suara tingkat profesional dan bass yang presisi.',
                    'price' => 2850000,
                ],
                [
                    'title' => 'Sennheiser HD 450BT Wireless',
                    'description' => 'Teman setia perjalanan Anda dengan daya tahan baterai hingga 30 jam, dukungan codec nirkabel berkualitas, dan fitur asisten suara.',
                    'price' => 2199000,
                ],
            ],
            'earbuds' => [
                [
                    'title' => 'Apple AirPods Pro 2nd Gen',
                    'description' => 'Earbuds dengan Active Noise Cancelling, mode Transparansi, dan audio spasial personal untuk pengalaman mendengarkan yang imersif.',
                    'price' => 3799000,
                ],
                [
                    'title' => 'Samsung Galaxy Buds3 Pro',
                    'description' => 'Earbuds premium dengan suara Hi-Fi 24-bit, ANC adaptif, dan desain ringan yang nyaman digunakan sepanjang hari.',
                    'price' => 2999000,
                ],
                [
                    'title' => 'Marshall Motif II A.N.C.',
                    'description' => 'Earbuds bergaya klasik Marshall dengan noise cancelling aktif dan daya tahan baterai hingga 30 jam bersama case.',
                    'price' => 3299000,
                ],
            ],
            'speaker' => [
                [
                    'title' => 'JBL Charge 6',
                    'description' => 'Speaker portabel tahan air dengan bass bertenaga dan power bank bawaan untuk menemani aktivitas di luar ruangan.',
                    'price' => 2699000,
                ],
                [
                    'title' => 'Marshall Emberton III',
                    'description' => 'Speaker Bluetooth ringkas dengan suara 360 derajat, desain ikonik, dan daya tahan baterai hingga 32 jam.',
                    'price' => 2499000,
                ],
                [
                    'title' => 'Sonos Roam 2',
                    'description' => 'Speaker portabel pintar dengan koneksi Wi-Fi dan Bluetooth, tahan air, serta dukungan kontrol suara.',
                    'price' => 3199000,
                ],
            ],
            'smartwatch' => [
                [
                    'title' => 'Apple Watch Series 10',
                    'description' => 'Smartwatch dengan layar lebih besar, pemantauan kesehatan lengkap, dan fitur keselamatan canggih untuk aktivitas sehari-hari.',
                    'price' => 5999000,
                ],
                [
                    'title' => 'Samsung Galaxy Watch7',
                    'description' => 'Smartwatch berbasis Wear OS dengan pemantauan tidur, deteksi aktivitas olahraga, dan desain premium yang ringan.',
                    'price' => 4299000,
                ],
                [
                    'title' => 'Garmin Forerunner 165',
                    'description' => 'Jam olahraga dengan GPS akurat, panduan latihan adaptif, dan pemantauan kesehatan untuk pelari pemula hingga mahir.',
                    'price' => 3899000,
                ],
            ],
            'keyboard' => [
                [
                    'title' => 'Keychron K8 Pro',
                    'description' => 'Keyboard mekanikal wireless dengan layout tenkeyless, hot-swappable switch, dan dukungan Windows serta macOS.',
                    'price' => 1899000,
                ],
                [
                    'title' => 'Logitech MX Mechanical',
                    'description' => 'Keyboard mekanikal nirkabel low-profile dengan pencahayaan pintar dan konektivitas multi-perangkat.',
                    'price' => 2299000,
                ],
                [
                    'title' => 'Royal Kludge RK84',
                    'description' => 'Keyboard mekanikal 75 persen dengan koneksi triple mode, hot-swappable, dan baterai tahan lama.',
                    'price' => 899000,
                ],
            ],
        ];

        foreach ($products as $categorySlug => $items) {
            foreach ($items as $product) {
                Product::query()->updateOrCreate(
                    ['slug' => Str::slug($product['title'])],
                    [
                        'name' => $product['title'],
                        'description' => $product['description'],
                        'image' => $image,
                        'price' => $product['price'],
                        'stock' => 10,
                        'category_id' => $categories[$categorySlug],
                    ],
                );
            }
        }
    }
}
