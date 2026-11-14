@props(['title', 'description', 'image', 'buttonText' => 'Belanja Sekarang', 'buttonLink' => '#'])

<section class="relative overflow-hidden rounded-3xl py-10 sm:py-14 lg:py-16">
    <div class="grid items-center gap-8 md:grid-cols-2 lg:gap-12">
        <div class="max-w-xl">
            <span class="mb-4 inline-flex items-center rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold tracking-wide text-indigo-700">
                PILIHAN TERBAIK UNTUKMU
            </span>
            <h1 class="text-3xl font-bold leading-tight tracking-tight text-gray-950 sm:text-4xl lg:text-5xl">
                {{ $title }}
            </h1>
            <p class="mt-4 max-w-lg text-sm leading-7 text-gray-600 sm:text-base">
                {{ $description }}
            </p>
            <a href="{{ $buttonLink }}" class="mt-7 inline-flex items-center gap-2 rounded-full bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm shadow-indigo-600/20 transition hover:-translate-y-0.5 hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                {{ $buttonText }}
                <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
            </a>
        </div>

        <div class="hidden justify-center md:flex">
            <img src="{{ $image }}" alt="Ilustrasi berbelanja" class="max-h-72 w-full max-w-sm object-contain lg:max-h-80" loading="eager">
        </div>
    </div>
</section>
