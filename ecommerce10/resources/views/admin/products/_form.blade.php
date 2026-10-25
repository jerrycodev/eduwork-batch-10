{{-- blade-formatter-disable --}}
<?php
$fields = [
    ['label' => 'Name', 'name' => 'name', 'value' => old('name', $product?->name), 'type' => 'text', 'colSpan' => 6],
    ['label' => 'Slug', 'name' => 'slug', 'value' => old('slug', $product?->slug), 'type' => 'text', 'colSpan' => 6],
    ['label' => 'Description', 'name' => 'description', 'value' => old('description', $product?->description), 'type' => 'textarea', 'colSpan' => 12],
    ['label' => 'Category', 'name' => 'category_id', 'value' => old('category_id', $product?->category_id), 'type' => 'select', 'options' => $categories, 'colSpan' => 4],
    ['label' => 'Price', 'name' => 'price', 'value' => old('price', $product?->price), 'type' => 'number', 'colSpan' => 4],
    ['label' => 'Stock', 'name' => 'stock', 'value' => old('stock', $product?->stock), 'type' => 'number', 'colSpan' => 4],
    ['label' => 'Image', 'name' => 'image', 'value' => old('image', $product?->image), 'type' => 'file', 'colSpan' => 12],
];
?>
{{-- blade-formatter-enable --}}

<form id="product-form" action="{{ $action }}" method="POST" class="form" autocomplete="off">

    @csrf
    @if ($product)
        @method('PUT')
    @endif
    <div class="grid grid-cols-12 gap-4">
        @foreach ($fields as $field)
            <div class="form-field" style="{{ $field['colSpan'] ? 'grid-column-start: span '.$field['colSpan'] : '' }}">
                <label class="label" for="{{ $field['name'] }}">{{ $field['label'] }}</label>
                @if ($field['type'] === 'text' || $field['type'] === 'number')
                    <input class="input" id="{{ $field['name'] }}" name="{{ $field['name'] }}"
                        type="{{ $field['type'] }}" value="{{ $field['value'] }}" required
                        @if ($field['type'] === 'number') min="0" step="1" @endif>
                @elseif ($field['type'] === 'textarea')
                    <textarea class="textarea" id="{{ $field['name'] }}" name="{{ $field['name'] }}" required>{{ $field['value'] }}</textarea>
                @elseif ($field['type'] === 'select')
                    <select class="select" id="{{ $field['name'] }}" name="{{ $field['name'] }}" required>
                        <option value="" disabled @selected(! $field['value'])>-- Select --</option>
                        @foreach ($field['options'] as $option)
                            <option value="{{ $option->id }}" @selected((string) $field['value'] === (string) $option->id)>
                                {{ $option->name }}
                            </option>
                        @endforeach
                    </select>
                @elseif ($field['type'] === 'file')
                    @include('admin.products._file_upload', ['image' => $field['value']])
                @endif
                @error($field['name'])
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>
    <div class="form-actions">
        <button class="btn bg-indigo-400" type="submit"
            data-variant="default">{{ $submitLabel ?? 'Save changes' }}</button>
        <button class="btn" type="reset" data-variant="outline">Reset</button>
    </div>
</form>

@push('scripts')
    @vite('resources/js/product.form.js')
@endpush
