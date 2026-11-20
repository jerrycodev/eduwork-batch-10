{{-- Dokumentasi: Komponen form kategori yang dipakai untuk membuat dan mengubah kategori melalui dialog. --}}
<button id="category-dialog-button" type="button"
    class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-200"
    data-dialog-trigger="category-dialog" aria-haspopup="dialog">
    <i data-lucide="plus" class="h-4 w-4"></i>Create Category
</button>

<dialog id="category-dialog" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-gray-200 bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-gray-950/40" role="dialog" aria-modal="true" aria-labelledby="category-form-title">
    <form id="category-form" method="POST" action="{{ route('product-categories.store') }}" enctype="multipart/form-data" class="space-y-6 p-6 sm:p-7" autocomplete="off">
        @csrf
        @method('POST')
        <input type="hidden" id="category-id" name="category_id">
        <div>
            <h2 class="text-lg font-bold text-gray-900" id="category-form-title">New Category</h2>
            <p class="mt-1 text-sm text-gray-500" id="category-form-description">Create a new category</p>
        </div>
        <div class="space-y-4">
            <div class="form-field space-y-2">
                <label class="block text-sm font-medium text-gray-700" for="category-name">Category name</label>
                <input class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-primary-500 focus:ring-4 focus:ring-primary-100" id="category-name" name="name" type="text" required>
            </div>
            <div class="form-field space-y-2">
                <label class="block text-sm font-medium text-gray-700" for="category-slug">Category slug</label>
                <input class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-primary-500 focus:ring-4 focus:ring-primary-100" id="category-slug" name="slug" type="text" required>
            </div>
            <div class="form-field space-y-2">
                <label class="block text-sm font-medium text-gray-700" for="category-image">Category image</label>
                <input class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-primary-700 hover:file:bg-primary-100" id="category-image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                <p class="text-xs text-gray-500">JPG, PNG, or WebP. Maximum 5 MB. Leave empty to keep the current image when editing.</p>
                <div id="category-image-preview-wrap" class="hidden items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-2">
                    <img id="category-image-preview" src="" alt="Category image preview" class="h-16 w-16 rounded-md object-cover">
                    <span id="category-image-preview-label" class="text-xs text-gray-500">Image preview</span>
                </div>
            </div>
        </div>
        <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
            <button type="button" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50" data-dialog-close>Cancel</button>
            <button type="submit" id="category-form-submit" class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">Save changes</button>
        </div>
    </form>
</dialog>

@push('scripts')
    @vite('resources/js/category.form.js')
@endpush
