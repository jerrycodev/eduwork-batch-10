<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title ?? 'My Ecommerce' }}</title>
    <meta name="author" content="{{ $author ?? 'My Ecommerce' }}">
    <meta name="keywords" content="{{ $keywords ?? 'e-commerce, online shopping, store' }}">
    <meta name="description" content="{{ $description ?? 'My Ecommerce Site' }}">
    @vite(['resources/css/app.css'])
    @stack('head')
    @stack('styles')
</head>

<body>
    @include('components.header')
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 min-h-[calc(100vh-5rem)]">
        {{ $slot }}
    </main>
    @include('components.footer')
    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>

</html>
