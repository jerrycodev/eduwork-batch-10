<?php

$columns = ['Image', 'Name', 'Price', 'Stock', 'Category', 'Description', 'Actions'];

?>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Products') }}
            </h2>
            <a href="{{ route('products.create') }}" class="btn" data-variant="secondary">
                <i data-lucide="plus" class="h-4 w-4"></i>
                {{ __('Create Product') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col gap-4">
            <form action="{{ route('products.index') }}" method="GET">
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-4">
                    <input type="search" name="search" placeholder="Search" class="input"
                        value="{{ request('search') }}">
                    <select name="category_id" class="select max-w-50" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach ($categories as $category)
                            <option
                                value="{{ $category->id }}"{{ request('category_id') == $category->id ? ' selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="order" class="select max-w-50" onchange="this.form.submit()">
                        <option value="">Order by</option>
                        <option value="name" {{ request('order') == 'name' ? ' selected' : '' }}>Name A-Z</option>
                        <option value="name_desc" {{ request('order') == 'name_desc' ? ' selected' : '' }}>Name Z-A
                        </option>
                        <option value="price" {{ request('order') == 'price' ? ' selected' : '' }}>Price Low-High
                        </option>
                        <option value="price_desc" {{ request('order') == 'price_desc' ? ' selected' : '' }}>Price
                            High-Low</option>
                        <option value="stock" {{ request('order') == 'stock' ? ' selected' : '' }}>Stock Low-High
                        </option>
                        <option value="stock_desc" {{ request('order') == 'stock_desc' ? ' selected' : '' }}>Stock
                            High-Low</option>
                    </select>
                    @if (request()->has('search') || request()->has('category_id') || request()->has('order'))
                        {{-- Clear Filters --}}
                        <button type="button" class="btn bg-indigo-400"
                            onclick="window.location.replace('{{ route('products.index') }}')">
                            <i data-lucide="trash" class="h-4 w-4"></i>
                        </button>
                    @endif
                    <button type="submit" class="btn bg-indigo-400">
                        Filter
                    </button>
                </div>
            </form>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg divide-y divide-neutral-200">
                <table class="table ">
                    <thead>
                        <tr class="table-row">
                            {{-- <th class="table-cell">#</th>
                                <th class="table-cell">Name</th>
                                <th class="table-cell" style="text-align:right">Action</th> --}}
                            @foreach ($columns as $column)
                                <th class="table-cell">{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        {{-- <tr class="table-row">
                                <td class="table-cell">1</td>
                                <td class="table-cell">John</td>
                                <td class="table-cell" style="text-align:right">Edit</td>
                            </tr> --}}
                        @foreach ($products as $product)
                            <tr class="table-row">
                                <td class="table-cell">
                                    <img src="{{ $product->image }}" alt="{{ $product->name }}"
                                        class="h-10 w-10 rounded-full object-cover">
                                </td>
                                <td class="table-cell">{{ $product->name }}</td>
                                <td class="table-cell whitespace-nowrap">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </td>
                                <td class="table-cell">{{ $product->stock }}</td>
                                <td class="table-cell">{{ $product->category->name }}</td>
                                <td class="table-cell">
                                    <div class="truncate max-w-100">{{ $product->description }}</div>
                                </td>
                                <td class="table-cell" style="text-align:right">
                                    <div class="flex gap-2 items-center">
                                        <a href="{{ route('products.show', $product) }}"
                                            class="text-indigo-400 underline">
                                            {{ __('View') }}
                                        </a>
                                        <a href="{{ route('products.edit', $product) }}"
                                            class="text-indigo-400 underline">
                                            {{ __('Edit') }}
                                        </a>
                                        <form action="{{ route('products.destroy', $product) }}" method="POST"
                                            onsubmit="return confirm('Are you sure?')">
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
                    {{-- <tfoot>
                            <tr class="table-row">
                                <td class="table-cell" colspan="2">Total</td>
                                <td class="table-cell" style="text-align:right">1</td>
                            </tr>
                        </tfoot> --}}
                </table>
                @if ($products->hasPages())
                    <div class="p-4">
                        {{ $products->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
