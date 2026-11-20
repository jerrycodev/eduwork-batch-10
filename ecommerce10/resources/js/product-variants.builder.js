// Builder varian untuk halaman produk baru: option -> nilai -> kombinasi SKU/stok.
document.addEventListener('DOMContentLoaded', () => {
    const builder = document.querySelector('[data-variant-builder]');
    if (!builder) return;

    const groups = builder.querySelector('[data-option-groups]');
    const rowsHost = builder.querySelector('[data-variant-rows]');
    const hidden = builder.querySelector('#options-json');
    const form = builder.closest('form');

    const slugify = (value) => value.toLowerCase().trim()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

    const addValue = (group) => {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-1 items-center gap-2 rounded-lg bg-gray-50 p-3 sm:grid-cols-[1fr_1fr_48px_1fr_auto]';
        row.dataset.optionValue = '';
        row.innerHTML = `
            <input type="text" data-value-label placeholder="Label (Royal Brown)" class="rounded-md border-gray-300 text-xs">
            <input type="text" data-value-code placeholder="Value (royal-brown)" class="rounded-md border-gray-300 text-xs">
            <input type="color" data-value-hex value="#5A4632" aria-label="HEX color" class="h-9 w-12 rounded border border-gray-300 p-1">
            <input type="text" data-value-image placeholder="Swatch image URL (optional)" class="rounded-md border-gray-300 text-xs">
            <button type="button" data-remove-value class="rounded-md px-2 py-2 text-xs text-red-600 hover:bg-red-50">Remove</button>`;
        group.querySelector('[data-values]').append(row);
        const label = row.querySelector('[data-value-label]');
        const code = row.querySelector('[data-value-code]');
        label.addEventListener('input', () => { if (!code.dataset.edited) code.value = slugify(label.value); });
        code.addEventListener('input', () => { code.dataset.edited = '1'; });
        row.querySelector('[data-remove-value]').addEventListener('click', () => row.remove());
    };

    const addOption = () => {
        const group = document.createElement('section');
        group.className = 'space-y-3 rounded-xl border border-gray-200 p-4';
        group.dataset.optionGroup = '';
        group.innerHTML = `
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr_180px_auto]">
                <label class="space-y-1 text-xs font-medium text-gray-600">Option name
                    <input data-option-name placeholder="Color" class="block w-full rounded-md border-gray-300 text-sm">
                </label>
                <label class="space-y-1 text-xs font-medium text-gray-600">Code
                    <input data-option-code placeholder="color" class="block w-full rounded-md border-gray-300 text-sm">
                </label>
                <label class="space-y-1 text-xs font-medium text-gray-600">Display
                    <select data-option-type class="block w-full rounded-md border-gray-300 text-sm">
                        <option value="color">Color swatches</option>
                        <option value="button">Buttons</option>
                        <option value="dropdown">Dropdown</option>
                    </select>
                </label>
                <button type="button" data-remove-option class="self-end rounded-md px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Remove option</button>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-700">Option values</span>
                    <button type="button" data-add-value class="text-xs font-semibold text-primary-700">+ Add value</button>
                </div>
                <div data-values class="space-y-2"></div>
            </div>`;
        groups.append(group);
        const name = group.querySelector('[data-option-name]');
        const code = group.querySelector('[data-option-code]');
        name.addEventListener('input', () => { if (!code.dataset.edited) code.value = slugify(name.value); });
        code.addEventListener('input', () => { code.dataset.edited = '1'; });
        group.querySelector('[data-add-value]').addEventListener('click', () => addValue(group));
        group.querySelector('[data-remove-option]').addEventListener('click', () => {
            group.remove();
            rowsHost.replaceChildren();
        });
        addValue(group);
    };

    const readOptions = () => Array.from(groups.querySelectorAll('[data-option-group]')).map((group) => ({
        name: group.querySelector('[data-option-name]').value.trim(),
        code: slugify(group.querySelector('[data-option-code]').value),
        display_type: group.querySelector('[data-option-type]').value,
        values: Array.from(group.querySelectorAll('[data-option-value]')).map((row) => ({
            label: row.querySelector('[data-value-label]').value.trim(),
            value: slugify(row.querySelector('[data-value-code]').value),
            color_hex: group.querySelector('[data-option-type]').value === 'color' ? row.querySelector('[data-value-hex]').value : null,
            swatch_image: row.querySelector('[data-value-image]').value.trim() || null,
        })),
    }));

    const cartesian = (options) => options.length
        ? options.reduce((combinations, option) => combinations.flatMap((combination) => option.values.map((value) => ({
            values: { ...combination.values, [option.code]: value.value },
            labels: [...combination.labels, value.label],
        }))), [{ values: {}, labels: [] }])
        : [{ values: {}, labels: ['Default'] }];

    const renderVariants = () => {
        const options = readOptions();
        if (options.some((option) => !option.name || !option.code || !option.values.length || option.values.some((value) => !value.label || !value.value))) {
            window.alert('Lengkapi nama option dan minimal satu value sebelum membuat kombinasi.');
            return;
        }
        const combinations = cartesian(options);
        if (combinations.length > 100) {
            window.alert('Maksimal 100 kombinasi variant per produk.');
            return;
        }
        const baseSku = document.querySelector('#sku')?.value.trim() || 'PRD';
        const basePrice = document.querySelector('#price')?.value || '0';
        const baseWeight = document.querySelector('#weight_gram')?.value || '1000';
        const table = document.createElement('table');
        table.className = 'min-w-[1500px] w-full divide-y divide-gray-200 text-left text-xs';
        table.innerHTML = `
            <thead class="bg-gray-50 text-gray-500"><tr>
                <th class="px-2 py-2">Combination</th><th class="px-2 py-2">SKU</th><th class="px-2 py-2">Barcode</th>
                <th class="px-2 py-2">Price (Rp)</th><th class="px-2 py-2">Harga normal</th><th class="px-2 py-2">Harga modal</th>
                <th class="px-2 py-2">Stock</th><th class="px-2 py-2">Weight (g)</th>
                <th class="px-2 py-2">Length</th><th class="px-2 py-2">Width</th><th class="px-2 py-2">Height</th>
                <th class="px-2 py-2">Images</th><th class="px-2 py-2">Active</th>
            </tr></thead><tbody class="divide-y divide-gray-100"></tbody>`;
        const tbody = table.querySelector('tbody');
        combinations.forEach((combination, index) => {
            const suffix = combination.labels.map(slugify).join('-').toUpperCase();
            const row = document.createElement('tr');
            row.dataset.variantOptions = JSON.stringify(combination.values);
            row.innerHTML = `
                <td class="px-2 py-2 font-medium text-gray-700">${combination.labels.join(' / ')}</td>
                <td class="px-2 py-2"><input data-v-sku value="${baseSku}-${suffix}" class="w-36 rounded border-gray-300 text-xs"></td>
                <td class="px-2 py-2"><input data-v-barcode class="w-32 rounded border-gray-300 text-xs"></td>
                <td class="px-2 py-2"><input data-v-price type="number" min="0" value="${basePrice}" class="w-24 rounded border-gray-300 text-xs"></td>
                <td class="px-2 py-2"><input data-v-compare type="number" min="0" class="w-24 rounded border-gray-300 text-xs"></td>
                <td class="px-2 py-2"><input data-v-cost type="number" min="0" class="w-24 rounded border-gray-300 text-xs"></td>
                <td class="px-2 py-2"><input data-v-stock type="number" min="0" value="0" class="w-20 rounded border-gray-300 text-xs"></td>
                <td class="px-2 py-2"><input data-v-weight type="number" min="1" value="${baseWeight}" class="w-20 rounded border-gray-300 text-xs"></td>
                <td class="px-2 py-2"><input data-v-length type="number" min="0" step="0.01" class="w-20 rounded border-gray-300 text-xs"></td>
                <td class="px-2 py-2"><input data-v-width type="number" min="0" step="0.01" class="w-20 rounded border-gray-300 text-xs"></td>
                <td class="px-2 py-2"><input data-v-height type="number" min="0" step="0.01" class="w-20 rounded border-gray-300 text-xs"></td>
                <td class="min-w-52 px-2 py-2">
                    <input type="file" name="variant_images[${index}][]" data-variant-file accept="image/jpeg,image/png,image/webp" multiple class="block w-52 text-[10px]">
                    <div data-variant-preview class="mt-2 flex flex-wrap gap-1"></div>
                </td>
                <td class="px-2 py-2"><input data-v-active type="checkbox" checked class="rounded border-gray-300 text-primary-600"></td>`;
            tbody.append(row);
        });
        rowsHost.replaceChildren(table);
        rowsHost.classList.add('overflow-x-auto', 'rounded-lg', 'border', 'border-gray-200');
    };

    builder.querySelector('[data-add-option]').addEventListener('click', addOption);
    builder.querySelector('[data-generate-variants]').addEventListener('click', renderVariants);
    builder.querySelector('[data-generate-variants]').insertAdjacentHTML('afterend', '<span class="text-xs text-gray-500">Tanpa option, sistem membuat satu variant Default.</span>');

    form.addEventListener('submit', (event) => {
        const options = readOptions();
        const rows = Array.from(rowsHost.querySelectorAll('tbody tr'));
        if (!rows.length) {
            event.preventDefault();
            window.alert('Klik Generate variants terlebih dahulu, lalu lengkapi harga, stok, dan berat setiap variant.');
            return;
        }
        const variants = rows.map((row) => ({
            options: JSON.parse(row.dataset.variantOptions),
            sku: row.querySelector('[data-v-sku]').value.trim(),
            barcode: row.querySelector('[data-v-barcode]').value.trim() || null,
            price: Number(row.querySelector('[data-v-price]').value),
            compare_at_price: row.querySelector('[data-v-compare]').value || null,
            cost_price: row.querySelector('[data-v-cost]').value || null,
            stock: Number(row.querySelector('[data-v-stock]').value),
            weight_gram: Number(row.querySelector('[data-v-weight]').value),
            length_cm: row.querySelector('[data-v-length]').value || null,
            width_cm: row.querySelector('[data-v-width]').value || null,
            height_cm: row.querySelector('[data-v-height]').value || null,
            is_active: row.querySelector('[data-v-active]').checked,
        }));
        const first = variants[0];
        document.querySelector('#price').value = first.price;
        document.querySelector('#compare_at_price').value = first.compare_at_price || '';
        document.querySelector('#cost_price').value = first.cost_price || '';
        document.querySelector('#stock').value = variants.reduce((total, variant) => total + variant.stock, 0);
        document.querySelector('#weight_gram').value = first.weight_gram;
        document.querySelector('#length_cm').value = first.length_cm || '';
        document.querySelector('#width_cm').value = first.width_cm || '';
        document.querySelector('#height_cm').value = first.height_cm || '';
        hidden.value = JSON.stringify({ options, variants });
    });
});
