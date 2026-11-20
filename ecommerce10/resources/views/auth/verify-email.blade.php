<x-guest-layout>
    <div class="mb-4 space-y-3 text-sm text-gray-600">
        <p>
            Terima kasih sudah mendaftar di RevStore. Kami telah mengirim tautan verifikasi ke alamat email
            <strong>{{ auth('customer')->user()->email }}</strong>.
        </p>
        <p>
            Silakan buka email tersebut dan klik tombol <strong>Verifikasi Email</strong> sebelum melakukan checkout.
            Jika email belum terlihat, periksa folder spam atau kirim ulang tautan verifikasi di bawah.
        </p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 rounded-md bg-green-50 p-3 text-sm font-medium text-green-700">
            Email verifikasi berhasil dikirim. Silakan periksa kotak masuk Anda.
        </div>
    @endif

    <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                Kirim Ulang Email Verifikasi
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button type="submit" variant="ghost" size="sm" class="underline">
                Keluar
            </x-ui.button>
        </form>
    </div>
</x-guest-layout>
