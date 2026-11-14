<form id="product-form" action="{{ $action }}" method="POST" class="space-y-8" autocomplete="off">
    @csrf
    @if ($product)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="space-y-2">
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $product?->name) }}" required
                class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
            <input id="slug" name="slug" type="text" value="{{ old('slug', $product?->slug) }}" required
                class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('slug') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2 md:col-span-2">
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <textarea id="description" name="description" rows="4" required
                class="block w-full resize-y rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">{{ old('description', $product?->description) }}</textarea>
            @error('description') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
            <select id="category_id" name="category_id" required
                class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                <option value="" disabled @selected(! old('category_id', $product?->category_id))>Choose a category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label for="price" class="block text-sm font-medium text-gray-700">Price (Rp)</label>
            <input id="price" name="price" type="number" min="0" step="1" value="{{ old('price', $product?->price) }}" required
                class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('price') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label for="stock" class="block text-sm font-medium text-gray-700">Stock</label>
            <input id="stock" name="stock" type="number" min="0" step="1" value="{{ old('stock', $product?->stock) }}" required
                class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('stock') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label for="weight_gram" class="block text-sm font-medium text-gray-700">Berat produk (gram)</label>
            <input id="weight_gram" name="weight_gram" type="number" min="1" step="1" value="{{ old('weight_gram', $product?->weight_gram ?? 1000) }}" required
                class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            <p class="text-xs text-gray-500">Berat per item untuk perhitungan ongkos kirim.</p>
            @error('weight_gram') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2 md:col-span-2">
            @include('admin.products._file_upload', ['fileUploadId' => 'product-image-upload', 'fileUploadLabel' => 'Product image', 'fileUploadName' => 'image', 'fileUploadAccept' => 'image/png,image/jpeg,image/gif,image/webp', 'fileUploadHelpText' => 'PNG, JPG, GIF or WEBP', 'fileUploadMaxSize' => 2, 'fileUploadMode' => 'async', 'fileUploadUrl' => route('file-uploads.store'), 'fileUploadDeleteUrl' => route('file-uploads.destroy'), 'fileUploadHiddenName' => 'image', 'fileUploadValue' => old('image', $product?->image), 'fileUploadPreviewUrl' => old('image', $product?->image)])
            @error('image') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:justify-end">
        <button type="reset" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-100">Reset</button>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200">
            <i data-lucide="save" class="h-4 w-4"></i>{{ $submitLabel ?? 'Save changes' }}
        </button>
    </div>
</form>

@push('scripts')
    @vite('resources/js/product.form.js')
@endpush