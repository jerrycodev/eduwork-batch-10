@php
    $image = $image ?? old('image', $product?->image);
    $imagePath = $image ? parse_url($image, PHP_URL_PATH) : null;
    $imageName = $imagePath ? basename($imagePath) : null;
    $imageExtension = $imageName ? strtoupper(pathinfo($imageName, PATHINFO_EXTENSION)) : null;
@endphp

<div id="file-upload-container" class="flex flex-col gap-4" data-file-upload
    data-upload-url="{{ route('file-uploads.store') }}" data-delete-url="{{ route('file-uploads.destroy') }}">

    {{-- Dropzone --}}
    <div class="flex w-full items-center justify-center">
        <label for="dropzone-file" data-file-upload-dropzone
            class="flex h-64 w-full cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-neutral-300 shadow-sm transition-colors hover:bg-neutral-50">
            <div class="flex flex-col items-center justify-center pb-6 pt-5">
                <svg class="mb-4 h-8 w-8" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h3a3 3 0 0 0 0-6h-.025a5.56 5.56 0 0 0 .025-.5A5.5 5.5 0 0 0 7.207 9.021C7.137 9.017 7.071 9 7 9a4 4 0 1 0 0 8h2.167M12 19v-9m0 0-2 2m2-2 2 2" />
                </svg>
                <p class="mb-2 text-sm"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                <p class="text-xs text-neutral-500">PNG, JPG, GIF or WEBP (MAX. 2 MB)</p>
            </div>
            <input id="dropzone-file" type="file" class="hidden" accept="image/png,image/jpeg,image/gif,image/webp"
                multiple>
        </label>
    </div>

    {{-- Upload status --}}
    <p data-file-upload-status class="hidden text-sm text-neutral-500"></p>
    <p data-file-upload-error class="hidden text-sm text-red-500"></p>

    {{-- Preview --}}
    <div data-file-upload-preview class="flex flex-wrap gap-4">
        @if ($image)
            <div data-file-preview-card data-file-existing
                class="group relative w-36 overflow-hidden rounded-2xl border border-neutral-200 bg-white p-2 shadow-sm">
                <div class="aspect-square overflow-hidden rounded-xl bg-neutral-100">
                    <img src="{{ $image }}" alt="{{ $imageName }}" class="h-full w-full object-cover">
                </div>
                <div class="px-1 pb-1 pt-3">
                    <p class="truncate text-sm font-semibold text-neutral-900">
                        {{ $imageName ?: 'Current image' }}
                    </p>
                    <p class="mt-1 truncate text-xs font-medium uppercase tracking-wide text-neutral-500">
                        {{ $imageExtension ? $imageExtension . ' · ' : '' }}Current image
                    </p>
                </div>
                <button type="button" data-file-delete-existing aria-label="Delete image"
                    class="absolute right-3 top-3 flex size-7 items-center justify-center rounded-full bg-white/90 text-red-500 opacity-100 shadow transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 sm:opacity-0 sm:group-hover:opacity-100">
                    <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18 17.94 6M18 18 6.06 6" />
                    </svg>
                </button>
            </div>
        @endif
    </div>

    <input type="hidden" name="image" data-file-upload-input value="{{ $image }}">

    <template data-file-upload-template>
        <div data-file-preview-card
            class="group relative w-36 overflow-hidden rounded-2xl border border-neutral-200 bg-white p-2 shadow-sm">
            <div class="aspect-square overflow-hidden rounded-xl bg-neutral-100">
                <img data-file-preview class="h-full w-full object-cover" alt="">
            </div>
            <div class="px-1 pb-1 pt-3">
                <p data-file-name class="truncate text-sm font-semibold text-neutral-900"></p>
                <p data-file-meta class="mt-1 truncate text-xs font-medium uppercase tracking-wide text-neutral-500"></p>
            </div>
            <button type="button" data-file-delete aria-label="Delete image"
                class="absolute right-3 top-3 flex size-7 items-center justify-center rounded-full bg-white/90 text-red-500 opacity-100 shadow transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 sm:opacity-0 sm:group-hover:opacity-100">
                <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18 17.94 6M18 18 6.06 6" />
                </svg>
            </button>
        </div>
    </template>
</div>

@push('scripts')
    @vite('resources/js/file_upload.form.js')
@endpush
