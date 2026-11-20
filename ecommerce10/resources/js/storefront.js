export function registerStorefrontComponents(Alpine) {
    Alpine.data('addressDropdown', (addresses, selectedId, placeholder) => ({
        open: false,
        selected: String(selectedId ?? ''),
        addresses,
        placeholder,
        get selectedAddress() {
            return this.addresses.find((address) => address.id === this.selected);
        },
        choose(address) {
            this.selected = address.id;
            this.open = false;
            this.$dispatch('address-selected');
        },
    }));

    Alpine.data('cartQuantity', () => ({
        value: 1,
        defaultValue: 1,
        min: 1,
        max: Number.POSITIVE_INFINITY,
        init() {
            const input = this.$refs.quantity;
            this.value = Number(input.value || 1);
            this.defaultValue = this.value;
            this.min = Number(input.min || 1);
            this.max = input.max === '' ? Number.POSITIVE_INFINITY : Number(input.max);
            this.sync();
        },
        get hasChanged() {
            return this.value === '' || Number(this.value) !== this.defaultValue;
        },
        get decrementDisabled() {
            return Number(this.value || this.min) <= this.min;
        },
        get incrementDisabled() {
            return Number(this.value || this.min) >= this.max;
        },
        step(amount) {
            const current = Number(this.value || this.min);
            this.value = Math.min(this.max, Math.max(this.min, current + amount));
            this.sync();
        },
        sync() {
            if (this.value !== '') {
                this.value = Math.min(this.max, Math.max(this.min, Number(this.value) || this.min));
            }
        },
    }));

    Alpine.data('checkoutFlow', ({ subtotal, ratesUrl }) => ({
        subtotal: Number(subtotal),
        ratesUrl,
        rates: [],
        quoteId: '',
        rateKey: '',
        shippingCost: null,
        loading: false,
        message: 'Pilih alamat lalu hitung tarif kurir yang tersedia.',
        get total() {
            return this.subtotal + Number(this.shippingCost || 0);
        },
        get hasAddress() {
            return Boolean(document.getElementById('checkout-address')?.value);
        },
        get canSubmit() {
            return Boolean(this.quoteId && this.rateKey && !this.loading);
        },
        get groupedRates() {
            const groups = new Map();
            this.rates.forEach((rate) => {
                if (!groups.has(rate.courier_code)) {
                    groups.set(rate.courier_code, { code: rate.courier_code, name: rate.courier_name, rates: [] });
                }
                groups.get(rate.courier_code).rates.push(rate);
            });
            return [...groups.values()];
        },
        currency(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(value || 0));
        },
        clearSelection() {
            this.quoteId = '';
            this.rateKey = '';
            this.shippingCost = null;
        },
        resetShipping() {
            this.clearSelection();
            this.rates = [];
            this.message = 'Alamat berubah. Hitung ulang ongkos kirim.';
        },
        async calculate() {
            if (!this.hasAddress) {
                this.message = 'Pilih alamat pengiriman terlebih dahulu.';
                return;
            }

            this.clearSelection();
            this.rates = [];
            this.loading = true;
            this.message = 'Mengambil tarif kurir...';

            try {
                const token = document.querySelector('#checkout-form [name="_token"]')?.value;
                const response = await fetch(this.ratesUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token || '',
                    },
                    body: JSON.stringify({ address_id: document.getElementById('checkout-address')?.value }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Tarif pengiriman tidak tersedia.');

                this.quoteId = data.quote_id;
                this.rates = data.rates || [];
                this.message = this.rates.length
                    ? `${this.rates.length} layanan tersedia. Pilih salah satu layanan untuk melanjutkan.`
                    : 'Belum ada layanan pengiriman untuk alamat ini.';
            } catch (error) {
                this.message = error.message || 'Terjadi kesalahan saat menghitung ongkos kirim.';
            } finally {
                this.loading = false;
            }
        },
        selectRate(rate) {
            this.rateKey = rate.rate_key;
            this.shippingCost = Number(rate.price);
        },
        validateCheckout(event) {
            if (!this.canSubmit) {
                event.preventDefault();
                this.message = 'Hitung ongkir dan pilih layanan pengiriman terlebih dahulu.';
            }
        },
    }));

    Alpine.data('productVariantSelector', (variants, initialVariantId) => ({
        variants,
        selected: {},
        selectedVariant: null,
        priceLabel: '',
        stockLabel: '',
        init() {
            const initial = this.variants.find((variant) => String(variant.id) === String(initialVariantId)) || this.variants[0];
            this.selected = { ...(initial?.option_values || {}) };
            this.refresh();
        },
        isSelected(code, valueId) {
            return String(this.selected[code] ?? '') === String(valueId);
        },
        selectOption(code, valueId) {
            this.selected[code] = String(valueId);
            this.refresh();
        },
        refresh() {
            this.selectedVariant = this.variants.find((variant) =>
                Object.entries(this.selected).every(([code, valueId]) => String(variant.option_values[code]) === String(valueId))
                && Object.keys(variant.option_values).length === Object.keys(this.selected).length,
            ) || null;

            this.$root.querySelectorAll('[data-option-code]').forEach((control) => {
                const code = control.dataset.optionCode;
                if (control.tagName === 'SELECT' && this.selected[code] && control.value !== this.selected[code]) {
                    control.value = this.selected[code];
                }
                const valueId = control.tagName === 'SELECT' ? control.value : control.dataset.valueId;
                if (control.tagName !== 'SELECT') {
                    control.classList.toggle('border-primary-600', this.isSelected(code, valueId));
                    control.classList.toggle('ring-2', this.isSelected(code, valueId));
                    control.classList.toggle('ring-primary-200', this.isSelected(code, valueId));
                    control.setAttribute('aria-pressed', this.isSelected(code, valueId) ? 'true' : 'false');
                }
                if (this.isSelected(code, valueId)) {
                    const label = this.$root.querySelector(`[data-selected-option-label="${code}"]`);
                    if (label) label.textContent = control.tagName === 'SELECT'
                        ? control.selectedOptions[0]?.dataset.valueLabel || ''
                        : control.dataset.valueLabel || '';
                }
            });

            if (!this.selectedVariant) {
                this.priceLabel = 'Kombinasi varian tidak tersedia';
                this.stockLabel = 'Kombinasi varian tidak tersedia';
                this.$root.querySelector('#selected-product-variant').value = '';
                this.$root.querySelector('#product-cart-form button[type="submit"]').disabled = true;
                return;
            }

            const variant = this.selectedVariant;
            this.$root.querySelector('#selected-product-variant').value = variant.id;
            this.priceLabel = 'Rp ' + new Intl.NumberFormat('id-ID').format(variant.price);
            this.stockLabel = variant.stock > 0 ? `Stok tersedia: ${variant.stock}` : 'Stok habis';
            this.$root.querySelector('#product-cart-form button[type="submit"]').disabled = variant.stock < 1;
            const label = this.$root.querySelector('#product-cart-form button[type="submit"] span');
            if (label) label.textContent = variant.stock > 0 ? 'Tambah ke Keranjang' : 'Stok Habis';
            this.$root.querySelector('#product-cart-form').dispatchEvent(new CustomEvent('variant-stock-updated', { detail: { stock: variant.stock }, bubbles: true }));

            if (variant.image) {
                window.dispatchEvent(new CustomEvent('product-image-change', { detail: { image: variant.image } }));
            }
        },
    }));

    Alpine.data('paymentCountdown', (expiresAt) => ({
        label: 'menghitung...',
        interval: null,
        init() {
            this.update();
            this.interval = window.setInterval(() => this.update(), 1000);
        },
        destroy() {
            if (this.interval) window.clearInterval(this.interval);
        },
        update() {
            const remaining = Math.max(0, Math.floor((Number(expiresAt) * 1000 - Date.now()) / 1000));
            if (remaining <= 0) {
                this.label = 'Waktu habis — sistem sedang memverifikasi pembayaran';
                window.dispatchEvent(new CustomEvent('payment-expired'));
                this.destroy();
                return;
            }
            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            this.label = `${minutes} menit ${String(seconds).padStart(2, '0')} detik`;
        },
    }));

    Alpine.data('paymentSnap', (token, snapJsUrl) => ({
        loading: false,
        error: '',
        async ensureSnapLoaded() {
            if (window.snap?.pay) return;

            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = snapJsUrl;
                script.dataset.midtransSnap = 'true';
                script.dataset.clientKey = document.querySelector('script[data-midtrans-snap]')?.dataset.clientKey || '';
                script.onload = resolve;
                script.onerror = () => reject(new Error('Skrip pembayaran Midtrans gagal dimuat. Periksa koneksi atau coba beberapa saat lagi.'));
                document.head.appendChild(script);
            });

            if (!window.snap?.pay) throw new Error('Layanan pembayaran Midtrans belum siap. Silakan muat ulang halaman.');
        },
        async pay() {
            if (this.loading) return;
            this.loading = true;
            this.error = '';
            try {
                await this.ensureSnapLoaded();
                window.snap.pay(token, {
                    onSuccess: () => window.location.reload(),
                    onPending: () => window.location.reload(),
                    onError: () => {
                        this.error = 'Pembayaran belum berhasil diproses. Silakan coba kembali atau pilih metode pembayaran lain.';
                        this.loading = false;
                    },
                    onClose: () => { this.loading = false; },
                });
            } catch (error) {
                this.error = error.message || 'Tidak dapat membuka halaman pembayaran.';
                this.loading = false;
            }
        },
    }));
}
