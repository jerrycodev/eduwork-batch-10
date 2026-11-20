<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-teal-600">Account settings</p>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900">Admin Profile</h1>
            <p class="mt-1 text-sm text-slate-500">Manage your administrator account and security settings.</p>
        </div>
    </x-slot>

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-5 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        @if (session('status') === 'profile-updated')
            <x-alert type="success">Profile information updated successfully.</x-alert>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <div class="mb-6 flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700"><i data-lucide="user-round" class="h-5 w-5"></i></span>
                <div>
                    <h2 class="font-semibold text-slate-900">Profile information</h2>
                    <p class="mt-1 text-sm text-slate-500">Update the name and email address associated with your admin account.</p>
                </div>
            </div>
            <div class="max-w-2xl">
                @include('profile.partials.update-profile-information-form', ['profileUpdateRoute' => $profileUpdateRoute])
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <div class="mb-6 flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700"><i data-lucide="shield-check" class="h-5 w-5"></i></span>
                <div>
                    <h2 class="font-semibold text-slate-900">Password and security</h2>
                    <p class="mt-1 text-sm text-slate-500">Use a strong password to protect access to the admin workspace.</p>
                </div>
            </div>
            <div class="max-w-2xl">
                @include('profile.partials.update-password-form')
            </div>
        </section>

        <section class="rounded-2xl border border-red-200 bg-white p-5 shadow-sm sm:p-7">
            <div class="mb-6 flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600"><i data-lucide="user-round-x" class="h-5 w-5"></i></span>
                <div>
                    <h2 class="font-semibold text-slate-900">Delete account</h2>
                    <p class="mt-1 text-sm text-slate-500">Permanently remove this administrator account. This action cannot be undone.</p>
                </div>
            </div>
            <div class="max-w-2xl">
                @include('profile.partials.delete-user-form', ['profileDestroyRoute' => $profileDestroyRoute])
            </div>
        </section>
    </div>
</x-app-layout>
