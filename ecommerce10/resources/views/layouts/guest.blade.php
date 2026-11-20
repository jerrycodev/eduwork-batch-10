<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    @include('layouts.partials.storefront-head')
    <title>{{ config('app.name', 'RevStore') }}</title>
</head>

<body
    class="storefront-shell min-h-screen bg-gradient-to-br from-teal-50 via-gray-50 to-white font-sans text-gray-900 antialiased">
    <main class="flex min-h-screen flex-col items-center justify-center px-4 py-10 sm:px-6 gap-4">
        <x-logo />
        <section
            class="w-full max-w-md rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xl shadow-gray-900/5 sm:p-8">
            {{ $slot }}
        </section>
        <p class="mt-6 text-center text-xs text-gray-400">© {{ now()->year }} {{ config('app.name', 'RevStore') }}</p>
    </main>
</body>

</html>
