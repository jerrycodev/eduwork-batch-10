# RevStore — Platform E-Commerce Laravel

RevStore adalah aplikasi e-commerce berbasis Laravel yang menyediakan storefront untuk customer dan area administrasi terpisah untuk mengelola katalog, varian, inventori, pesanan, pembayaran, pengiriman, refund, serta aktivitas operasional.

> README ini disusun agar pengunjung repository dapat memahami aplikasi, arsitektur database, dan alur pengguna sebelum memasangnya. Jika ingin mencoba aplikasi secara lokal, mulai dari bagian **Setup dan Instalasi**.

**Live Preview:** [https://revstore.jerrycodev.my.id](https://revstore.jerrycodev.my.id) · **Source Code:** [GitHub Repository](https://github.com/jerrycodev/eduwork-batch-10/tree/main/ecommerce10)

![RevStore Storefront](docs/screenshots/01-storefront-home.png)

<p align="center">
  <strong>Storefront · Product Catalog · Checkout · Order Fulfillment · Payment & Shipping Audit</strong>
</p>

## Daftar Isi

- [1. Gambaran Aplikasi](#1-gambaran-aplikasi)
- [2. Setup dan Instalasi Lokal](#2-setup-dan-instalasi-lokal)
- [3. Konfigurasi Integrasi Eksternal](#3-konfigurasi-integrasi-eksternal)
- [4. Akun Demo](#4-akun-demo)
- [5. Desain Database — ER Diagram](#5-desain-database--er-diagram)
- [6. User Flow](#6-user-flow)
- [7. Demo Flow Customer](#7-demo-flow-customer)
- [8. Demo Flow Admin](#8-demo-flow-admin)
- [9. Pembatalan, Retur, dan Refund](#9-pembatalan-retur-dan-refund)
- [10. Galeri Screenshot](#10-galeri-screenshot)
- [11. Struktur Proyek dan Dokumentasi](#11-struktur-proyek-dan-dokumentasi)
- [12. Testing](#12-testing)
- [13. Lisensi dan Kredit](#13-lisensi-dan-kredit)

---

## 1. Gambaran Aplikasi

RevStore menghubungkan pengalaman belanja customer dengan proses operasional admin dalam satu aplikasi.

### Fitur customer

- Registrasi, login, verifikasi email, reset password, dan pengelolaan profil.
- Penggantian alamat email dengan verifikasi ke alamat baru.
- Katalog produk, kategori, pencarian, detail produk, galeri gambar, dan pilihan varian.
- Varian produk dengan opsi seperti warna (termasuk kode warna HEX) dan ukuran.
- Keranjang, wishlist, pengelolaan alamat, dan checkout.
- Pilihan tarif pengiriman berdasarkan alamat dan data produk.
- Pembayaran melalui Midtrans, invoice PDF, dan bukti pembayaran.
- Riwayat pesanan, status pembayaran, tracking pengiriman, dan notifikasi.
- Konfirmasi penerimaan pesanan, ulasan produk, pembatalan, dan pengajuan retur.

### Fitur admin

- Dashboard ringkasan penjualan dan operasional.
- Pengelolaan kategori, produk, gambar, SEO, opsi, varian, SKU, barcode, harga, dan stok.
- Inventory management dan riwayat pergerakan stok.
- Pengelolaan pesanan, pemenuhan, pengiriman, pembatalan, retur, dan refund.
- Moderasi ulasan dan pengelolaan akun admin.
- Payment reconciliation, webhook audit, dan status resolution.
- Biteship reconciliation, webhook logs, dan riwayat shipment.
- Admin activity logs, customer activity logs, serta email delivery logs.

### Teknologi utama

| Area | Teknologi |
|---|---|
| Backend | PHP 8.3+, Laravel 13 |
| Database | MySQL 8+ atau MariaDB yang kompatibel |
| Frontend | Blade, Tailwind CSS 4, Alpine.js, JavaScript |
| Asset build | Vite, Node.js, npm |
| Interaksi dan ikon | Livewire (package tersedia), Lucide, Axios |
| Grafik | Chart.js |
| Pembayaran | Midtrans PHP SDK |
| Pengiriman | Biteship API |
| Data wilayah | `aliziodev/laravel-wilayah` |
| Dokumen | Laravel DomPDF |
| Realtime opsional | Laravel Reverb dan Laravel Echo |
| Testing | Pest |

---

## 2. Setup dan Instalasi Lokal

### 2.1 Persyaratan

Pastikan perangkat sudah memiliki:

- Git.
- PHP 8.3 atau lebih baru beserta ekstensi Laravel, PDO MySQL, OpenSSL, Mbstring, Fileinfo, GD, Intl, XML, dan ZIP.
- Composer 2.
- Node.js dan npm yang kompatibel dengan lockfile proyek.
- MySQL 8+ atau MariaDB yang kompatibel.
- (Opsional) Docker untuk menjalankan Mailpit.

Periksa versi perangkat:

```bash
php -v
composer --version
node --version
npm --version
```

### 2.2 Clone repository

```bash
git clone https://github.com/jerrycodev/eduwork-batch-10.git
cd eduwork-batch-10/ecommerce10
```

Jika repository dipindahkan atau URL berubah, sesuaikan alamat clone dengan URL repository yang aktif.

### 2.3 Install dependency

```bash
composer install
npm ci
```

Package wilayah Indonesia sudah tercantum pada `composer.json` dan `composer.lock`; tidak perlu memasangnya kembali dengan `composer require`.

### 2.4 Siapkan environment dan database

Salin template environment:

```bash
cp .env.example .env
```

Buat database lokal, misalnya `revstore`, lalu sesuaikan bagian berikut di `.env`:

```dotenv
APP_NAME="RevStore"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=revstore
DB_USERNAME=root
DB_PASSWORD=password-database-lokal

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
SESSION_SECURE_COOKIE=false
```

Buat application key:

```bash
php artisan key:generate
```

Jangan commit `.env`, application key, password, API key, atau secret webhook ke repository.

### 2.5 Jalankan migration

```bash
php artisan migrate
```

Migration aplikasi membuat tabel domain e-commerce, autentikasi, pesanan, pembayaran, pengiriman, audit, serta tabel wilayah yang disediakan package. Periksa hasilnya dengan:

```bash
php artisan migrate:status
```

### 2.6 Seed data wilayah Indonesia

RevStore menggunakan [`aliziodev/laravel-wilayah`](https://github.com/aliziodev/laravel-wilayah) untuk dropdown alamat bertingkat. Jalankan setelah migration:

```bash
php artisan wilayah:seed
```

Perintah ini mengisi provinsi, kabupaten/kota, kecamatan, desa/kelurahan, dan kode pos yang tersedia. Seeder wilayah berbeda dari seeder data contoh aplikasi.

### 2.7 Seed data aplikasi

Pastikan `APP_ENV=local`, lalu jalankan:

```bash
php artisan db:seed
```

Pada environment `local` atau `testing`, `DatabaseSeeder`:

- Membuat customer demo `demo1@ecommerce.test` dan `demo2@ecommerce.test` jika belum ada.
- Menjalankan katalog contoh berisi enam kategori dan 16 produk dengan opsi, varian, dan gambar.
- Menghindari duplikasi data katalog saat dijalankan ulang dan tidak mereset stok varian yang sudah ada.

Password factory untuk akun demo adalah `password`. Ganti password akun demo sebelum digunakan di lingkungan yang dapat diakses publik. Seeder tidak membuat akun demo customer atau katalog contoh di production.

Untuk membuat akun admin lokal melalui seeder, tambahkan nilai sendiri ke `.env`:

```dotenv
ADMIN_NAME="Administrator"
ADMIN_EMAIL=admin@revstore.test
ADMIN_PASSWORD=ganti-dengan-password-lokal-yang-kuat
```

Kemudian jalankan:

```bash
php artisan db:seed
```

Seeder hanya membuat akun admin jika email tersebut belum ada; password admin yang sudah ada tidak akan di-reset. Jangan gunakan kredensial contoh ini sebagai akun production.

### 2.8 Storage dan frontend

```bash
php artisan storage:link
npm run build
```

Untuk pengembangan frontend dengan hot reload, gunakan `npm run dev` pada terminal terpisah.

### 2.9 Jalankan aplikasi

Terminal pertama:

```bash
php artisan serve
```

Terminal kedua untuk email/pekerjaan antrean:

```bash
php artisan queue:work --tries=3 --backoff=10
```

Buka [http://127.0.0.1:8000](http://127.0.0.1:8000).

> **Catatan:** checkout yang memakai tarif pengiriman dan pembayaran membutuhkan konfigurasi Biteship dan Midtrans. Storefront dan bagian yang tidak memerlukan layanan eksternal tetap dapat dieksplorasi tanpa mengaktifkan transaksi live.

### 2.10 Email lokal (opsional)

Mailpit dapat digunakan untuk menangkap email pengembangan tanpa mengirim ke inbox sungguhan:

```bash
docker run -d --name revstore-mailpit \
  -p 1025:1025 -p 8025:8025 \
  axllent/mailpit:latest
```

Konfigurasikan `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="noreply@revstore.test"
MAIL_FROM_NAME="${APP_NAME}"
```

Buka [http://127.0.0.1:8025](http://127.0.0.1:8025) untuk melihat email yang ditangkap Mailpit.

---

## 3. Konfigurasi Integrasi Eksternal

Integrasi eksternal dapat dikonfigurasi setelah aplikasi lokal berjalan. Gunakan kredensial sandbox/test untuk demonstrasi.

### 3.1 Midtrans — pembayaran

Isi konfigurasi berikut di `.env` dengan kredensial sandbox dari dashboard Midtrans:

```dotenv
MIDTRANS_MERCHANT_ID=
MIDTRANS_CLIENT_KEY=
MIDTRANS_SERVER_KEY=
MIDTRANS_IS_PRODUCTION=false
```

Callback/notification URL aplikasi:

```text
POST https://domain-publik-anda/midtrans/callback
```

Untuk pengembangan lokal, gunakan tunnel HTTPS agar layanan Midtrans dapat mengakses callback lokal. Jangan menganggap redirect browser sebagai bukti pembayaran; status pembayaran harus diverifikasi dari notifikasi provider dan data transaksi yang tersimpan.

### 3.2 Biteship — tarif, shipment, dan tracking

Isi `.env` dengan API key test, data warehouse, dan secret webhook:

```dotenv
BITESHIP_BASE_URL=https://api.biteship.com
BITESHIP_TRACKING_ENVIRONMENT=development
BITESHIP_API_KEY=
BITESHIP_COURIERS=jne,sicepat,jnt,anteraja,pos
BITESHIP_TIMEOUT=15
BITESHIP_RATES_CACHE_TTL=300

BITESHIP_WAREHOUSE_NAME="RevStore Warehouse"
BITESHIP_WAREHOUSE_PHONE=
BITESHIP_WAREHOUSE_EMAIL=
BITESHIP_WAREHOUSE_ADDRESS=
BITESHIP_WAREHOUSE_POSTAL_CODE=
BITESHIP_WAREHOUSE_NOTE=

BITESHIP_WEBHOOK_SIGNATURE_KEY=X-Biteship-Signature
BITESHIP_WEBHOOK_SIGNATURE_SECRET=
```

Webhook Biteship diterima pada:

```text
POST https://domain-publik-anda/webhooks/biteship
```

Sesuaikan nama header signature dan secret dengan pengaturan pada dashboard Biteship. Warehouse harus berisi nama, nomor telepon, alamat, dan kode pos yang valid. Email dan catatan warehouse bersifat opsional. `BITESHIP_WAREHOUSE_ID` tidak diperlukan oleh implementasi saat ini.

### 3.3 SMTP dan queue

Email transaksional dikirim melalui konfigurasi `MAIL_*` Laravel. Di production, gunakan SMTP yang benar dan jalankan queue worker secara persisten. Periksa `/dashboard/email-delivery-logs` jika pengiriman email perlu ditelusuri.

### 3.4 Realtime (opsional)

Realtime tidak aktif secara default. Untuk mengaktifkan pembaruan status pesanan/notifikasi, siapkan Laravel Reverb dan nilai `REVERB_*`, `VITE_REVERB_*`, serta sakelar berikut:

```dotenv
BROADCAST_CONNECTION=reverb
REALTIME_ENABLED=true
REALTIME_ORDER_STATUS_ENABLED=true
REALTIME_NOTIFICATIONS_ENABLED=true
```

Jalankan layanan Reverb, queue worker, dan Vite sesuai kebutuhan pengembangan:

```bash
php artisan reverb:start
php artisan queue:work
npm run dev
```

Setelah mengubah `.env`, bersihkan cache konfigurasi:

```bash
php artisan config:clear
```

Panduan deployment lebih lengkap tersedia di [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md), sedangkan detail wilayah dan shipping tersedia di [`docs/WILAYAH-SETUP.md`](docs/WILAYAH-SETUP.md) dan [`docs/SHIPPING-SETUP.md`](docs/SHIPPING-SETUP.md).

---

## 4. Akun Demo

### Customer

| Email | Password lokal |
|---|---|
| `demo1@ecommerce.test` | `password` |
| `demo2@ecommerce.test` | `password` |

Akun customer tersebut dibuat oleh seeder hanya pada environment `local` dan `testing`. Jika belum tersedia, pastikan `APP_ENV=local` lalu jalankan `php artisan db:seed`.

### Admin

Akun admin dibuat menggunakan `ADMIN_NAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` pada `.env`, kemudian menjalankan `php artisan db:seed`. Login admin terpisah melalui:

```text
http://127.0.0.1:8000/admin/login
```

Customer login melalui `/login`. Jangan menggunakan akun demo atau password default pada production.

---

## 5. Desain Database — ER Diagram

Database menggunakan UUID untuk sebagian besar primary key domain. Diagram berikut merangkum relasi domain utama berdasarkan migration dan foreign key aplikasi. Tabel internal Laravel seperti `cache`, `jobs`, `sessions`, dan `password_reset_tokens` tidak ditampilkan agar diagram lebih mudah dibaca.

### 5.1 Katalog, opsi, dan inventori

```mermaid
erDiagram
    PRODUCT_CATEGORIES o|--o{ PRODUCTS : groups
    PRODUCTS ||--o{ PRODUCT_IMAGES : has
    PRODUCTS ||--o{ PRODUCT_VARIANTS : offers
    PRODUCTS ||--o{ PRODUCT_OPTIONS : defines
    PRODUCT_OPTIONS ||--o{ PRODUCT_OPTION_VALUES : contains
    PRODUCT_VARIANTS ||--o{ PRODUCT_VARIANT_OPTION_VALUES : maps
    PRODUCT_OPTION_VALUES ||--o{ PRODUCT_VARIANT_OPTION_VALUES : maps
    PRODUCT_VARIANTS o|--o{ PRODUCT_IMAGES : illustrates
    PRODUCTS o|--o{ STOCK_MOVEMENTS : tracks
    PRODUCT_VARIANTS o|--o{ STOCK_MOVEMENTS : tracks

    PRODUCT_CATEGORIES {
        uuid id PK
        string name
        string slug
    }
    PRODUCTS {
        uuid id PK
        uuid category_id FK
        string name
        string slug
        string sku
        int price
        int stock
        boolean is_active
    }
    PRODUCT_IMAGES {
        uuid id PK
        uuid product_id FK
        uuid product_variant_id FK
        string path
        boolean is_primary
    }
    PRODUCT_VARIANTS {
        uuid id PK
        uuid product_id FK
        string sku
        int price
        int stock
        int weight_gram
    }
    PRODUCT_OPTIONS {
        uuid id PK
        uuid product_id FK
        string name
        string code
    }
    PRODUCT_OPTION_VALUES {
        uuid id PK
        uuid product_option_id FK
        string value
        string color_hex
    }
    PRODUCT_VARIANT_OPTION_VALUES {
        uuid product_variant_id FK
        uuid product_option_value_id FK
    }
    STOCK_MOVEMENTS {
        uuid id PK
        uuid product_id FK
        uuid product_variant_id FK
        uuid created_by FK
        int quantity
    }
```

Satu produk dapat memiliki banyak varian dan opsi. Kombinasi varian dengan nilai opsi disimpan pada tabel pivot `product_variant_option_values`. Gambar dapat terkait ke produk atau ke varian tertentu. Pergerakan stok menyimpan referensi produk/varian dan pembuat perubahan jika tersedia.

### 5.2 Customer, keranjang, pesanan, dan review

```mermaid
erDiagram
    USERS ||--o{ USER_ADDRESSES : owns
    USERS ||--o{ CARTS : has
    PRODUCTS ||--o{ CARTS : added
    PRODUCT_VARIANTS o|--o{ CARTS : selected
    USERS ||--o{ ORDERS : places
    CARTS o|--o| ORDERS : checkout_source
    ORDERS ||--|{ ORDER_ITEMS : contains
    PRODUCTS ||--o{ ORDER_ITEMS : referenced
    PRODUCT_VARIANTS o|--o{ ORDER_ITEMS : selected
    USERS ||--o{ WISHLIST_ITEMS : saves
    PRODUCTS ||--o{ WISHLIST_ITEMS : saved
    USERS ||--o{ PRODUCT_REVIEWS : writes
    PRODUCTS ||--o{ PRODUCT_REVIEWS : receives
    PRODUCT_REVIEWS ||--o{ PRODUCT_REVIEW_MEDIA : includes

    USERS {
        uuid id PK
        string name
        string email
        string role
        string pending_email
    }
    USER_ADDRESSES {
        uuid id PK
        uuid user_id FK
        string recipient_name
        string postal_code
        boolean is_default
    }
    CARTS {
        uuid id PK
        uuid user_id FK
        uuid product_id FK
        uuid product_variant_id FK
        int quantity
    }
    ORDERS {
        uuid id PK
        uuid user_id FK
        uuid cart_id FK
        string order_number
        int subtotal
        int shipping_cost
        int total
        string status
        string payment_status
    }
    ORDER_ITEMS {
        uuid id PK
        uuid order_id FK
        uuid product_id FK
        uuid product_variant_id FK
        string product_sku
        int quantity
        int price
        int total
    }
    WISHLIST_ITEMS {
        uuid id PK
        uuid user_id FK
        uuid product_id FK
    }
    PRODUCT_REVIEWS {
        uuid id PK
        uuid product_id FK
        uuid user_id FK
        int rating
        boolean is_approved
    }
    PRODUCT_REVIEW_MEDIA {
        uuid id PK
        uuid product_review_id FK
        string path
        string media_type
    }
```

`order_items` menyimpan snapshot nama produk, SKU, nama varian, harga, dan kuantitas saat transaksi dibuat. Karena itu, rincian historis pesanan tidak bergantung sepenuhnya pada perubahan nama atau harga katalog setelah pembelian.

### 5.3 Fulfillment, retur, pembayaran, dan audit

```mermaid
erDiagram
    ORDERS ||--o{ ORDER_SHIPMENTS : ships
    ORDERS ||--o| ORDER_CANCELLATION_REQUESTS : cancellation
    ORDERS ||--o| ORDER_RETURN_REQUESTS : return
    ORDER_RETURN_REQUESTS ||--o{ ORDER_RETURN_REVISIONS : revisions
    ORDERS o|--o{ PAYMENT_WEBHOOK_LOGS : receives
    ORDERS ||--o{ PAYMENT_RECONCILIATION_CHECKS : checks
    ORDERS ||--o{ PAYMENT_RECONCILIATION_ALERTS : alerts
    ORDERS ||--o{ PAYMENT_STATUS_RESOLUTIONS : resolves
    ORDERS o|--o{ BITESHIP_WEBHOOK_LOGS : receives
    ORDERS ||--o{ BITESHIP_RECONCILIATION_CHECKS : checks
    ORDERS o|--o{ EMAIL_DELIVERY_LOGS : notifies
    USERS o|--o{ ADMIN_ACTIVITY_LOGS : acts
    USERS o|--o{ CUSTOMER_ACTIVITY_LOGS : acts

    ORDER_SHIPMENTS {
        uuid id PK
        uuid order_id FK
        int attempt
        string reference_id
        string biteship_order_id
        string waybill_id
        string status
    }
    ORDER_CANCELLATION_REQUESTS {
        uuid id PK
        uuid order_id FK
        uuid user_id FK
        string status
        string reason
    }
    ORDER_RETURN_REQUESTS {
        uuid id PK
        uuid order_id FK
        uuid user_id FK
        string status
        string reason
    }
    ORDER_RETURN_REVISIONS {
        uuid id PK
        uuid order_return_request_id FK
        uuid requested_by FK
        string status
    }
    PAYMENT_WEBHOOK_LOGS {
        uuid id PK
        uuid order_id FK
        string transaction_status
        boolean signature_valid
    }
    PAYMENT_RECONCILIATION_CHECKS {
        uuid id PK
        uuid order_id FK
        uuid checked_by FK
        string result
    }
    PAYMENT_RECONCILIATION_ALERTS {
        bigint id PK
        uuid order_id FK
        string finding_code
    }
    PAYMENT_STATUS_RESOLUTIONS {
        uuid id PK
        uuid order_id FK
        uuid admin_id FK
        string previous_status
        string new_status
    }
    BITESHIP_WEBHOOK_LOGS {
        uuid id PK
        uuid order_id FK
        string reference_id
        string remote_status
    }
    BITESHIP_RECONCILIATION_CHECKS {
        uuid id PK
        uuid order_id FK
        uuid checked_by FK
        string result
    }
    EMAIL_DELIVERY_LOGS {
        uuid id PK
        uuid order_id FK
        string recipient
        string status
    }
    ADMIN_ACTIVITY_LOGS {
        uuid id PK
        uuid user_id FK
        string action
        string subject_type
    }
    CUSTOMER_ACTIVITY_LOGS {
        uuid id PK
        uuid user_id FK
        string action
        string subject_type
    }
```

Relasi `order_id` pada email delivery log dan webhook log dapat bernilai null karena tidak semua email atau webhook terkait langsung ke satu pesanan. Relasi actor pada audit juga nullable agar riwayat tetap dapat disimpan ketika akun terkait sudah tidak tersedia. `payment_reconciliation_alerts.id` menggunakan auto-increment bigint, sedangkan sebagian besar tabel domain menggunakan UUID.

> Diagram ini menampilkan relasi database yang didefinisikan melalui foreign key migration. Kolom kode wilayah pada alamat tidak digambarkan sebagai foreign key ke tabel package wilayah.

---

## 6. User Flow

### 6.1 Customer — belanja sampai pesanan selesai

```mermaid
flowchart TD
    A[Customer membuka RevStore] --> B{Sudah punya akun?}
    B -->|Belum| C[Registrasi]
    B -->|Sudah| D[Login]
    C --> D
    D --> E[Jelajahi katalog]
    E --> F[Detail produk dan pilih varian]
    F --> G[Tambahkan ke keranjang]
    G --> H[Checkout]
    H --> I[Pilih alamat dan tarif pengiriman]
    I --> J[Buat pesanan]
    J --> K[Pembayaran Midtrans]
    K --> L{Pembayaran terverifikasi?}
    L -->|Tidak / gagal| M[Periksa status atau coba pembayaran lagi]
    M --> K
    L -->|Ya| N[Pesanan diproses admin]
    N --> O[Shipment dibuat melalui Biteship]
    O --> P[Webhook memperbarui tracking]
    P --> Q[Customer menerima pesanan]
    Q --> R[Konfirmasi penerimaan]
    R --> S[Pesanan completed]
    S --> T[Tulis ulasan produk]
```

### 6.2 Admin — persiapan toko dan fulfillment

```mermaid
flowchart TD
    A[Admin login] --> B[Dashboard]
    B --> C[Kelola kategori dan produk]
    C --> D[Atur opsi, varian, SKU, harga, dan stok]
    D --> E[Kelola inventory]
    E --> F[Terima pesanan customer]
    F --> G{Pembayaran sudah paid?}
    G -->|Belum| H[Menunggu verifikasi pembayaran]
    G -->|Sudah| I[Proses pesanan]
    I --> J[Buat shipment Biteship]
    J --> K{Shipment berhasil?}
    K -->|Tidak| L[Periksa Biteship audit dan tindak lanjuti]
    L --> J
    K -->|Ya| M[Webhook dan tracking]
    M --> N[Monitoring sampai delivered]
    N --> O[Audit dan rekonsiliasi]
```

### 6.3 Pembatalan dan pengembalian

```mermaid
flowchart TD
    A[Customer membuka pesanan] --> B{Jenis pengajuan}
    B -->|Pembatalan| C[Ajukan pembatalan]
    C --> D[Admin meninjau]
    D --> E{Disetujui?}
    E -->|Tidak| F[Pengajuan ditolak dengan catatan]
    E -->|Ya| G{Pesanan sudah dibayar?}
    G -->|Belum| H[Batalkan dan pulihkan stok sesuai alur]
    G -->|Sudah| I[Proses refund dan verifikasi hasil]
    I --> J[Refund tercatat]
    J --> K[Pembatalan diselesaikan]
    B -->|Retur| L[Ajukan retur dengan alasan dan bukti]
    L --> M[Admin meninjau]
    M --> N{Disetujui?}
    N -->|Tidak| F
    N -->|Ya| O[Customer mengirim barang kembali]
    O --> P[Admin menerima dan memeriksa barang]
    P --> Q{Resolusi}
    Q -->|Refund| I
    Q -->|Replacement| R[Catat pengiriman pengganti]
    R --> S[Retur diselesaikan]
```

### 6.4 Email dan keamanan akun

```mermaid
flowchart TD
    A[Customer mengubah email] --> B[Masukkan email baru dan password saat ini]
    B --> C[Email baru disimpan sebagai pending]
    C --> D[Kirim tautan konfirmasi ke email baru]
    D --> E{Tautan valid dan belum kedaluwarsa?}
    E -->|Tidak| F[Email lama tetap aktif, kirim ulang jika diperlukan]
    E -->|Ya| G[Aktifkan email baru dan tandai terverifikasi]
    G --> H[Kirim notifikasi ke email lama]
    D --> I[Catat pengiriman pada Email Delivery Logs]
    H --> I
```

---

## 7. Demo Flow Customer

Gunakan akun customer demo dari bagian [Akun Demo](#4-akun-demo), atau daftarkan akun baru melalui `/register`.

### Langkah 1 — Registrasi dan login

1. Buka `/register`.
2. Isi nama, email, dan password.
3. Login melalui `/login`.
4. Untuk checkout, pastikan akun memenuhi persyaratan verifikasi email aplikasi.

![Halaman registrasi customer](docs/screenshots/03-customer-register.png)

![Halaman login customer](docs/screenshots/02-customer-login.png)

### Langkah 2 — Jelajahi katalog

1. Buka halaman utama `/`.
2. Buka `/shop` untuk melihat katalog produk.
3. Pilih kategori atau gunakan pencarian/filter yang tersedia.
4. Pilih salah satu produk untuk membuka halaman detail.

![Storefront RevStore](docs/screenshots/01-storefront-home.png)

![Katalog produk](docs/screenshots/04-storefront-shop.png)

### Langkah 3 — Detail produk dan varian

1. Periksa gambar, deskripsi, harga, dan informasi produk.
2. Pilih opsi varian seperti warna atau ukuran.
3. Periksa ketersediaan stok varian.
4. Tambahkan varian yang dipilih ke keranjang.

![Detail produk RevStore](docs/screenshots/05-product-detail.png)

### Langkah 4 — Keranjang dan checkout

1. Buka `/cart`.
2. Perbarui kuantitas atau hapus item jika diperlukan.
3. Buka `/checkout`.
4. Pilih atau tambahkan alamat pengiriman melalui `/addresses`.
5. Minta tarif pengiriman, lalu pilih kurir dan layanan.
6. Periksa subtotal produk, ongkir, dan total pembayaran.
7. Buat pesanan.

![Cart RevStore](docs/screenshots/customer-addresses.png)
![Cart RevStore](docs/screenshots/customer-cart.png)

### Langkah 5 — Pembayaran

1. Lanjutkan ke instruksi pembayaran Midtrans.
2. Gunakan skenario pembayaran sandbox.
3. Periksa halaman hasil pembayaran dan detail pesanan.
4. Pastikan status `paid` berasal dari callback/notifikasi provider yang terverifikasi, bukan sekadar redirect browser.

![Menu Pembayaran RevStore](docs/screenshots/customer-detail-order-produk-pembayaran.png)
![Pembayaran RevStore](docs/screenshots/customer-detail-order-pembayaran.png)
![Pembayaran Terverifikasi RevStore](docs/screenshots/customer-detail-order-pembayaran-terverifikasi.png)

### Langkah 6 — Tracking dan konfirmasi penerimaan

1. Buka `/orders`.
2. Pilih pesanan dan buka detailnya.
3. Periksa tab Ringkasan, Produk & Pembayaran, Pengiriman, Review, dan Pengajuan sesuai status pesanan.
4. Lihat status serta nomor resi ketika tersedia.
5. Setelah pesanan berstatus `delivered`, customer dapat mengonfirmasi penerimaan.
6. Pesanan menjadi `completed`; customer dapat menulis ulasan produk.

![Daftar Order RevStore](docs/screenshots/customer-daftar-order.png)
![Ringkasan Delivered RevStore](docs/screenshots/customer-detail-order-ringkasan-delivered.png)
---

## 8. Demo Flow Admin

Login admin menggunakan akun yang dibuat melalui `ADMIN_*` di `.env` pada `/admin/login`.

![Halaman login admin](docs/screenshots/06-admin-login.png)

### Langkah 1 — Dashboard

Buka `/dashboard` untuk melihat metrik penjualan, pesanan, customer, produk, dan ringkasan operasional.
![Admin Dashboard RevStore](docs/screenshots/admin-dashboard.png)

### Langkah 2 — Kategori, produk, dan varian

- Kategori: `/dashboard/product-categories`.
- Produk: `/dashboard/products`.
- Inventory: `/dashboard/inventory`.

Tunjukkan pembuatan dan pengeditan produk, gambar, deskripsi, kategori, SEO, opsi, nilai opsi, kombinasi varian, SKU, barcode, harga, berat, dimensi, status aktif, dan stok.
![Products RevStore](docs/screenshots/admin-products.png)
![Create Product RevStore](docs/screenshots/admin-product-create.png)

### Langkah 3 — Pesanan dan pengiriman

1. Buka `/dashboard/orders`.
2. Pilih pesanan yang sudah dibayar.
3. Periksa item, alamat penerima, subtotal, ongkir, total, dan status pembayaran.
4. Proses pesanan sesuai alur fulfillment.
5. Periksa shipment dan tracking Biteship.
6. Jika ada kegagalan sinkronisasi, gunakan halaman audit untuk mendiagnosisnya.

Status `shipped` dan `delivered` berasal dari pembaruan webhook Biteship. Status `completed` berasal dari konfirmasi penerimaan customer atau proses otomatis aplikasi.

![Admin Orders RevStore](docs/screenshots/admin-orders.png)
![Admin Orders RevStore](docs/screenshots/admin-orders-1.png)
![Admin Detail Order RevStore](docs/screenshots/admin-detail-order.png)

### Langkah 4 — Moderasi ulasan dan akun

- Moderasi ulasan: `/dashboard/reviews`.
- Pengelolaan akun admin: `/dashboard/users`.
- Profil admin: `/dashboard/profile`.
![Admin Reviews RevStore](docs/screenshots/admin-reviews.png)
![Admin Users RevStore](docs/screenshots/admin-users.png)
![Admin Profile RevStore](docs/screenshots/admin-profile.png)


### Langkah 5 — Audit dan monitoring

| Halaman | URL | Kegunaan |
|---|---|---|
| Payment reconciliation | `/dashboard/payment-reconciliation` | Pemeriksaan status transaksi dan penyelesaian perbedaan status |
| Biteship reconciliation | `/dashboard/biteship-reconciliation` | Pemeriksaan shipment dan sinkronisasi pengiriman |
| Admin activity logs | `/dashboard/activity-logs` | Riwayat aktivitas admin |
| Customer activity logs | `/dashboard/customer-activity-logs` | Aktivitas customer yang dicatat aplikasi |
| Email delivery logs | `/dashboard/email-delivery-logs` | Status, percobaan, dan error pengiriman email |
| Notifications | `/dashboard/notifications` | Notifikasi admin |

![Admin Payment Reconciliation RevStore](docs/screenshots/admin-payment-reconciliation.png)
![Admin Biteship Reconciliation](docs/screenshots/admin-biteship-audit.png)
![Admin Activity Logs RevStore](docs/screenshots/admin-activity.png)
![Customer Activity Logs RevStore](docs/screenshots/admin-customer-activity.png)
![Email Activity Logs RevStore](docs/screenshots/admin-email-logs.png)


---

## 9. Pembatalan, Retur, dan Refund

Gunakan pesanan demo terpisah untuk memperlihatkan skenario pembatalan atau retur. Jangan mengubah pesanan utama yang sedang dipakai untuk demo fulfillment normal.

### 9.1 Pembatalan pesanan

1. Customer membuka detail pesanan dan mengajukan pembatalan jika pesanan memenuhi syarat.
2. Untuk pesanan belum dibayar, pembatalan mengikuti alur pembatalan langsung dan pemulihan stok aplikasi.
3. Untuk pesanan yang sudah dibayar, permintaan masuk ke admin untuk ditinjau.
4. Admin menyetujui atau menolak permintaan. Penolakan disertai catatan.
5. Jika disetujui, admin memproses refund sesuai metode yang tersedia.
6. Untuk transaksi yang memenuhi syarat, refund dapat dikirim melalui API Midtrans. Metode manual juga tersedia sesuai alur aplikasi dan harus disertai referensi/bukti.
7. Setelah refund benar-benar berhasil dan tercatat, admin menyelesaikan pembatalan.

![Pengajuan Pembatalan RevStore](docs/screenshots/customer-detail-order-pengajuan.png)
![Pengajuan Pembatalan RevStore](docs/screenshots/customer-detail-order-pengajuan-pembatalan.png)
![Pengajuan Pembatalan RevStore](docs/screenshots/admin-detail-order-cancel.png)
![Pengajuan Pembatalan RevStore](docs/screenshots/admin-detail-order-cancel-proceed.png)
![Pengajuan Pembatalan RevStore](docs/screenshots/admin-detail-order-cancel-proceed-waiting.png)
![Pengajuan Pembatalan RevStore](docs/screenshots/admin-detail-order-cancel-proceed-confirm.png)
![Pengajuan Pembatalan RevStore](docs/screenshots/admin-detail-order-cancel-proceed-completed.png)
![Pengajuan Pembatalan RevStore](docs/screenshots/customer-detail-order-ringkasan-cancelled.png)


### 9.2 Pengembalian barang (retur)

1. Customer mengajukan retur pada pesanan yang memenuhi syarat; batas pengajuan adalah tujuh hari setelah pesanan diterima.
2. Customer memilih alasan, menulis deskripsi, dan mengunggah bukti jika diperlukan.
3. Admin meninjau dan menyetujui atau menolak pengajuan.
4. Jika disetujui, customer mengirim barang kembali dan mengisi informasi kurir serta nomor resi.
5. Admin mencatat penerimaan barang retur.
6. Admin memilih resolusi refund atau replacement.
7. Refund diproses melalui Midtrans jika memenuhi syarat atau melalui metode manual yang didukung. Replacement dicatat dengan informasi pengiriman pengganti.
8. Selesaikan retur setelah tindakan resolusi benar-benar dilakukan.

### 9.3 Prinsip verifikasi

- Jangan menyatakan refund berhasil hanya karena tombol proses refund ditekan.
- Periksa respons provider, status refund, webhook, dan catatan audit.
- Jangan menyelesaikan pembatalan berbayar sebelum refund tercatat selesai.
- Gunakan sandbox/test untuk demonstrasi pembayaran dan refund.

Panduan lebih rinci tersedia pada [`docs/ORDER-CANCELLATION.md`](docs/ORDER-CANCELLATION.md) dan [`docs/ORDER-FULFILLMENT.md`](docs/ORDER-FULFILLMENT.md).

---

## 10. Galeri Screenshot

Screenshot berikut diambil dari aplikasi RevStore yang dijalankan secara lokal. Screenshot autentikasi dan storefront bersifat publik; halaman admin setelah login dijelaskan pada demo flow dan dapat ditampilkan setelah menyiapkan akun admin lokal.

| Halaman | Screenshot |
|---|---|
| Storefront home | ![Storefront home](docs/screenshots/01-storefront-home.png) |
| Customer login | ![Customer login](docs/screenshots/02-customer-login.png) |
| Customer registration | ![Customer registration](docs/screenshots/03-customer-register.png) |
| Product catalog | ![Product catalog](docs/screenshots/04-storefront-shop.png) |
| Product detail | ![Product detail](docs/screenshots/05-product-detail.png) |
| Admin login | ![Admin login](docs/screenshots/06-admin-login.png) |

---

## 11. Struktur Proyek dan Dokumentasi

```text
ecommerce10/
├── app/
│   ├── Http/Controllers/     # Controller customer dan admin
│   ├── Models/               # Model dan relasi Eloquent
│   ├── Notifications/        # Email dan notifikasi
│   └── Services/             # Integrasi dan business logic
├── config/                   # Konfigurasi aplikasi dan integrasi
├── database/
│   ├── migrations/           # Skema dan relasi database
│   ├── factories/            # Factory testing/demo
│   └── seeders/              # Data awal aplikasi
├── docs/                     # Panduan, demo flow, dan screenshot
├── resources/
│   ├── css/                  # Styling
│   ├── js/                   # JavaScript dan Alpine components
│   └── views/                # Blade storefront, admin, dan email
├── routes/                   # Web dan authentication routes
├── storage/                  # File aplikasi (tidak seluruhnya di Git)
├── tests/                    # Feature dan unit tests
├── composer.json
├── package.json
└── README.md
```

### Dokumentasi pendukung

- [Demo Flow](docs/DEMO_FLOW.md) — urutan presentasi dan checklist demonstrasi.
- [Deployment](docs/DEPLOYMENT.md) — setup dari clone, konfigurasi, deployment, dan checklist rilis.
- [Shipping Setup](docs/SHIPPING-SETUP.md) — konfigurasi tarif, shipment, dan Biteship.
- [Wilayah Setup](docs/WILAYAH-SETUP.md) — dataset dan endpoint wilayah Indonesia.
- [Order Fulfillment](docs/ORDER-FULFILLMENT.md) — proses pemenuhan pesanan.
- [Order Cancellation](docs/ORDER-CANCELLATION.md) — pembatalan dan refund.
- [Presentation](docs/PRESENTATION.md) — materi sumber presentasi proyek.

---

## 12. Testing

Jalankan test suite dengan:

```bash
php artisan test
```

Atau gunakan script Composer:

```bash
composer test
```

Test menggunakan konfigurasi database testing yang terpisah. Jangan arahkan test ke database lokal atau production yang berisi data penting.

---

## 13. Lisensi dan Kredit

Proyek ini menggunakan lisensi MIT sebagaimana didefinisikan pada `composer.json`. Integrasi dan package pihak ketiga tetap mengikuti lisensi masing-masing.

Dibangun sebagai proyek e-commerce Eduwork Batch 10 dengan Laravel dan ekosistem open-source terkait.
