<button id="category-dialog-button" type="button"
    class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200"
    data-dialog-trigger="category-dialog" aria-haspopup="dialog">
    <i data-lucide="plus" class="h-4 w-4"></i>Create Category
</button>

<dialog id="category-dialog" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-gray-200 bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-gray-950/40" role="dialog" aria-modal="true" aria-labelledby="category-form-title">
    <form id="category-form" method="POST" action="{{ route('product-categories.store') }}" class="space-y-6 p-6 sm:p-7" autocomplete="off">
        @csrf
        @method('POST')
        <input type="hidden" id="category-id" name="category_id">
        <div><h2 class="text-lg font-bold text-gray-900" id="category-form-title">New Category</h2><p class="mt-1 text-sm text-gray-500" id="category-form-description">Create a new category</p></div>
        <div class="space-y-4">
            <div class="space-y-2"><label class="block text-sm font-medium text-gray-700" for="category-name">Category name</label><input class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" id="category-name" name="name" type="text" required></div>
            <div class="space-y-2"><label class="block text-sm font-medium text-gray-700" for="category-slug">Category slug</label><input class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" id="category-slug" name="slug" type="text" required></div>
        </div>
        <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
            <button type="button" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50" data-dialog-close>Cancel</button>
            <button type="submit" id="category-form-submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Save changes</button>
        </div>
    </form>
</dialog>

@push('scripts')
    @vite('resources/js/category.form.js')
@endpush
