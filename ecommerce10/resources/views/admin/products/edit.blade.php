<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Update Product') }}
            </h2>
            <a href="{{ route('products.index') }}" class="btn" data-variant="secondary">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                {{ __('Go to Products') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 sm:px-6 lg:px-8">
            <div class="bg-white p-4 shadow sm:rounded-lg">
                @include('admin.products._form', [
                    'action' => route('products.update', $product),
                    'submitLabel' => 'Update Product',
                    'product' => $product,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
