{{-- Dokumentasi: Halaman autentikasi khusus administrator; guard admin dipisahkan dari pelanggan. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login · {{ config('app.name', 'RevStore') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-500 text-white shadow-lg shadow-teal-950/50"><i data-lucide="store" class="h-6 w-6"></i></span>
                <span><span class="block text-lg font-bold text-white">{{ config('app.name', 'RevStore') }}</span><span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-teal-400">Admin workspace</span></span>
            </a>
            <section class="rounded-3xl border border-white/10 bg-slate-900 p-7 shadow-2xl shadow-black/30 sm:p-9">
                <div class="mb-7"><span class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-teal-400/10 text-teal-300"><i data-lucide="shield-check" class="h-5 w-5"></i></span><h1 class="text-2xl font-bold text-white">Administrator sign in</h1><p class="mt-2 text-sm leading-6 text-slate-400">Masuk ke workspace untuk mengelola operasional RevStore.</p></div>
                @if (session('status'))<div class="mb-5 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-300">{{ session('status') }}</div>@endif
                <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5">
                    @csrf
                    <div><label for="email" class="mb-2 block text-sm font-medium text-slate-300">Email administrator</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white placeholder:text-slate-600 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400/20">@error('email')<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror</div>
                    <div><label for="password" class="mb-2 block text-sm font-medium text-slate-300">Password</label><input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white placeholder:text-slate-600 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400/20">@error('password')<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror</div>
                    <label class="flex items-center gap-2 text-sm text-slate-400"><input type="checkbox" name="remember" value="1" class="rounded border-slate-600 bg-slate-950 text-teal-500 focus:ring-teal-500"> Ingat saya</label>
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-500 px-4 py-3 text-sm font-bold text-slate-950 transition hover:bg-teal-400"><i data-lucide="log-in" class="h-4 w-4"></i> Masuk ke Admin</button>
                </form>
                <div class="mt-6 flex items-center justify-between border-t border-white/10 pt-5 text-sm"><a href="{{ route('admin.password.request') }}" class="text-teal-300 hover:text-teal-200">Lupa password?</a><a href="{{ route('login') }}" class="text-slate-400 hover:text-white">Login pelanggan</a></div>
            </section>
            <p class="mt-6 text-center text-xs text-slate-600">Akses khusus administrator. Seluruh aktivitas admin dicatat.</p>
        </div>
    </main>
</body>
</html>
