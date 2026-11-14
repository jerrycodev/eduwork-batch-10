<?php
$links = [
    [
        'label' => 'Dashboard',
        'route' => route('dashboard'),
        'active' => request()->routeIs('dashboard'),
    ],
    [
        'label' => 'Products',
        'route' => route('products.index'),
        'active' => request()->routeIs('products.*'),
    ],
    [
        'label' => 'Inventory',
        'route' => route('inventory.index'),
        'active' => request()->routeIs('inventory.*'),
    ],
    [
        'label' => 'Product Categories',
        'route' => route('product-categories.index'),
        'active' => request()->routeIs('product-categories.*'),
    ],
    [
        'label' => 'Reviews',
        'route' => route('reviews.index'),
        'active' => request()->routeIs('reviews.*'),
    ],
    [
        'label' => 'Orders',
        'route' => route('orders.index'),
        'active' => request()->routeIs('orders.*'),
    ],
];
?>

<nav x-data="{ open: false }" class="border-b border-gray-100 bg-white">
    <!-- Primary Navigation Menu -->
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-16 items-center justify-between gap-3">
            <div class="flex">
                <!-- Logo -->
                <div class="flex shrink-0 items-center">
                    <x-logo />
                </div>

                <!-- Navigation Links -->
                <div class="hidden items-center gap-4 sm:-my-px sm:ms-6 sm:flex lg:gap-8">
                    @admin
                        @foreach ($links as $item)
                            <x-nav-link :href="$item['route']" :active="$item['active']">
                                {{ $item['label'] }}
                            </x-nav-link>
                        @endforeach
                    @endadmin
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:ms-4 sm:flex sm:items-center lg:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button type="submit"
                                class="block w-full rounded-lg px-3 py-2 text-start text-sm text-gray-600 transition hover:bg-indigo-50 hover:text-indigo-700 focus:bg-indigo-50 focus:text-indigo-700 focus:outline-none"
                                onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                                {{ __('Log Out') }}
                            </button>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden">

        @admin
            <div class="space-y-1 px-2 pb-3 pt-2">
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')">
                    {{ __('Products') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">
                    {{ __('Inventory') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('product-categories.index')" :active="request()->routeIs('product-categories.*')">
                    {{ __('Product Categories') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reviews.index')" :active="request()->routeIs('reviews.*')">
                    {{ __('Reviews') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.index')">
                    {{ __('Orders') }}
                </x-responsive-nav-link>
            </div>
        @endadmin

        <!-- Responsive Settings Options -->
        <div class="border-t border-gray-200 px-2 pb-2 pt-4">
            <div class="px-4">
                <div class="text-base font-medium text-gray-800">{{ Auth::user()->name }}</div>
                <div class="text-sm font-medium text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit"
                        class="block w-full border-l-4 border-transparent px-3 py-2 pe-4 ps-3 text-start text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 focus:border-gray-300 focus:bg-gray-50 focus:text-gray-800 focus:outline-none">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
