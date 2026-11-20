<x-guest-layout>
    <div class="mb-5"><p class="text-xs font-semibold uppercase tracking-wider text-teal-600">Akun pelanggan</p><h1 class="mt-1 text-xl font-bold text-slate-900">Masuk ke RevStore</h1><p class="mt-1 text-sm text-slate-500">Gunakan akun pelanggan untuk melanjutkan belanja.</p></div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}"
        x-data="{ form: $form('post', '{{ route('login') }}', { email: @js(old('email', '')), password: '' }).setValidationTimeout(500) }">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ old('redirect_to', request()->query('redirect_to')) }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" x-model="form.email" @change="form.validate('email')" :value="old('email')" required
                autofocus autocomplete="username" />
            <x-ui.input-error field="email" :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="mt-1 block w-full"
                type="password"
                name="password" x-model="form.password" @change="form.validate('password')"
                required autocomplete="current-password" />

            <x-ui.input-error field="password" :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="mt-4 block">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-gray-600 shadow-sm focus:ring-primary-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="mt-5 flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-end">
            @if (Route::has('password.request'))
                <a class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <div class="mt-5 gap-3 grid sm:grid-cols-2">
            <x-ui.button type="submit" class="flex w-full items-center justify-center">
                {{ __('Log in') }}
            </x-ui.button>

            <a href="{{ route('register', ['redirect_to' => request()->query('redirect_to')]) }}"
                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gray-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                {{ __('Register') }}
            </a>
        </div>
    </form>
</x-guest-layout>
