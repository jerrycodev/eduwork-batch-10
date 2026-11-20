<x-guest-layout>
    <form method="POST" action="{{ route('register') }}"
        x-data="{ form: $form('post', '{{ route('register') }}', { name: @js(old('name', '')), email: @js(old('email', '')), password: '', password_confirmation: '' }).setValidationTimeout(500) }">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ old('redirect_to', request()->query('redirect_to')) }}">

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" x-model="form.name" @change="form.validate('name')" :value="old('name')" required autofocus autocomplete="name" />
            <x-ui.input-error field="name" :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" x-model="form.email" @change="form.validate('email')" :value="old('email')" required autocomplete="username" />
            <x-ui.input-error field="email" :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password" x-model="form.password" @change="form.validate('password')"
                            required autocomplete="new-password" />

            <x-ui.input-error field="password" :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" x-model="form.password_confirmation" @change="form.validate('password_confirmation')" required autocomplete="new-password" />

            <x-ui.input-error field="password_confirmation" :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-5 flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a href="{{ route('login', ['redirect_to' => request()->query('redirect_to')]) }}" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                {{ __('Already registered?') }}
            </a>

            <x-ui.button type="submit" class="ms-4" :disabled="false">
                {{ __('Register') }}
            </x-ui.button>
        </div>
    </form>
</x-guest-layout>
