<button id="category-dialog-button" type="button" class="btn" data-variant="secondary"
    data-dialog-trigger="category-dialog" aria-haspopup="dialog">
    <i data-lucide="plus" class="h-4 w-4"></i>
    Create Category
</button>

<dialog id="category-dialog" class="dialog" role="dialog" aria-modal="true" aria-labelledby="category-form-title">
    <form id="category-form" method="POST" action="{{ route('product-categories.store') }}" class="dialog-content"
        autocomplete="off">
        @csrf
        @method('POST')
        <input type="hidden" id="category-id" name="category_id">
        <div class="dialog-header text-center">
            <h2 class="dialog-title" id="category-form-title">New Category</h2>
            <p class="dialog-description" id="category-form-description">Create a new category</p>
        </div>
        <div class="dialog-body">
            <div class="form-group">
                <div class="form-field">
                    <label class="label" for="category-name">Category Name</label>
                    <input class="input" id="category-name" name="name" type="text" required>
                </div>
                <div class="form-field">
                    <label class="label" for="category-slug">Category Slug</label>
                    <input class="input" id="category-slug" name="slug" type="text" required>
                </div>
            </div>
        </div>
        <div class="dialog-footer">
            <button type="button" class="btn" data-variant="outline" data-dialog-close>Cancel</button>
            <button type="submit" id="category-form-submit" class="btn bg-indigo-400" data-variant="default">
                Save changes
            </button>
        </div>
    </form>
</dialog>

@push('scripts')
    @vite('resources/js/category.form.js')
@endpush
