<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Categories') }}
            </h2>
            <a href="{{ route('product-categories.create') }}" class="btn" data-variant="secondary">
                <i data-lucide="plus" class="h-4 w-4"></i>
                {{ __('Create Category') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="table">
                    <thead>
                        <tr class="table-row">
                            <th class="table-cell">Name</th>
                            <th class="table-cell">Slug</th>
                            <th class="table-cell">Total Products</th>
                            <th class="table-cell">Total Price</th>
                            <th class="table-cell">Total Stock</th>
                            <th class="table-cell" style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr class="table-row">
                                <td class="table-cell">
                                    <a href="{{ route('products.index', ['category_id' => $category->id]) }}">
                                        {{ $category->name }}
                                    </a>
                                </td>
                                <td class="table-cell">{{ $category->slug }}</td>
                                <td class="table-cell">{{ $category->products_count }}</td>
                                <td class="table-cell">Rp
                                    {{ number_format($category->products_sum_price, 0, ',', '.') }}</td>
                                <td class="table-cell">{{ $category->products_sum_stock }}</td>
                                <td class="table-cell">
                                    <div class="flex items-center gap-2 justify-end">
                                        <a href="{{ route('product-categories.show', $category) }}"
                                            class="text-indigo-400 underline">
                                            {{ __('View') }}
                                        </a>
                                        <a href="{{ route('product-categories.edit', $category) }}"
                                            class="text-indigo-400 underline">
                                            {{ __('Edit') }}
                                        </a>
                                        <form action="{{ route('product-categories.destroy', $category) }}"
                                            method="POST" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            <button type="submit" class="text-red-500 underline cursor-pointer">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
