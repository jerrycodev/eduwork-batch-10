<x-layout-component>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($products as $product)
            <x-product-card 
            :image="$product['image']" 
            :title="$product['title']" 
            :description="$product['description']" 
            :link="$product['link']"
            :price="$product['price']"
             />
        @endforeach
    </div>
</x-layout-component>
