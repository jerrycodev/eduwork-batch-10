<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create Product') }}
            </h2>
            <a href="{{ route('products.index') }}" class="btn" data-variant="secondary">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                {{ __('Go to Products') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col gap-4">
            <div class="bg-white p-4 shadow sm:rounded-lg">
                @include('admin.products._form', [
                    'action' => route('products.store'),
                    'submitLabel' => 'Create Product',
                    'product' => null,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
