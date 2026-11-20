<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-slate-900">
    @admin
        {{-- Alpine mengontrol drawer navigasi pada layar kecil; sidebar desktop tetap selalu terlihat. --}}
        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-slate-50">
            <div class="flex min-h-screen">
                {{-- Desktop sidebar --}}
                <div class="hidden lg:fixed lg:inset-y-0 lg:z-30 lg:flex">
                    @include('layouts.admin-sidebar')
                </div>

                {{-- Mobile sidebar --}}
                <div x-cloak x-show="sidebarOpen" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Admin navigation">
                    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 bg-slate-950/60" @click="sidebarOpen = false"></div>
                    <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex h-full w-72 max-w-[85vw]">
                        @include('layouts.admin-sidebar')
                        <button type="button" @click="sidebarOpen = false" class="absolute right-3 top-4 rounded-lg p-2 text-slate-400 hover:bg-white/10 hover:text-white" aria-label="Close navigation">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>

                <div class="flex min-w-0 flex-1 flex-col lg:pl-72">
                    {{-- Admin top bar --}}
                    <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 shadow-sm backdrop-blur sm:px-6 lg:px-8">
                        <div class="flex min-w-0 items-center gap-3">
                            <button type="button" @click="sidebarOpen = true" class="rounded-xl border border-slate-200 p-2 text-slate-600 transition hover:bg-slate-50 lg:hidden" aria-label="Open navigation">
                                <i data-lucide="menu" class="h-5 w-5"></i>
                            </button>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800">Admin panel</p>
                                <p class="hidden text-xs text-slate-500 sm:block">Manage your store in one place</p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2 sm:gap-4">
                            <x-notification-link />
                            <div class="hidden h-7 w-px bg-slate-200 sm:block"></div>
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center gap-2 rounded-xl px-2 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-500/30">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-teal-50 text-xs font-bold text-teal-700">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </span>
                                        <span class="hidden max-w-36 truncate sm:block">{{ Auth::user()->name }}</span>
                                        <i data-lucide="chevron-down" class="hidden h-4 w-4 text-slate-400 sm:block"></i>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <div class="border-b border-slate-100 px-4 py-3">
                                        <p class="truncate text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</p>
                                    </div>
                                    <x-dropdown-link :href="route('admin.profile.edit')">
                                        {{ __('Profile') }}
                                    </x-dropdown-link>
                                    <form method="POST" action="{{ route(auth()->user()->role === 'admin' ? 'admin.logout' : 'logout') }}"
                                        x-data="adminConfirm(@js('Apakah Anda yakin ingin keluar?'))" @submit="submit($event)">
                                        @csrf
                                        <button type="submit"
                                            class="block w-full rounded-lg px-3 py-2 text-start text-sm text-slate-600 transition hover:bg-red-50 hover:text-red-700 focus:bg-red-50 focus:text-red-700 focus:outline-none">
                                            {{ __('Log Out') }}
                                        </button>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </header>

                    @isset($header)
                        <div class="border-b border-slate-200 bg-white px-4 py-5 sm:px-6 lg:px-8">
                            <div class="mx-auto w-full max-w-[1600px]">
                                {{ $header }}
                            </div>
                        </div>
                    @endisset

                    <main class="min-w-0 flex-1">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    @else
        <div class="storefront-shell flex min-h-screen flex-col bg-gray-50">
            @include('components.storefront.header')
            @isset($header)
                <div class="container w-full pt-6">{{ $header }}</div>
            @endisset
            <main class="w-full flex-1 py-5 sm:py-8">{{ $slot }}</main>
            @include('components.storefront.footer')
        </div>
    @endadmin

    @stack('scripts')
</body>

</html>
