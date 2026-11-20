
@props([
    'addresses',
    'name' => 'address_id',
    'selected' => null,
    'placeholder' => 'Pilih alamat pengiriman',
])

@php
    $defaultAddress = $addresses->firstWhere('is_default', true)
        ?? $addresses->first();

    $selectedId = old($name, $selected ?? $defaultAddress?->id);
@endphp

<div
    x-data="addressDropdown(@js($addresses->map(fn ($address) => [
        'id' => (string) $address->id,
        'label' => $address->label,
        'recipient' => $address->recipient_name,
        'address' => $address->formattedAddress(),
        'isDefault' => (bool) $address->is_default,
    ])->values()), @js((string) $selectedId), @js($placeholder))"
    class="relative w-full"
>
    {{-- Input --}}
    <input
        id="{{ $name === 'address_id' ? 'checkout-address' : $name }}"
        type="hidden"
        name="{{ $name }}"
        :value="selected"
        required
    >

    {{-- Selected Address --}}
    <button
        type="button"
        @click="open = !open"
        class="flex w-full items-center justify-between gap-3 rounded-xl border border-gray-300 bg-white p-4 text-left transition hover:border-primary-500 focus:outline-none focus:ring-4 focus:ring-primary-100"
    >
        <div class="flex min-w-0 items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 10.5 12 3l9 7.5V21H3V10.5Z"/>
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 21v-7h6v7"/>
                </svg>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="text-sm font-semibold text-gray-900"
                        x-text="selectedAddress?.label ?? @js($placeholder)"
                    ></span>

                    <span
                        x-show="selectedAddress?.isDefault"
                        class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700"
                    >
                        Utama
                    </span>
                </div>

                <p
                    class="mt-1 text-sm font-medium text-gray-700"
                    x-text="selectedAddress?.recipient ?? ''"
                ></p>

                <p
                    class="mt-1 text-xs leading-relaxed text-gray-500"
                    x-text="selectedAddress?.address ?? ''"
                ></p>
            </div>
        </div>

        <svg
            class="h-5 w-5 shrink-0 text-gray-400 transition-transform"
            :class="{ 'rotate-180': open }"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >
            <path stroke-linecap="round"
                stroke-linejoin="round"
                d="m6 9 6 6 6-6"/>
        </svg>
    </button>

    {{-- Dropdown --}}
    <div
        x-show="open"
        x-transition.origin.top
        @click.outside="open = false"
        @keydown.escape.window="open = false"
        style="display: none"
        class="absolute left-0 right-0 z-30 mt-2 max-h-80 space-y-2 overflow-y-auto rounded-xl border border-gray-200 bg-white p-2 shadow-xl"
    >
        <template x-for="address in addresses" :key="address.id">
            <button
                type="button"
                @click="choose(address)"
                class="flex w-full items-start gap-3 rounded-lg border p-3 text-left transition"
                :class="selected === address.id
                    ? 'border-primary-500 bg-primary-50'
                    : 'border-transparent hover:border-gray-200 hover:bg-gray-50'"
            >
                {{-- Radio --}}
                <div
                    class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border"
                    :class="selected === address.id
                        ? 'border-primary-600 bg-primary-600'
                        : 'border-gray-300 bg-white'"
                >
                    <svg
                        x-show="selected === address.id"
                        class="h-3 w-3 text-white"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path fill-rule="evenodd"
                            d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.26a1 1 0 0 1-1.42.003L3.29 9.19a1 1 0 0 1 1.42-1.407l4.09 4.13 6.49-6.547a1 1 0 0 1 1.414-.076Z"
                            clip-rule="evenodd"/>
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="text-sm font-semibold text-gray-900"
                            x-text="address.label"
                        ></span>

                        <span
                            x-show="address.isDefault"
                            class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700"
                        >
                            Utama
                        </span>
                    </div>

                    <p
                        class="mt-1 text-sm font-medium text-gray-700"
                        x-text="address.recipient"
                    ></p>

                    <p
                        class="mt-1 text-xs leading-relaxed text-gray-500"
                        x-text="address.address"
                    ></p>
                </div>
            </button>
        </template>

        <div
            x-show="addresses.length === 0"
            class="p-4 text-center text-sm text-gray-500"
        >
            Belum ada alamat tersimpan.
        </div>
    </div>
</div>
