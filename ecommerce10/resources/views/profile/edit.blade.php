<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8 lg:py-10">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-4 shadow sm:p-6 lg:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="rounded-lg bg-white p-4 shadow sm:p-6 lg:p-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h3 class="font-semibold text-gray-900">Alamat Pengiriman</h3><p class="mt-1 text-sm text-gray-500">Kelola beberapa alamat untuk checkout lebih cepat.</p></div>
                    <a href="{{ route('addresses.index') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Kelola alamat</a>
                </div>
            </div>

            <div class="rounded-lg bg-white p-4 shadow sm:p-6 lg:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="rounded-lg bg-white p-4 shadow sm:p-6 lg:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
