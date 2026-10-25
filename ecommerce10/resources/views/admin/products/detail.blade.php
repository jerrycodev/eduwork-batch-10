{{-- blade-formatter-disable --}}
<?php
$columns = [
    ['label'=>'Name', 'value'=>$product->name],
    ['label'=>'Slug', 'value'=>$product->slug],
    ['label'=>'Category', 'value'=>$product->category->name],
    ['label'=>'Price', 'value'=> "Rp ".number_format($product->price, 0, ',', '.')],
    ['label'=>'Stock', 'value'=>$product->stock],
    ['label'=>'Description', 'value'=>$product->description],
    ['label'=>'Image', 'value'=>$product->image],
];
?>
{{-- blade-formatter-enable --}}

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Product Detail') }}
            </h2>
            <a href="{{ route('products.index') }}" class="btn" data-variant="secondary">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                {{ __('Go to Products') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 sm:px-6 lg:px-8">

            <div class="bg-white shadow sm:rounded-lg flex flex-col divide-y divide-neutral-200">

                <div class="flex flex-wrap items-center gap-4 p-4">
                    {{-- View Product on the Shop --}}
                    <a href="{{ route('product-detail', $product) }}" target="_blank" class="btn bg-indigo-400">
                        <i data-lucide="eye" class="h-4 w-4"></i>
                        View Product on the Shop
                    </a>
                    {{-- Edit Product --}}
                    <a href="{{ route('products.edit', $product) }}" class="btn bg-indigo-400">
                        <i data-lucide="pencil" class="h-4 w-4"></i>
                        Edit Product
                    </a>
                </div>

                <table class="table whitespace-nowrap">
                    <tbody>
                        @foreach ($columns as $column)
                            <tr class="table-row">
                                <td class="table-cell">{{ $column['label'] }}</td>
                                <td class="table-cell">:</td>
                                <td class="table-cell w-full">
                                     @if($column['label'] === 'Image')
                                        @if($column['value'])
                                            <img src="{{ asset($column['value']) }}" alt="{{ $product->name }}" class="h-50 w-50 rounded-lg shadow object-cover">
                                        @else
                                            <span class="text-gray-400">No Image</span>
                                        @endif
                                    @else
                                        {{ $column['value'] }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
