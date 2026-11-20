<x-storefront-layout>
    <div class="container max-w-3xl"><a href="{{ route('addresses.index') }}"
            class="text-sm font-medium text-primary-600 hover:text-primary-700">← Kembali ke alamat</a>
        <h1 class="mt-3 text-2xl font-bold text-gray-900">Tambah Alamat</h1>
        <p class="mt-1 text-sm text-gray-500">Lengkapi detail alamat pengiriman.</p>
        <form action="{{ route('addresses.store') }}" method="POST" x-data="{ form: $form('post', '{{ route('addresses.store') }}', { label: @js(old('label', '')), recipient_name: @js(old('recipient_name', '')), phone: @js(old('phone', '')), address_line: @js(old('address_line', '')), province_code: @js(old('province_code', '')), regency_code: @js(old('regency_code', '')), district_code: @js(old('district_code', '')), village_code: @js(old('village_code', '')), postal_code: @js(old('postal_code', '')), country: 'Indonesia', is_default: @js((bool) old('is_default', false)) }).setValidationTimeout(500) }"
            class="mt-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">@csrf
            @include('addresses._form')</form>
    </div>
</x-storefront-layout>
