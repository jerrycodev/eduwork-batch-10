<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    private $dummy_products = [
        [
            'id' => 1,
            'image' => 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?q=80&w=1465&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=NHwxMjA3fDA%3D',
            'title' => 'Sony WH-1000XM4 Wireless Headphones',
            'description' => 'Headphone premium dengan fitur Active Noise Cancelling (ANC) terbaik dan kualitas suara resolusi tinggi yang memanjakan telinga.',
            'link' => '/product/1',
            'price' => 3499000,
        ],
        [
            'id' => 2,
            'image' => 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?q=80&w=1465&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=NHwxMjA3fDA%3D',
            'title' => 'Bose QuietComfort Ultra',
            'description' => 'Menghadirkan kenyamanan legendaris berpadu dengan teknologi spatial audio untuk pengalaman mendengarkan musik yang lebih imersif.',
            'link' => '/product/2',
            'price' => 5299000,
        ],
        [
            'id' => 3,
            'image' => 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?q=80&w=1465&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=NHwxMjA3fDA%3D',
            'title' => 'Audio-Technica ATH-M50xBT2',
            'description' => 'Versi nirkabel dari headphone monitor studio legendaris, menawarkan kejernihan suara tingkat profesional dan bass yang presisi.',
            'link' => '/product/3',
            'price' => 2850000,
        ],
        [
            'id' => 4,
            'image' => 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?q=80&w=1465&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=NHwxMjA3fDA%3D',
            'title' => 'Sennheiser HD 450BT Wireless',
            'description' => 'Teman setia perjalanan Anda dengan daya tahan baterai hingga 30 jam, dukungan codec nirkabel berkualitas, dan fitur asisten suara.',
            'link' => '/product/4',
            'price' => 2199000,
        ]
    ];

    public function findAll()
    {
        return $this->dummy_products;
    }
    public function findById(int $id)
    {
        return (object) collect($this->dummy_products)->firstWhere('id', $id);
    }
}
