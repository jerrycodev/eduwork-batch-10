<x-guest-layout>
    <div class="text-center">
        <h1 class="text-2xl font-semibold text-gray-900">
            {{ config('app.name', 'Laravel') }}
        </h1>

        <p class="mt-4 text-sm text-gray-600">
            Selamat datang. Silakan masuk atau mulai belanja.
        </p>

        <div class="mt-6 flex flex-col gap-3">
            <a href="{{ route('home') }}"
               class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700">
                Mulai Belanja
            </a>

            @auth
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Masuk
                </a>

                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Daftar
                </a>
            @endauth
        </div>
    </div>
</x-guest-layout>
