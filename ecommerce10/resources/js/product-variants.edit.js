// Builder varian tambahan pada produk existing; kombinasi lama tidak dihapus atau digandakan.
document.addEventListener('DOMContentLoaded', () => {
    const builder = document.querySelector('[data-edit-variant-builder]');
    if (!builder) return;

    const form = builder.closest('form');
    const optionsHost = builder.querySelector('[data-edit-option-groups]');
    const rowsHost = builder.querySelector('[data-edit-new-variant-rows]');
    const payload = builder.querySelector('#additional-variants-json');
    const seed = JSON.parse(builder.querySelector('[data-edit-variant-seed]').value || '{"options":[],"combinations":[]}');
    let generated = [];
    const clearGenerated = () => {
        generated = [];
        payload.value = '';
        rowsHost.replaceChildren();
    };

    const slugify = (value) => value.toLowerCase().trim()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

    const addValue = (group, value = {}) => {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-1 items-center gap-2 rounded-lg bg-gray-50 p-3 sm:grid-cols-[1fr_1fr_48px_1fr_auto]';
        row.dataset.editOptionValue = '';
        const label = document.createElement('input');
        label.placeholder = 'Label (Royal Brown)';
        label.value = value.label || '';
        label.dataset.valueLabel = '';
        label.className = 'rounded-md border-gray-300 text-xs';
        const code = document.createElement('input');
        code.placeholder = 'Value (royal-brown)';
        code.value = value.value || '';
        code.dataset.valueCode = '';
        code.className = 'rounded-md border-gray-300 text-xs';
        const hex = document.createElement('input');
        hex.type = 'color';
        hex.value = value.color_hex || '#5A4632';
        hex.dataset.valueHex = '';
        hex.className = 'h-9 w-12 rounded border border-gray-300 p-1';
        const image = document.createElement('input');
        image.placeholder = 'Swatch image URL (optional)';
        image.value = value.swatch_image || '';
        image.dataset.valueImage = '';
        image.className = 'rounded-md border-gray-300 text-xs';
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.textContent = value.id ? 'Existing' : 'Remove';
        remove.className = 'rounded-md px-2 py-2 text-xs text-red-600 hover:bg-red-50';
        remove.disabled = Boolean(value.id);
        if (value.id) remove.title = 'Nilai option yang sudah tersimpan tidak dihapus dari sini.';
        remove.addEventListener('click', () => { row.remove(); clearGenerated(); });
        label.addEventListener('input', () => { if (!code.dataset.edited) code.value = slugify(label.value); clearGenerated(); });
        code.addEventListener('input', () => { code.dataset.edited = '1'; clearGenerated(); });
        hex.addEventListener('change', clearGenerated);
        image.addEventListener('input', clearGenerated);
        row.append(label, code, hex, image, remove);
        if (value.id) row.dataset.valueId = value.id;
        group.querySelector('[data-edit-values]').append(row);
    };

    const addOption = (option = {}) => {
        const group = document.createElement('section');
        group.className = 'space-y-3 rounded-xl border border-gray-200 p-4';
        group.dataset.editOptionGroup = '';
        if (option.id) group.dataset.optionId = option.id;

        const fields = document.createElement('div');
        fields.className = 'grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr_180px_auto]';
        const makeField = (labelText, attr, value, placeholder) => {
            const label = document.createElement('label');
            label.className = 'space-y-1 text-xs font-medium text-gray-600';
            label.textContent = labelText;
            const input = document.createElement('input');
            input.dataset[attr] = '';
            input.value = value || '';
            input.placeholder = placeholder;
            input.className = 'block w-full rounded-md border-gray-300 text-sm';
            label.append(input);
            return label;
        };
        const nameField = makeField('Option name', 'optionName', option.name, 'Color');
        const codeField = makeField('Code', 'optionCode', option.code, 'color');
        const typeLabel = document.createElement('label');
        typeLabel.className = 'space-y-1 text-xs font-medium text-gray-600';
        typeLabel.textContent = 'Display';
        const type = document.createElement('select');
        type.dataset.optionType = '';
        type.className = 'block w-full rounded-md border-gray-300 text-sm';
        [['color', 'Color swatches'], ['button', 'Buttons'], ['dropdown', 'Dropdown']].forEach(([v, t]) => {
            const item = document.createElement('option');
            item.value = v; item.textContent = t; type.append(item);
        });
        type.value = option.display_type || 'button';
        typeLabel.append(type);
        const removeOption = document.createElement('button');
        removeOption.type = 'button';
        removeOption.textContent = 'Remove option';
        removeOption.className = 'self-end rounded-md px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50';
        removeOption.disabled = Boolean(option.id);
        if (option.id) removeOption.title = 'Option yang sudah digunakan variant tidak dihapus dari sini.';
        removeOption.addEventListener('click', () => { group.remove(); rowsHost.replaceChildren(); generated = []; payload.value = ''; });
        fields.append(nameField, codeField, typeLabel, removeOption);

        const valuesHeader = document.createElement('div');
        valuesHeader.className = 'flex items-center justify-between';
        const heading = document.createElement('span');
        heading.className = 'text-xs font-semibold text-gray-700';
        heading.textContent = 'Option values';
        const add = document.createElement('button');
        add.type = 'button'; add.textContent = '+ Add value';
        add.className = 'text-xs font-semibold text-primary-700';
        const values = document.createElement('div');
        values.dataset.editValues = '';
        values.className = 'space-y-2';
        add.addEventListener('click', () => { addValue(group); clearGenerated(); });
        valuesHeader.append(heading, add);
        group.append(fields, valuesHeader, values);
        optionsHost.append(group);
        nameField.querySelector('input').addEventListener('input', () => {
            const code = codeField.querySelector('input');
            if (!code.dataset.edited) code.value = slugify(nameField.querySelector('input').value);
            clearGenerated();
        });
        codeField.querySelector('input').addEventListener('input', (event) => { event.target.dataset.edited = '1'; clearGenerated(); });
        type.addEventListener('change', clearGenerated);
        (option.values || []).forEach((value) => addValue(group, value));
    };

    const readOptions = () => Array.from(optionsHost.querySelectorAll('[data-edit-option-group]')).map((group) => ({
        id: group.dataset.optionId || null,
        name: group.querySelector('[data-option-name]').value.trim(),
        code: slugify(group.querySelector('[data-option-code]').value),
        display_type: group.querySelector('[data-option-type]').value,
        values: Array.from(group.querySelectorAll('[data-edit-option-value]')).map((row) => ({
            id: row.dataset.valueId || null,
            label: row.querySelector('[data-value-label]').value.trim(),
            value: slugify(row.querySelector('[data-value-code]').value),
            color_hex: group.querySelector('[data-option-type]').value === 'color' ? row.querySelector('[data-value-hex]').value : null,
            swatch_image: row.querySelector('[data-value-image]').value.trim() || null,
        })),
    }));

    const cartesian = (options) => options.length
        ? options.reduce((sets, option) => sets.flatMap((set) => option.values.map((value) => ({
            values: { ...set.values, [option.code]: value.value },
            labels: [...set.labels, value.label],
        }))), [{ values: {}, labels: [] }])
        : [];

    const render = () => {
        const options = readOptions();
        if (!options.length || options.some((o) => !o.name || !o.code || !o.values.length || o.values.some((v) => !v.label || !v.value))) {
            window.alert('Tambahkan minimal satu option dan lengkapi seluruh nilainya terlebih dahulu.');
            return;
        }
        const combinations = cartesian(options);
        if (combinations.length > 100) {
            window.alert('Maksimal 100 kombinasi variant per produk.');
            return;
        }
        const additions = combinations.filter((combo) => {
            const selectedExistingIds = options
                .filter((option) => option.id)
                .map((option) => option.values.find((value) => value.value === combo.values[option.code])?.id)
                .filter(Boolean)
                .sort();
            const newOptionsUseDefault = options
                .filter((option) => !option.id)
                .every((option) => combo.values[option.code] === option.values[0]?.value);
            const alreadyExists = newOptionsUseDefault && seed.combinations.some((existing) => {
                const sorted = [...existing].sort();
                return sorted.length === selectedExistingIds.length &&
                    sorted.every((id, index) => id === selectedExistingIds[index]);
            });
            return !alreadyExists;
        });
        if (!additions.length) {
            window.alert('Semua kombinasi sudah tersedia. Tambahkan nilai option baru untuk membuat variant tambahan.');
            return;
        }
        generated = additions;
        const table = document.createElement('table');
        table.className = 'min-w-[1400px] w-full divide-y divide-gray-200 text-left text-xs';
        table.innerHTML = '<thead class="bg-gray-50 text-gray-500"><tr><th class="px-2 py-2">Combination</th><th class="px-2 py-2">SKU</th><th class="px-2 py-2">Barcode</th><th class="px-2 py-2">Price (Rp)</th><th class="px-2 py-2">Normal price</th><th class="px-2 py-2">Cost</th><th class="px-2 py-2">Stock</th><th class="px-2 py-2">Weight (g)</th><th class="px-2 py-2">Length</th><th class="px-2 py-2">Width</th><th class="px-2 py-2">Height</th><th class="px-2 py-2">Images</th></tr></thead><tbody></tbody>';
        const body = table.querySelector('tbody');
        additions.forEach((combo, index) => {
            const row = document.createElement('tr');
            row.dataset.options = JSON.stringify(combo.values);
            const cell = (className = 'px-2 py-2') => { const td = document.createElement('td'); td.className = className; return td; };
            const combination = cell('px-2 py-2 font-medium text-gray-700');
            combination.textContent = combo.labels.join(' / ');
            row.append(combination);
            const fields = [
                ['sku', 'text', (document.querySelector('#sku')?.value || 'PRD') + '-' + combo.labels.map(slugify).join('-').toUpperCase(), 'w-36'],
                ['barcode', 'text', '', 'w-32'],
                ['price', 'number', document.querySelector('#price')?.value || '0', 'w-24'],
                ['compare', 'number', '', 'w-24'],
                ['cost', 'number', '', 'w-24'],
                ['stock', 'number', '0', 'w-20'],
                ['weight', 'number', document.querySelector('#weight_gram')?.value || '1000', 'w-20'],
                ['length', 'number', '', 'w-20'],
                ['width', 'number', '', 'w-20'],
                ['height', 'number', '', 'w-20'],
            ];
            fields.forEach(([key, type, value, width]) => {
                const td = cell();
                const input = document.createElement('input');
                input.type = type; input.value = value; input.dataset[key] = '';
                if (type === 'number') { input.min = key === 'weight' ? '1' : '0'; if (['length', 'width', 'height'].includes(key)) input.step = '0.01'; }
                input.className = width + ' rounded border-gray-300 text-xs';
                td.append(input); row.append(td);
            });
            const imageCell = cell('min-w-52 px-2 py-2');
            const imageInput = document.createElement('input');
            imageInput.type = 'file'; imageInput.name = 'new_variant_images[' + index + '][]';
            imageInput.accept = 'image/jpeg,image/png,image/webp'; imageInput.multiple = true;
            imageInput.className = 'block w-52 text-[10px]';
            imageCell.append(imageInput); row.append(imageCell);
            body.append(row);
        });
        rowsHost.replaceChildren(table);
        rowsHost.classList.add('overflow-x-auto', 'rounded-lg', 'border', 'border-gray-200');
    };

    seed.options.forEach(addOption);
    builder.querySelector('[data-edit-add-option]').addEventListener('click', () => { addOption(); clearGenerated(); });
    builder.querySelector('[data-edit-generate]').addEventListener('click', render);
    form.addEventListener('submit', () => {
        if (!generated.length) return;
        const options = readOptions();
        const rows = Array.from(rowsHost.querySelectorAll('tbody tr'));
        const variants = rows.map((row) => ({
            options: JSON.parse(row.dataset.options),
            sku: row.querySelector('[data-sku]').value.trim(),
            barcode: row.querySelector('[data-barcode]').value.trim() || null,
            price: Number(row.querySelector('[data-price]').value),
            compare_at_price: row.querySelector('[data-compare]').value || null,
            cost_price: row.querySelector('[data-cost]').value || null,
            stock: Number(row.querySelector('[data-stock]').value),
            weight_gram: Number(row.querySelector('[data-weight]').value),
            length_cm: row.querySelector('[data-length]').value || null,
            width_cm: row.querySelector('[data-width]').value || null,
            height_cm: row.querySelector('[data-height]').value || null,
        }));
        payload.value = JSON.stringify({ options, variants });
    });
});
