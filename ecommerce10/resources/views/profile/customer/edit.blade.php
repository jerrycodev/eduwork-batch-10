<x-storefront-layout>
    <div class="container">
    <x-ui.page-header eyebrow="Akun Saya" title="Profil Saya" description="Kelola informasi akun dan keamanan Anda." />

    <div class="space-y-5 py-6 sm:py-8">
        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6 lg:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form', ['profileUpdateRoute' => $profileUpdateRoute])
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6 lg:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-gray-900">Alamat Pengiriman</h2>
                    <p class="mt-1 text-sm text-gray-500">Kelola beberapa alamat untuk checkout lebih cepat.</p>
                </div>
                <a href="{{ route('addresses.index') }}" class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700">Kelola alamat</a>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6 lg:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6 lg:p-8">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form', ['profileDestroyRoute' => $profileDestroyRoute])
            </div>
        </section>
    </div>
    </div>
</x-storefront-layout>
