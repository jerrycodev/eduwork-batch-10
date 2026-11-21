# Persyaratan, Pengembangan, dan Produksi

Panduan ini menjelaskan kebutuhan aplikasi, langkah menyiapkan lingkungan pengembangan dari hasil `git clone`, serta persiapan aplikasi untuk lingkungan produksi. Dokumentasi ini hanya membahas aplikasi dan proses rilis; konfigurasi sistem operasi, web server, DNS, sertifikat TLS, firewall, dan infrastruktur server tidak termasuk dalam cakupan.

Aplikasi menggunakan Laravel 13, PHP 8.3+, Composer, Vite, MySQL/MariaDB, serta package `aliziodev/laravel-wilayah` untuk data wilayah administratif Indonesia.

## 1. Persyaratan

### 1.1 Perangkat pengembangan

- Git.
- PHP 8.3 atau lebih baru beserta ekstensi PHP yang dibutuhkan Laravel.
- Composer 2.
- Node.js dan npm dengan versi yang kompatibel dengan lockfile proyek dan Vite.
- MySQL 8+ atau MariaDB versi kompatibel.
- Opsional: Docker untuk menjalankan layanan pendukung lokal seperti Mailpit.

### 1.2 Lingkungan produksi

- PHP 8.3 atau lebih baru beserta ekstensi yang dibutuhkan.
- Composer 2, atau dependency yang sudah dipasang pada tahap build/rilis.
- MySQL 8+ atau MariaDB versi kompatibel.
- Node.js dan npm pada lingkungan build, kecuali aset frontend dibangun melalui CI.
- Lingkungan runtime yang mendukung Laravel dan proses aplikasi yang berjalan terus-menerus bila diperlukan.
- URL aplikasi yang dapat diakses publik melalui HTTPS.
- Layanan SMTP untuk email transaksional.

Ekstensi PHP yang umum dibutuhkan untuk Laravel dan MySQL:

- `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`.
- `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`.
- `bcmath`, `gd`, `intl`, `zip`.

Pastikan versi PHP CLI kompatibel dengan PHP yang digunakan oleh runtime aplikasi.

Periksa perangkat pengembangan:

```bash
php -v
composer --version
node --version
npm --version
php -m
```

## 2. Menyiapkan lingkungan pengembangan

Ikuti urutan berikut setelah mendapatkan repository. Contoh di bawah menggunakan repository `eduwork-batch-10` dan direktori aplikasi `ecommerce10`.

### 2.1 Clone repository

```bash
git clone https://github.com/jerrycodev/eduwork-batch-10.git
cd eduwork-batch-10/ecommerce10
```

Jika repository menggunakan URL atau branch lain, sesuaikan URL dan nama branch dengan akses repository yang diberikan.

### 2.2 Pasang dependency

Pasang dependency PHP dan JavaScript berdasarkan lockfile yang sudah disediakan:

```bash
composer install
npm ci
```

`composer install` akan memasang package `aliziodev/laravel-wilayah` bersama dependency aplikasi lainnya. Tidak perlu menjalankan `composer require` untuk package tersebut karena sudah tercantum di `composer.json` dan `composer.lock`.

### 2.3 Siapkan file environment

Buat file `.env` dari template:

```bash
cp .env.example .env
```

Buka `.env`, lalu sesuaikan minimal konfigurasi aplikasi dan database berikut:

```dotenv
APP_NAME="Ecommerce"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce10
DB_USERNAME=root
DB_PASSWORD=password-database-lokal

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
SESSION_SECURE_COOKIE=false
```

Buat database `ecommerce10` terlebih dahulu pada MySQL/MariaDB lokal, atau sesuaikan nama database dan kredensial di atas dengan database yang sudah tersedia.

Buat application key:

```bash
php artisan key:generate
```

Jangan commit file `.env`. Setiap lingkungan harus memiliki application key dan kredensialnya sendiri.

### 2.4 Jalankan migrasi database

Setelah konfigurasi database benar, jalankan seluruh migrasi aplikasi:

```bash
php artisan migrate
```

Migrasi ini juga membuat tabel yang dibutuhkan package wilayah, seperti `provinces`, `regencies`, `districts`, dan `villages`, karena migrasinya sudah tersedia di repository.

Periksa status migrasi jika diperlukan:

```bash
php artisan migrate:status
```

### 2.5 Isi data wilayah Indonesia

