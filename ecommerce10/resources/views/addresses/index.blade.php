<x-storefront-layout>
    <div class="container">
    <x-ui.page-header eyebrow="Akun Saya" title="Alamat Pengiriman" description="Simpan beberapa alamat dan pilih salah satunya saat checkout.">
        <x-slot:actions><x-ui.link-button :href="route('addresses.create')"><i data-lucide="plus" class="h-4 w-4"></i>Tambah Alamat</x-ui.link-button></x-slot:actions>
    </x-ui.page-header>
    @if (session('success'))<div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if ($addresses->isEmpty())
        <section class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-6 py-14 text-center"><i data-lucide="map-pin" class="mx-auto h-10 w-10 text-gray-400"></i><h2 class="mt-4 font-semibold text-gray-900">Belum ada alamat tersimpan</h2><p class="mt-1 text-sm text-gray-500">Tambahkan alamat rumah atau kantor agar checkout lebih cepat.</p><a href="{{ route('addresses.create') }}" class="mt-5 inline-flex rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white">Tambah alamat pertama</a></section>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($addresses as $address)
                <article class="rounded-xl border {{ $address->is_default ? 'border-primary-200 ring-1 ring-primary-100' : 'border-gray-200' }} bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3"><div class="flex items-center gap-2"><h2 class="font-semibold text-gray-900">{{ $address->label }}</h2>@if ($address->is_default)<span class="rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700">Utama</span>@endif</div><i data-lucide="map-pin" class="h-5 w-5 text-gray-400"></i></div>
                    <p class="mt-3 font-medium text-gray-900">{{ $address->recipient_name }}</p><p class="mt-0.5 text-sm text-gray-600">{{ $address->phone }}</p><p class="mt-3 text-sm leading-6 text-gray-600">{{ $address->formattedAddress() }}</p>
                    <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4">
                        <a href="{{ route('addresses.edit', $address) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Edit</a>
                        @unless ($address->is_default)<form action="{{ route('addresses.default', $address) }}" method="POST">@csrf @method('PATCH')<x-ui.button type="submit" variant="soft" size="xs">Jadikan utama</x-ui.button></form>@endunless
                        <form action="{{ route('addresses.destroy', $address) }}" method="POST" x-data="{}" @submit="if (!window.confirm('Hapus alamat ini?')) $event.preventDefault()">@csrf @method('DELETE')<x-ui.button type="submit" variant="danger-soft" size="xs">Hapus</x-ui.button></form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
    </div>
</x-storefront-layout>
