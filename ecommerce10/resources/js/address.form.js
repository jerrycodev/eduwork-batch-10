function initializeAddressRegionForms() {
    document.querySelectorAll('[data-region-form]').forEach((form) => {
        if (form.dataset.boostInitialized) return;
        form.dataset.boostInitialized = 'true';

        const province = form.querySelector('#province_code');
        const regency = form.querySelector('#regency_code');
        const district = form.querySelector('#district_code');
        const village = form.querySelector('#village_code');
        const postalCode = form.querySelector('#postal_code');
        const status = form.querySelector('[data-region-status]');
        const selected = [province, regency, district, village].map((select) => select.dataset.selected || '');
        const endpoints = {
            provinces: form.dataset.provincesUrl,
            regencies: form.dataset.regenciesUrl,
            districts: form.dataset.districtsUrl,
            villages: form.dataset.villagesUrl,
        };

        const reset = (select, label) => {
            select.replaceChildren(new Option(label, ''));
            select.disabled = true;
        };

        const load = async (url, select, placeholder, selectedValue = '') => {
            reset(select, 'Memuat...');
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Gagal memuat data wilayah. Silakan muat ulang halaman.');
                const items = await response.json();
                select.replaceChildren(new Option(placeholder, ''));
                items.forEach((item) => {
                    const option = new Option(item.label, item.value);
                    if (item.value === selectedValue) option.selected = true;
                    if (item.postal_code) option.dataset.postalCode = item.postal_code;
                    select.add(option);
                });
                select.disabled = false;
                return items;
            } catch (error) {
                reset(select, 'Data gagal dimuat');
                status.textContent = error.message;
                return [];
            }
        };

        province.addEventListener('change', async () => {
            postalCode.value = '';
            reset(regency, 'Pilih kota/kabupaten');
            reset(district, 'Pilih kecamatan');
            reset(village, 'Pilih kelurahan/desa');
            if (province.value) await load(`${endpoints.regencies}?province=${encodeURIComponent(province.value)}`, regency, 'Pilih kota/kabupaten');
        });
        regency.addEventListener('change', async () => {
            postalCode.value = '';
            reset(district, 'Pilih kecamatan');
            reset(village, 'Pilih kelurahan/desa');
            if (regency.value) await load(`${endpoints.districts}?regency=${encodeURIComponent(regency.value)}`, district, 'Pilih kecamatan');
        });
        district.addEventListener('change', async () => {
            postalCode.value = '';
            reset(village, 'Pilih kelurahan/desa');
            if (district.value) await load(`${endpoints.villages}?district=${encodeURIComponent(district.value)}`, village, 'Pilih kelurahan/desa');
        });
        village.addEventListener('change', () => {
            const selectedOption = village.selectedOptions[0];
            if (selectedOption?.dataset.postalCode) postalCode.value = selectedOption.dataset.postalCode;
        });

        (async () => {
            await load(endpoints.provinces, province, 'Pilih provinsi', selected[0]);
            if (selected[0]) await load(`${endpoints.regencies}?province=${encodeURIComponent(selected[0])}`, regency, 'Pilih kota/kabupaten', selected[1]);
            if (selected[1]) await load(`${endpoints.districts}?regency=${encodeURIComponent(selected[1])}`, district, 'Pilih kecamatan', selected[2]);
            if (selected[2]) {
                const villages = await load(`${endpoints.villages}?district=${encodeURIComponent(selected[2])}`, village, 'Pilih kelurahan/desa', selected[3]);
                const chosenVillage = villages.find((item) => item.value === selected[3]);
                if (chosenVillage?.postal_code && !postalCode.value) postalCode.value = chosenVillage.postal_code;
            }
            status.textContent = 'Pilih wilayah sesuai alamat pengiriman.';
        })();
    });
}

document.addEventListener('boost:page-ready', initializeAddressRegionForms);
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAddressRegionForms, { once: true });
} else {
    initializeAddressRegionForms();
}
