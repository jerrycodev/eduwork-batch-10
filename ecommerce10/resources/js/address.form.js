export function registerAddressRegionForm(Alpine) {
    Alpine.data('addressRegionForm', (endpoints, selected = []) => ({
        status: 'Pilih wilayah sesuai alamat pengiriman.',
        async init() {
            await this.load(endpoints.provinces, this.$refs.province, 'Pilih provinsi', selected[0] || '');
            if (selected[0]) await this.load(`${endpoints.regencies}?province=${encodeURIComponent(selected[0])}`, this.$refs.regency, 'Pilih kota/kabupaten', selected[1] || '');
            if (selected[1]) await this.load(`${endpoints.districts}?regency=${encodeURIComponent(selected[1])}`, this.$refs.district, 'Pilih kecamatan', selected[2] || '');
            if (selected[2]) {
                const villages = await this.load(`${endpoints.villages}?district=${encodeURIComponent(selected[2])}`, this.$refs.village, 'Pilih kelurahan/desa', selected[3] || '');
                const village = villages.find((item) => item.value === (selected[3] || ''));
                if (village?.postal_code && !this.$refs.postalCode.value) this.$refs.postalCode.value = village.postal_code;
            }
        },
        reset(select, label) {
            const hadValue = Boolean(select.value);
            select.replaceChildren(new Option(label, ''));
            select.disabled = true;
            if (hadValue) select.dispatchEvent(new Event('change', { bubbles: true }));
        },
        async load(url, select, placeholder, selectedValue = '') {
            this.reset(select, 'Memuat...');
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Gagal memuat data wilayah. Silakan coba kembali.');
                const items = await response.json();
                select.replaceChildren(new Option(placeholder, ''));
                items.forEach((item) => {
                    const option = new Option(item.label, item.value);
                    option.selected = item.value === selectedValue;
                    if (item.postal_code) option.dataset.postalCode = item.postal_code;
                    select.add(option);
                });
                select.disabled = false;
                return items;
            } catch (error) {
                this.reset(select, 'Data gagal dimuat');
                this.status = error.message;
                return [];
            }
        },
        async provinceChanged() {
            this.$refs.postalCode.value = '';
            this.reset(this.$refs.regency, 'Pilih kota/kabupaten');
            this.reset(this.$refs.district, 'Pilih kecamatan');
            this.reset(this.$refs.village, 'Pilih kelurahan/desa');
            if (this.$refs.province.value) await this.load(`${endpoints.regencies}?province=${encodeURIComponent(this.$refs.province.value)}`, this.$refs.regency, 'Pilih kota/kabupaten');
        },
        async regencyChanged() {
            this.$refs.postalCode.value = '';
            this.reset(this.$refs.district, 'Pilih kecamatan');
            this.reset(this.$refs.village, 'Pilih kelurahan/desa');
            if (this.$refs.regency.value) await this.load(`${endpoints.districts}?regency=${encodeURIComponent(this.$refs.regency.value)}`, this.$refs.district, 'Pilih kecamatan');
        },
        async districtChanged() {
            this.$refs.postalCode.value = '';
            this.reset(this.$refs.village, 'Pilih kelurahan/desa');
            if (this.$refs.district.value) await this.load(`${endpoints.villages}?district=${encodeURIComponent(this.$refs.district.value)}`, this.$refs.village, 'Pilih kelurahan/desa');
        },
        villageChanged() {
            const postalCode = this.$refs.village.selectedOptions[0]?.dataset.postalCode;
            if (postalCode) this.$refs.postalCode.value = postalCode;
        },
    }));
}
