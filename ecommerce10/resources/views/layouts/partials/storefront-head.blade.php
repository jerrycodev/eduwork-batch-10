<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="{{ $pageDescription ?? 'Temukan produk pilihan dengan pengalaman belanja yang mudah dan nyaman.' }}">
<meta name="theme-color" content="#0f766e">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest">
<link rel="preconnect" href="https://fonts.bunny.net">
@vite(['resources/css/app.css', 'resources/js/app.js'])
