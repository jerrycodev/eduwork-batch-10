<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('layouts.partials.storefront-head', [
        'pageTitle' => $title ?? config('app.name', 'RevStore'),
        'pageDescription' => $description ?? null,
    ])
    <title>{{ $title ?? config('app.name', 'RevStore') }}</title>
    @stack('head')
    @stack('styles')
</head>
<body class="storefront-shell min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    <div class="flex min-h-screen flex-col">
        @include('components.storefront.header')
        <main class="w-full flex-1 py-5 sm:py-8">
            {{ $slot }}
        </main>
        @include('components.storefront.footer')
    </div>
    @stack('scripts')
</body>
</html>
