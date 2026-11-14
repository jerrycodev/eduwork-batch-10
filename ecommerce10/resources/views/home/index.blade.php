<x-layout-component>

    {{-- Hero Section --}}
    @include('components.hero', [
        'title' => 'Selamat Datang di Toko Online Kami',
        'description' =>
            'Temukan berbagai produk berkualitas dengan harga terbaik. Belanja sekarang dan nikmati pengalaman berbelanja yang menyenangkan!',
        'image' => 'https://flowbite.s3.amazonaws.com/blocks/e-commerce/girl-shopping-list.svg',
        'buttonText' => 'Belanja Sekarang',
        'buttonLink' => route('shop')
    ])

    {{-- Categories --}}
    <x-categories :categories="$categories" />

    {{-- List Products --}}
    <div id="produk" class="grid scroll-mt-6 grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-4">
        @foreach ($products as $product)
            <x-product-card :product="$product" :image="$product->image" :title="$product->name" :description="$product->description" :link="route('product-detail', $product)" :price="$product->price" />
        @endforeach
    </div>
    <div class="py-5 sm:py-6">
        {{ $products->links() }}
    </div>
</x-layout-component>
