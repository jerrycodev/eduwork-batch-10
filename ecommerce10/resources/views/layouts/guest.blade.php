<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body data-boost="true" class="font-sans text-gray-900 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4 py-8 sm:px-6 sm:py-12">
            <div>
                <a href="/" class="flex items-center gap-4 text-2xl font-semibold">
                    <x-application-logo class="h-14 w-14 fill-current text-gray-500 sm:h-20 sm:w-20" />
                {{ config('app.name', 'Laravel') }}
                </a>
            </div>

            <div class="mt-6 w-full max-w-md overflow-hidden rounded-lg bg-white px-5 py-6 shadow-md sm:px-8 sm:py-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
