<x-layout-component>
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-sm font-semibold text-indigo-600">Akun Saya</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Alamat Pengiriman</h1><p class="mt-1 text-sm text-gray-500">Simpan beberapa alamat dan pilih salah satunya saat checkout.</p></div>
        <a href="{{ route('addresses.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"><i data-lucide="plus" class="h-4 w-4"></i>Tambah Alamat</a>
    </div>
    @if (session('success'))<div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if ($addresses->isEmpty())
        <section class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-6 py-14 text-center"><i data-lucide="map-pin" class="mx-auto h-10 w-10 text-gray-400"></i><h2 class="mt-4 font-semibold text-gray-900">Belum ada alamat tersimpan</h2><p class="mt-1 text-sm text-gray-500">Tambahkan alamat rumah atau kantor agar checkout lebih cepat.</p><a href="{{ route('addresses.create') }}" class="mt-5 inline-flex rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Tambah alamat pertama</a></section>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($addresses as $address)
                <article class="rounded-xl border {{ $address->is_default ? 'border-indigo-200 ring-1 ring-indigo-100' : 'border-gray-200' }} bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3"><div class="flex items-center gap-2"><h2 class="font-semibold text-gray-900">{{ $address->label }}</h2>@if ($address->is_default)<span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">Utama</span>@endif</div><i data-lucide="map-pin" class="h-5 w-5 text-gray-400"></i></div>
                    <p class="mt-3 font-medium text-gray-900">{{ $address->recipient_name }}</p><p class="mt-0.5 text-sm text-gray-600">{{ $address->phone }}</p><p class="mt-3 text-sm leading-6 text-gray-600">{{ $address->formattedAddress() }}</p>
                    <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4">
                        <a href="{{ route('addresses.edit', $address) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Edit</a>
                        @unless ($address->is_default)<form action="{{ route('addresses.default', $address) }}" method="POST">@csrf @method('PATCH')<button class="rounded-lg border border-indigo-200 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Jadikan utama</button></form>@endunless
                        <form action="{{ route('addresses.destroy', $address) }}" method="POST" onsubmit="return confirm('Hapus alamat ini?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Hapus</button></form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-layout-component>