RevStore menggunakan package [`aliziodev/laravel-wilayah`](https://github.com/aliziodev/laravel-wilayah) untuk menyediakan pilihan alamat bertingkat. Setelah migrasi selesai, isi dataset wilayah dengan perintah:

```bash
php artisan wilayah:seed
```

Seeder package akan mengisi data administratif Indonesia berupa:

- Provinsi.
- Kabupaten/kota.
- Kecamatan.
- Desa/kelurahan.
- Kode pos yang tersedia pada dataset.

Perintah `wilayah:seed` perlu dijalankan pada database baru agar pilihan wilayah muncul ketika customer menambahkan alamat. Perintah ini terpisah dari `php artisan db:seed` milik aplikasi.

Perintah tambahan yang berguna:

```bash
# Melihat versi dataset wilayah yang terpasang
php artisan wilayah:version

# Sinkronisasi dataset dengan data terbaru dari package
php artisan wilayah:sync

# Membersihkan cache data wilayah
php artisan wilayah:cache-clear
```

Hindari opsi `--fresh` pada database yang sudah digunakan karena opsi tersebut menghapus data wilayah sebelum melakukan seeding ulang. Untuk penggunaan normal, cukup jalankan `php artisan wilayah:seed` saat menyiapkan database baru.

Penjelasan lebih lanjut mengenai fitur dan endpoint wilayah tersedia pada [WILAYAH-SETUP.md](WILAYAH-SETUP.md).

### 2.6 Isi data awal aplikasi

Setelah migrasi dan data wilayah selesai, jalankan seeder aplikasi:

```bash
php artisan db:seed
```

Pada lingkungan `local` dan `testing`, seeder aplikasi akan:

- Membuat akun demo `demo1@ecommerce.test` dan `demo2@ecommerce.test` jika akun tersebut belum tersedia.
- Membuat data katalog contoh yang terdiri dari enam kategori dan 16 produk.
- Tidak menggandakan kategori atau produk ketika seeder dijalankan kembali.
- Tidak mereset stok varian yang sudah ada ketika seeder dijalankan kembali.

Pada lingkungan produksi, akun demo dan katalog contoh tidak dibuat.

Untuk membuat akun administrator lokal, tambahkan konfigurasi berikut ke `.env` sebelum menjalankan `php artisan db:seed`:

```dotenv
ADMIN_NAME="Administrator"
ADMIN_EMAIL=admin@example.test
ADMIN_PASSWORD=password-admin-lokal-yang-kuat
```

Jalankan seeder, lalu hapus nilai password sementara dari `.env`:

```bash
php artisan db:seed
```

Jika administrator dengan email tersebut sudah ada, seeder tidak akan mengganti password yang sudah digunakan. Seeder hanya memastikan akun tersebut memiliki role administrator.

### 2.7 Buat symbolic link storage

Jalankan perintah berikut agar file yang disimpan pada disk publik dapat diakses aplikasi:

```bash
php artisan storage:link
```

### 2.8 Siapkan email lokal dengan Mailpit (opsional)

Aplikasi mengirim email untuk proses pesanan, pembayaran terverifikasi, pemrosesan pesanan, pengiriman, dan notifikasi terkait. Email pesanan dapat berisi invoice PDF, sedangkan email pembayaran dapat berisi bukti pembayaran PDF.

Email diproses melalui queue. Jalankan worker selama pengembangan agar email tidak menumpuk di tabel `jobs`:

```bash
php artisan queue:work --tries=3 --backoff=10
```

Mailpit dapat digunakan untuk menangkap email lokal tanpa mengirimkannya ke inbox sungguhan. Jika Mailpit belum tersedia, jalankan container berikut:

```bash
docker run -d --name ecommerce-mailpit \
  -p 1025:1025 \
  -p 8025:8025 \
  axllent/mailpit:latest
```

Untuk Laravel yang berjalan langsung pada komputer host, gunakan konfigurasi berikut di `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="noreply@ecommerce.test"
MAIL_FROM_NAME="${APP_NAME}"
```

Buka kotak masuk Mailpit di `http://127.0.0.1:8025`. Jika Laravel berjalan di dalam Docker pada jaringan yang sama dengan Mailpit, gunakan nama service/container Mailpit sebagai `MAIL_HOST` (misalnya `mailpit`), bukan `127.0.0.1`.

Setelah mengubah konfigurasi email, bersihkan cache konfigurasi:

```bash
php artisan config:clear
```

Mailpit hanya untuk pengembangan. Gunakan layanan SMTP sungguhan pada lingkungan produksi.

### 2.9 Jalankan aplikasi

Buka terminal pertama untuk menjalankan Laravel:

```bash
php artisan serve
```

Buka terminal kedua untuk menjalankan Vite:

```bash
npm run dev
```

Jika fitur email/queue sedang diuji, jalankan worker pada terminal ketiga:

```bash
php artisan queue:work --tries=3 --backoff=10
```

Akses aplikasi melalui `http://127.0.0.1:8000`.

Untuk membuat build frontend:

```bash
npm run build
```

### 2.10 Akun dan alur awal demonstrasi

Setelah seeder selesai, akun demo customer yang tersedia pada lingkungan lokal adalah:

- `demo1@ecommerce.test`
- `demo2@ecommerce.test`

Akun administrator hanya tersedia jika `ADMIN_EMAIL` dan `ADMIN_PASSWORD` diisi saat menjalankan seeder. Login administrator tersedia melalui `/admin/login`, sedangkan login customer melalui `/login`.

Sebelum demonstrasi checkout, pastikan:

- Ada produk aktif dengan minimal satu varian aktif dan stok mencukupi.
- Customer memiliki alamat pengiriman yang lengkap.
- Data wilayah sudah terisi melalui `php artisan wilayah:seed`.
- Jika akan mendemokan ongkir, shipment, pembayaran, atau tracking, konfigurasi integrasi Biteship dan Midtrans sudah disiapkan menggunakan kredensial sandbox/test.

Urutan demonstrasi fitur aplikasi tersedia pada [DEMO_FLOW.md](DEMO_FLOW.md).

### 2.11 Jalankan pengujian

```bash
php artisan test
```

Jalankan seluruh pengujian sebelum mengirim perubahan. Pengujian harus menggunakan database testing terisolasi sesuai konfigurasi `phpunit.xml`, bukan database pengembangan atau produksi.

## 3. Persiapan aplikasi untuk produksi

Bagian ini hanya membahas konfigurasi dan rilis aplikasi. Pengadaan infrastruktur dan konfigurasi web server dilakukan di luar panduan ini.

### 3.1 Siapkan environment produksi

Atur environment melalui pengelola konfigurasi/secret milik platform yang digunakan. Jangan commit file `.env` produksi.

Minimal, siapkan konfigurasi setara dengan contoh berikut dan ganti seluruh nilai contoh:

```dotenv
APP_NAME="Ecommerce"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://shop.example.com
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=host-database-produksi
DB_PORT=3306
DB_DATABASE=ecommerce10
DB_USERNAME=ecommerce10
DB_PASSWORD=password-database-produksi-yang-kuat

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_HOST=host-smtp-produksi
MAIL_PORT=587
MAIL_USERNAME=username-smtp
MAIL_PASSWORD=password-smtp
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

Buat `APP_KEY` satu kali untuk instalasi produksi:

```bash
php artisan key:generate
```

Simpan key tersebut secara aman dan gunakan nilai yang sama pada setiap rilis. **Jangan membuat ulang `APP_KEY` pada instalasi yang sudah berjalan**, karena dapat membuat data terenkripsi yang ada tidak dapat dibaca.

Pastikan runtime aplikasi dapat menulis ke `storage/` dan `bootstrap/cache/`. File unggahan pada `storage/app/public` harus tetap tersedia setelah rilis baru.

### 3.2 Pasang dependency dan build aset frontend

Pasang dependency PHP produksi dan buat build frontend berdasarkan lockfile:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
```

Jika Node.js tidak tersedia pada runtime produksi, bangun direktori `public/build` pada lingkungan CI/build tepercaya dan sertakan hasil build tersebut ke dalam artefak rilis.

### 3.3 Jalankan migrasi dan siapkan data wilayah

Cadangkan database sebelum menerapkan migrasi, terutama pada instalasi yang sudah berisi data:

```bash
php artisan migrate --force
```

Pada instalasi produksi baru, isi data wilayah setelah migrasi selesai:

```bash
php artisan wilayah:seed
```

Data wilayah diperlukan agar customer dapat memilih provinsi, kabupaten/kota, kecamatan, dan desa/kelurahan saat mengelola alamat. Seeder wilayah terpisah dari seeder data contoh aplikasi.

Jangan gunakan `php artisan wilayah:seed --fresh` pada database produksi yang sudah digunakan. Jangan jalankan `migrate:fresh` atau `db:wipe` di produksi.

Buat symbolic link untuk file publik:

```bash
php artisan storage:link
```

### 3.4 Buat akun administrator produksi

Seeder aplikasi tidak membuat akun demo atau katalog contoh pada lingkungan produksi. Untuk membuat administrator pertama, berikan `ADMIN_NAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` yang kuat melalui konfigurasi environment/secret yang aman, kemudian jalankan:

```bash
php artisan db:seed --class=DatabaseSeeder --force
```

Hapus secret password sementara dari konfigurasi deployment setelah administrator berhasil dibuat. Menjalankan ulang seeder tidak akan mengganti password administrator yang sudah ada.

### 3.5 Optimalkan aplikasi

Setelah konfigurasi produksi tersedia dan dependency terpasang, jalankan:

```bash
php artisan optimize
```

Jika konfigurasi environment berubah saat rilis, bangun ulang cache konfigurasi:

```bash
php artisan optimize:clear
php artisan optimize
```

Pastikan `storage/` dan `bootstrap/cache/` dapat ditulis oleh runtime aplikasi. File unggahan harus disimpan secara persisten dan masuk ke dalam proses pencadangan.

### 3.6 Queue, scheduler, dan penyimpanan file

- Aplikasi menggunakan session, cache, dan queue berbasis database secara default. Pastikan migrasi database Laravel sudah diterapkan.
- Jalankan queue worker selama aplikasi menggunakan pekerjaan antrean. Gunakan pengelola proses pada platform untuk menjaga worker tetap berjalan dan restart worker setelah rilis dengan `php artisan queue:restart`.
- Jika aplikasi menggunakan scheduled task, atur platform untuk menjalankan `php artisan schedule:run` setiap menit.
- Pastikan direktori `storage/` dan `bootstrap/cache/` dapat ditulis oleh aplikasi.
- Pertahankan file unggahan antar-rilis. Cadangkan database dan `storage/app/public`.
- Gunakan SMTP produksi dan uji email registrasi, verifikasi, reset password, serta email transaksi.
- Pastikan queue worker mengirim invoice pesanan, bukti pembayaran, dan email pembaruan fulfillment.

### 3.7 Daftar periksa setiap rilis

Untuk setiap rilis aplikasi:

1. Aktifkan maintenance mode jika rilis membutuhkan penghentian layanan sementara.
2. Cadangkan database dan file unggahan.
3. Ambil branch/commit Git yang akan dirilis dan pasang dependency sesuai lockfile.
4. Bangun aset frontend.
5. Jalankan migrasi yang sudah ditinjau menggunakan `php artisan migrate --force`.
6. Untuk instalasi produksi baru, pastikan `php artisan wilayah:seed` sudah dijalankan. Tidak perlu mengulang seed wilayah pada setiap rilis normal.
7. Jalankan `php artisan optimize`.
8. Restart queue worker dan proses aplikasi jangka panjang jika digunakan.
9. Periksa aplikasi, lalu nonaktifkan maintenance mode.

Perintah pemeriksaan pada tingkat aplikasi:

```bash
composer audit --no-dev
php artisan migrate:status
php artisan route:list --except-vendor
php artisan about
```

Periksa melalui browser:

- HTTPS, redirect, dan secure cookie.
- Login, registrasi, dan pembatasan akses administrator.
- Daftar produk, detail produk, dan filter kategori.
- Unggah dan tampilan gambar produk.
- Pilihan wilayah saat mengelola alamat customer.
- Keranjang dan checkout.
- Pembatasan akses pesanan customer dan pengelolaan pesanan admin.
- Pengiriman email.

Jalankan pengujian otomatis pada lingkungan CI/testing sebelum rilis:

```bash
php artisan test
```

### 3.8 Cadangan data, keamanan, dan pemecahan masalah

- Cadangkan database dan file unggahan sebelum rilis maupun migrasi.
- Jangan commit `.env`, `APP_KEY`, kredensial produksi, dump database, atau file unggahan customer.
- Jangan gunakan akun demo di produksi. Gunakan password administrator yang unik.
- Jangan menjalankan `php artisan db:seed` di produksi kecuali memang sedang membuat administrator dengan environment yang dijelaskan di atas. Akun demo dan katalog contoh dilindungi oleh pemeriksaan environment aplikasi.
- Jangan menjalankan `migrate:fresh`, `db:wipe`, atau `key:generate` pada instalasi produksi yang sudah berjalan.
- Periksa log Laravel pada `storage/logs/` saat mendiagnosis error aplikasi.
- Jangan mengaktifkan `APP_DEBUG` pada aplikasi produksi yang dapat diakses publik.
- Jika gambar unggahan menghasilkan 404, periksa symbolic link storage dan pastikan file unggahan tetap tersedia setelah rilis.
- Jika pilihan wilayah kosong pada instalasi baru, pastikan migrasi sudah diterapkan dan jalankan `php artisan wilayah:seed`. Periksa juga koneksi database serta cache wilayah.

## 4. Pembaruan status pesanan secara realtime dan notifikasi

Halaman detail pesanan dapat berlangganan ke private channel `orders.{orderId}`. Ketika administrator mengubah status fulfillment atau nomor resi, Laravel mengirim event `order.status.updated` setelah transaksi database berhasil disimpan. Hanya pemilik pesanan dan administrator yang dapat mengakses private channel tersebut.

Fitur realtime bersifat opsional. Notifikasi pesanan tetap disimpan di database meskipun realtime tidak aktif, sehingga pengguna tetap dapat membacanya ketika membuka aplikasi. Konfigurasi bawaan `.env.example` tidak mengaktifkan koneksi WebSocket maupun broadcast status pesanan.

Konfigurasi bawaan:

```dotenv
BROADCAST_CONNECTION=null
REALTIME_ENABLED=false
REALTIME_ORDER_STATUS_ENABLED=false
REALTIME_NOTIFICATIONS_ENABLED=false
```

Untuk mengaktifkan Reverb, siapkan kredensial dan nilai `REVERB_*` serta `VITE_REVERB_*` yang sesuai. Kemudian aktifkan fitur realtime yang diperlukan:

```dotenv
BROADCAST_CONNECTION=reverb
REALTIME_ENABLED=true
REALTIME_ORDER_STATUS_ENABLED=true
REALTIME_NOTIFICATIONS_ENABLED=true

REVERB_APP_ID=id-aplikasi
REVERB_APP_KEY=key-aplikasi
REVERB_APP_SECRET=secret-aplikasi
REVERB_HOST=host-reverb
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_ALLOWED_ORIGINS=shop.example.com
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

`REALTIME_ENABLED` merupakan sakelar utama. `REALTIME_ORDER_STATUS_ENABLED` mengatur pembaruan langsung pada detail pesanan, sedangkan `REALTIME_NOTIFICATIONS_ENABLED` mengatur pengiriman notifikasi langsung ke private channel pengguna yang sedang login. Kedua fitur memerlukan sakelar utama aktif. Notifikasi database tetap berfungsi tanpa fitur realtime.

Untuk pengembangan lokal, gunakan kredensial Reverb lokal dan jalankan proses yang diperlukan bersama Laravel:

```bash
php artisan reverb:start
php artisan queue:work
npm run dev
```

Pada produksi, jalankan Reverb dan queue worker hanya jika pengiriman realtime diaktifkan. Pastikan proses tersebut diawasi oleh pengelola proses platform, gunakan endpoint WebSocket publik dengan TLS, dan batasi origin yang diizinkan ke domain storefront. Setelah mengubah environment, bersihkan cache konfigurasi dan restart proses Laravel, queue, serta Reverb agar menggunakan konfigurasi terbaru:

```bash
php artisan optimize:clear
php artisan queue:restart
```

Notifikasi pembayaran dibuat setelah callback Midtrans yang terverifikasi mengubah status pesanan menjadi lunas. Callback berulang tidak membuat notifikasi pembayaran berhasil atau pesanan baru secara duplikat. Administrator menerima notifikasi untuk pesanan yang baru dibayar; customer menerima konfirmasi pembayaran dan pembaruan status fulfillment/tracking berikutnya.

Jika Reverb atau queue worker tidak tersedia, halaman pesanan tetap dapat digunakan, tetapi tidak menerima pembaruan langsung sampai koneksi pulih. Memuat ulang halaman tetap menampilkan status pesanan terakhir yang tersimpan di database.
