<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $description ?? 'Temukan produk pilihan dengan pengalaman belanja yang mudah dan nyaman.' }}">
    <meta name="theme-color" content="#4f46e5">
    <title>{{ $title ?? config('app.name', 'Ecommerce') }}</title>
    @vite(['resources/css/app.css'])
    @stack('head')
    @stack('styles')
</head>
<body class="min-h-screen bg-white font-sans text-gray-900 antialiased">
    @include('components.header')
    <main class="mx-auto min-h-[calc(100vh-5rem)] w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-9 lg:px-8">
        {{ $slot }}
    </main>
    @include('components.footer')
    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>
</html>
