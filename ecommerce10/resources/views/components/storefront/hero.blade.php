<section class="relative isolate -mt-5 overflow-hidden border-b sm:-mt-8 border-gray-100 bg-gradient-to-br from-gray-50 via-white to-primary-50/50">
    <div class="pointer-events-none absolute -right-24 -top-28 h-80 w-80 rounded-full bg-primary-100/50 blur-3xl" aria-hidden="true"></div>
    <div class="container relative grid min-h-[430px] items-center gap-8 py-12 sm:min-h-[500px] sm:py-16 md:grid-cols-[1.05fr_0.95fr] lg:gap-16 lg:py-20">
        <div class="max-w-2xl">
            <span class="inline-flex items-center gap-2 rounded-full border border-primary-100 bg-white/80 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-primary-700 shadow-sm">
                <span class="h-1.5 w-1.5 rounded-full bg-primary-500"></span>
                Selamat datang di {{ config('app.name', 'RevStore') }}
            </span>
            <h1 class="mt-5 text-4xl font-bold leading-[1.12] tracking-tight text-gray-950 sm:text-5xl lg:text-[3.5rem]">
                Temukan pilihan tepat untuk <span class="text-primary-700">kebutuhan Anda.</span>
            </h1>
            <p class="mt-5 max-w-xl text-sm leading-7 text-gray-600 sm:text-base">
                Jelajahi beragam produk pilihan, temukan yang Anda cari, dan lanjutkan belanja dengan mudah.
            </p>
            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a href="{{ route('shop') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-600/15 transition hover:-translate-y-0.5 hover:bg-primary-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">
                    Belanja Sekarang
                    <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                </a>
                <a href="#kategori" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full border border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50">
                    Jelajahi Kategori
                    <i data-lucide="arrow-down" class="h-4 w-4"></i>
                </a>
            </div>
            <div class="mt-9 flex flex-wrap items-center gap-x-6 gap-y-3 border-t border-gray-200/80 pt-5 text-xs font-medium text-gray-500 sm:text-sm">
                <span class="inline-flex items-center gap-2"><i data-lucide="layers" class="h-4 w-4 text-primary-600"></i>Beragam kategori</span>
                <span class="inline-flex items-center gap-2"><i data-lucide="shopping-bag" class="h-4 w-4 text-primary-600"></i>Belanja praktis</span>
                <span class="inline-flex items-center gap-2"><i data-lucide="package-check" class="h-4 w-4 text-primary-600"></i>Pilihan produk</span>
            </div>
        </div>

        <div class="relative mx-auto hidden w-full max-w-[480px] md:block">
            <div class="absolute inset-5 rounded-[2rem] bg-primary-100/70 blur-2xl" aria-hidden="true"></div>
            <div class="relative overflow-hidden rounded-[2rem] border border-white bg-white p-5 shadow-2xl shadow-gray-900/10 lg:p-7">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary-700">Koleksi pilihan</p>
                        <p class="mt-1 text-sm text-gray-500">Temukan inspirasi belanja Anda</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-50 text-primary-700"><i data-lucide="sparkles" class="h-5 w-5"></i></span>
                </div>
                <img src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/girl-shopping-list.svg" alt="Ilustrasi seseorang memilih produk untuk dibeli" class="mx-auto my-5 max-h-64 w-full object-contain lg:max-h-72" fetchpriority="high">
                <div class="rounded-xl bg-gray-50 px-4 py-3">
                    <p class="text-sm font-semibold text-gray-900">Belanja sesuai kebutuhan</p>
                    <p class="mt-1 text-xs leading-5 text-gray-500">Jelajahi kategori dan lihat detail setiap produk sebelum memilih.</p>
                </div>
            </div>
            <span class="absolute -bottom-4 -left-5 flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-100 bg-white text-primary-700 shadow-lg" aria-hidden="true"><i data-lucide="shopping-bag" class="h-6 w-6"></i></span>
        </div>
    </div>
</section>
