/**
 * Kumpulan interaksi ringan khusus workspace admin.
 * Logika UI ditempatkan di Alpine agar perilaku komponen mudah ditemukan,
 * diuji, dan tidak tersebar sebagai script inline di setiap halaman.
 */
export function registerAdminComponents(Alpine) {
    /** Mengonfirmasi aksi destruktif sebelum form dikirim ke server. */
    Alpine.data('adminConfirm', (message) => ({
        message,
        submit(event) {
            if (!window.confirm(this.message)) event.preventDefault();
        },
    }));

    /** Mengelola preview gambar baru pada form produk dan membersihkan object URL. */
    Alpine.data('adminProductGallery', () => ({
        previews: [],
        handleFiles(event) {
            this.clearPreviews();
            this.previews = Array.from(event.target.files || [])
                .filter((file) => file.type.startsWith('image/'))
                .map((file, index) => ({
                    name: file.name,
                    url: URL.createObjectURL(file),
                    value: `new:${index}`,
                    index,
                }));

            // Gambar pertama otomatis menjadi gambar utama jika belum ada pilihan.
            if (this.previews.length && !document.querySelector('input[name="primary_image"]:checked')) {
                this.$nextTick(() => {
                    const firstRadio = this.$root.querySelector('[data-new-image-radio]');
                    if (firstRadio) firstRadio.checked = true;
                });
            }
        },
        clearPreviews() {
            this.previews.forEach((preview) => URL.revokeObjectURL(preview.url));
            this.previews = [];
        },
        destroy() {
            this.clearPreviews();
        },
    }));

    /**
     * Mengelola tab form produk dan status submit dalam satu komponen.
     * Panel dibuat dari kelompok field yang sudah ada agar markup field lama
     * tetap menjadi sumber kebenaran dan tidak perlu diduplikasi.
     */
    Alpine.data('adminProductForm', () => ({
        activeTab: 'general',
        submitting: false,
        startSubmit() {
            this.submitting = true;
        },
        init() {
            const form = this.$root;
            const grid = form.querySelector(':scope > .grid');
            if (!grid) return;

            // Aturan pencocokan memetakan field lama ke tab yang sesuai.
            const sections = [
                { id: 'general', label: 'Umum', match: (el) => ['name', 'slug', 'sku', 'barcode', 'description', 'category_id'].some((id) => el.querySelector('#' + id)) },
                { id: 'variants', label: 'Varian & Inventori', match: (el) => el.matches('[data-variant-builder], [data-edit-variant-builder]') || el.querySelector('[data-existing-variant-row]') !== null || ['Variants & Inventory', 'Product variants', 'Tambah Variant Baru'].includes(el.querySelector('h4')?.textContent.trim()) },
                { id: 'media', label: 'Gambar', match: (el) => el.matches('[data-product-gallery]') },
                { id: 'settings', label: 'SEO & Pengaturan', match: (el) => el.querySelector('#meta_title, #meta_description, input[name="is_active"], input[name="is_featured"]') },
            ];

            // Navigasi tab memakai directive Alpine, bukan listener click manual.
            const nav = document.createElement('div');
            nav.className = 'mb-5 flex gap-2 overflow-x-auto border-b border-gray-200 pb-2';
            nav.setAttribute('role', 'tablist');
            nav.setAttribute('aria-label', 'Bagian form produk');
            const panels = new Map();

            sections.forEach((section) => {
                const panel = document.createElement('section');
                panel.dataset.tabPanel = section.id;
                panel.className = 'grid grid-cols-1 gap-6 md:grid-cols-2';
                panel.setAttribute('role', 'tabpanel');
                panel.setAttribute('x-cloak', '');
                panel.setAttribute('x-show', `activeTab === '${section.id}'`);
                panels.set(section.id, panel);
                grid.append(panel);

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'shrink-0 rounded-lg px-4 py-2.5 text-sm font-semibold transition';
                button.setAttribute('role', 'tab');
                button.setAttribute('x-on:click', `activeTab = '${section.id}'`);
                button.setAttribute(':aria-selected', `activeTab === '${section.id}'`);
                button.setAttribute(':class', `activeTab === '${section.id}' ? 'bg-primary-50 text-primary-700' : 'text-gray-500 hover:bg-gray-50'`);
                button.textContent = section.label;
                nav.append(button);
            });

            // Pindahkan node field ke panel yang sesuai tanpa menggandakan input.
            Array.from(grid.children).filter((child) => !child.matches('[data-tab-panel]')).forEach((child) => {
                const section = sections.find((candidate) => candidate.match(child)) || sections[0];
                panels.get(section.id).append(child);
            });
            grid.before(nav);

            // Jika validasi server gagal, arahkan admin ke tab yang memuat error.
            const invalidField = form.querySelector('[aria-invalid="true"], .text-red-600');
            this.activeTab = invalidField?.closest('[data-tab-panel]')?.dataset.tabPanel || 'general';
        },
    }));
}
