<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create Product') }}
            </h2>
            <a href="{{ route('products.index') }}" class="btn" data-variant="secondary">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                {{ __('Go to Products') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 lg:py-10">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-4 shadow sm:p-6 lg:p-8">
                @include('admin.products._form', [
                    'action' => route('products.store'),
                    'submitLabel' => 'Create Product',
                    'product' => null,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
