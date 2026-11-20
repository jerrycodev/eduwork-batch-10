@php
    // This file is included with @include, so use prefixed variables to avoid
    // accidentally inheriting generic variables (for example $value) from the parent view.
    $uploadId = $id;
    $uploadLabel = $label;
    $uploadName = $name;
    $uploadAccept = $accept;
    $uploadHelpText = $helpText;
    $uploadMaxSize = $maxSize;
    $uploadMultiple = $multiple;
    $uploadMode = $mode;
    $uploadUrl = $uploadUrl ?? null;
    $deleteUrl = $deleteUrl ?? null;
    $uploadValue = $value ?? '';
    $uploadPreviewUrl = $previewUrl ?? null;
    $uploadPreviewName = $previewName ?? null;
    $uploadHiddenName = $hiddenName ?? null;

    $filePath = $uploadValue ? parse_url($uploadValue, PHP_URL_PATH) : null;
    $fileName = $uploadPreviewName ?: ($filePath ? basename($filePath) : null);
    $fileExtension = $fileName ? strtoupper(pathinfo($fileName, PATHINFO_EXTENSION)) : null;
    $isImage = in_array(strtolower($fileExtension ?? ''), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
@endphp

<div id="{{ $uploadId }}" class="flex flex-col gap-4" data-file-upload
    data-upload-mode="{{ $uploadMode }}"
    data-upload-url="{{ $uploadUrl }}"
    data-delete-url="{{ $deleteUrl }}"
    data-max-size="{{ (int) $uploadMaxSize * 1024 * 1024 }}"
    data-image-max-size="{{ (int) $imageMaxSize * 1024 * 1024 }}"
    data-video-max-size="{{ (int) $videoMaxSize * 1024 * 1024 }}"
    data-multiple="{{ $uploadMultiple ? 'true' : 'false' }}">
    <label for="{{ $uploadId }}-input" class="block text-sm font-medium text-gray-700">{{ $uploadLabel }}</label>
    <div class="flex w-full items-center justify-center">
        <label for="{{ $uploadId }}-input" data-file-upload-dropzone
            class="flex min-h-48 w-full cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-primary-300 bg-primary-50/50 px-5 py-8 text-center transition hover:border-primary-500 hover:bg-primary-50 focus-within:ring-2 focus-within:ring-primary-200">
            <svg class="mb-4 h-9 w-9 text-primary-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 16V4m0 0L8 8m4-4 4 4M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3" />
            </svg>
            <p class="mb-2 text-sm text-gray-700"><span class="font-semibold text-primary-600">Click to upload</span> or drag and drop</p>
            <p class="text-xs text-gray-500">{{ $uploadHelpText }} (MAX. {{ $uploadMaxSize }} MB{{ $uploadMultiple ? ' per file' : '' }})</p>
            <input id="{{ $uploadId }}-input" type="file" name="{{ $uploadMode === 'deferred' ? $uploadName : '' }}"
                class="sr-only" accept="{{ $uploadAccept }}" {{ $uploadMultiple ? 'multiple' : '' }}
                data-file-upload-input-file>
        </label>
    </div>

    <p data-file-upload-status class="hidden text-sm text-gray-500" role="status"></p>
    <p data-file-upload-error class="hidden text-sm text-red-600" role="alert"></p>

    <div data-file-upload-preview class="flex flex-wrap gap-4">
        @if ($uploadPreviewUrl || $uploadValue)
            <div data-file-preview-card data-file-existing data-file-path="{{ $uploadValue }}"
                class="group relative w-36 overflow-hidden rounded-2xl border border-neutral-200 bg-white p-2 shadow-sm">
                <div class="flex aspect-square items-center justify-center overflow-hidden rounded-xl bg-neutral-100">
                    @if ($isImage && $uploadPreviewUrl)
                        <img src="{{ $uploadPreviewUrl }}" alt="{{ $fileName }}" class="h-full w-full object-cover">
                    @else
                        <span class="text-xs font-bold text-gray-500">{{ $fileExtension ?: 'FILE' }}</span>
                    @endif
                </div>
                <div class="px-1 pb-1 pt-3">
                    <p class="truncate text-sm font-semibold text-neutral-900">{{ $fileName ?: 'Current file' }}</p>
                    <p class="mt-1 truncate text-xs font-medium uppercase tracking-wide text-neutral-500">{{ $fileExtension ? $fileExtension.' · ' : '' }}Current image</p>
                </div>
                <button type="button" data-file-delete-existing aria-label="Remove current file"
                    class="absolute right-3 top-3 flex size-7 items-center justify-center rounded-full bg-white/90 text-red-500 shadow transition hover:bg-white focus-visible:ring-2 focus-visible:ring-red-400">
                    <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" /></svg>
                </button>
            </div>
        @endif
    </div>

    @if ($uploadHiddenName)
        <input type="hidden" name="{{ $uploadHiddenName }}" data-file-upload-hidden value="{{ $uploadValue }}">
    @endif

    <template data-file-upload-template>
        <div data-file-preview-card class="group relative w-36 overflow-hidden rounded-2xl border border-neutral-200 bg-white p-2 shadow-sm">
            <div class="flex aspect-square items-center justify-center overflow-hidden rounded-xl bg-neutral-100">
                <img data-file-preview-image class="hidden h-full w-full object-cover" alt="">
                <span data-file-preview-file class="hidden text-xs font-bold text-gray-500"></span>
            </div>
            <div class="px-1 pb-1 pt-3">
                <p data-file-name class="truncate text-sm font-semibold text-neutral-900"></p>
                <p data-file-meta class="mt-1 truncate text-xs font-medium uppercase tracking-wide text-neutral-500"></p>
            </div>
            <button type="button" data-file-delete aria-label="Remove selected file" class="absolute right-3 top-3 flex size-7 items-center justify-center rounded-full bg-white/90 text-red-500 shadow transition hover:bg-white focus-visible:ring-2 focus-visible:ring-red-400">
                <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" /></svg>
            </button>
        </div>
    </template>
</div>

@once
    @push('scripts')
        @vite('resources/js/file_upload.form.js')
    @endpush
@endonce
