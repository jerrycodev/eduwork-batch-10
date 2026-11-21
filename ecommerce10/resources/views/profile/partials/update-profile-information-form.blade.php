<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    @php($precognitive = false)

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route($profileUpdateRoute ?? 'profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10" required autofocus autocomplete="name">
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10" required autocomplete="username">
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        @if ($precognitive)
                            <x-ui.button type="submit" form="send-verification" variant="ghost" size="sm" class="underline">{{ __('Click here to re-send the verification email.') }}</x-ui.button>
                        @else
                            <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">{{ __('Click here to re-send the verification email.') }}</button>
                        @endif
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @if (($profileUpdateRoute ?? 'profile.update') === 'profile.update')
            <div>
                <x-input-label for="password" value="Password saat ini" />
                <input id="password" name="password" type="password" autocomplete="current-password" class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10" placeholder="Wajib diisi jika mengganti email">
                <p class="mt-1 text-xs text-gray-500">Diperlukan untuk memverifikasi bahwa Anda pemilik akun saat mengubah alamat email.</p>
                <x-input-error class="mt-2" :messages="$errors->get('password')" />
            </div>

            @if ($user->pending_email)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <p class="font-semibold">Menunggu konfirmasi email baru</p>
                    <p class="mt-1">Tautan konfirmasi telah dikirim ke <strong>{{ $user->pending_email }}</strong>. Email akun Anda tetap {{ $user->email }} sampai alamat baru dikonfirmasi.</p>
                    <button type="submit" form="resend-email-change-form" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-800 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2">
                        <i data-lucide="mail" class="mr-2 h-4 w-4"></i>
                        Kirim ulang email konfirmasi
                    </button>
                </div>
                @if (session('status') === 'email-change-resent')
                    <p class="text-sm font-medium text-green-700">Email konfirmasi baru berhasil dikirim. Silakan periksa inbox atau folder spam.</p>
                @elseif (session('status') === 'email-change-resend-failed')
                    <p class="text-sm font-medium text-red-700">Email konfirmasi belum dapat dikirim. Silakan coba kembali beberapa saat lagi.</p>
                @endif
            @endif
        @endif

        <div class="flex items-center gap-4">
            <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>

            @if (session('status') === 'email-change-pending')
                <p class="text-sm font-medium text-amber-700">Permintaan perubahan email tersimpan. Silakan cek inbox email baru untuk mengonfirmasi dalam 60 menit.</p>
            @elseif (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>

    @if (($profileUpdateRoute ?? 'profile.update') === 'profile.update' && $user->pending_email)
        <form id="resend-email-change-form" method="POST" action="{{ route('profile.email-change.resend') }}" class="hidden">
            @csrf
        </form>
    @endif
</section>
