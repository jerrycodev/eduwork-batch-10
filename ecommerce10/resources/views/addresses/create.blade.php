<x-layout-component>
    <div class="mx-auto max-w-7xl"><a href="{{ route('addresses.index') }}"
            class="text-sm font-medium text-indigo-600 hover:text-indigo-700">← Kembali ke alamat</a>
        <h1 class="mt-3 text-2xl font-bold text-gray-900">Tambah Alamat</h1>
        <p class="mt-1 text-sm text-gray-500">Lengkapi detail alamat pengiriman.</p>
        <form action="{{ route('addresses.store') }}" method="POST"
            class="mt-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">@csrf
            @include('addresses._form')</form>
    </div>
</x-layout-component>
