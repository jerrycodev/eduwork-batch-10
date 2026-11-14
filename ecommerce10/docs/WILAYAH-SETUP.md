# Data Wilayah Indonesia

Aplikasi menggunakan [`aliziodev/laravel-wilayah`](https://github.com/aliziodev/laravel-wilayah) untuk dropdown alamat bertingkat dan kode pos.

## Instalasi awal

Dependency sudah ditambahkan ke `composer.json` dan `composer.lock`. Untuk environment baru:

```bash
composer install
php artisan migrate --force
php artisan wilayah:seed
```

Migrasi package membuat tabel `provinces`, `regencies`, `districts`, dan `villages`. Seeder mengisi data provinsi, kabupaten/kota, kecamatan, kelurahan/desa, termasuk kode pos yang tersedia.

## Fitur alamat

- Dropdown bertingkat Provinsi → Kabupaten/Kota → Kecamatan → Kelurahan/Desa.
- Pilihan wilayah dimuat melalui endpoint yang memerlukan autentikasi.
- Kode pos otomatis diisi dari data kelurahan/desa jika tersedia; pengguna tetap dapat mengoreksinya jika data kosong/tidak sesuai.
- Backend memverifikasi bahwa kabupaten/kota memang milik provinsi yang dipilih, kecamatan milik kabupaten/kota, dan kelurahan/desa milik kecamatan.
- Nama wilayah dan kode pos yang disimpan diambil dari database server, bukan mempercayai nama yang dikirim browser.
- Kode wilayah juga disimpan di `user_addresses` agar alamat tetap memiliki referensi administratif yang stabil.

## Endpoint

Semua endpoint berada di middleware `auth`:

| Method | Endpoint | Parameter |
| --- | --- | --- |
| GET | `/wilayah/provinces` | — |
| GET | `/wilayah/regencies` | `province` = kode provinsi |
| GET | `/wilayah/districts` | `regency` = kode kabupaten/kota |
| GET | `/wilayah/villages` | `district` = kode kecamatan |

## Memperbarui dataset

Jalankan secara berkala saat ada pembaruan dataset upstream:

```bash
php artisan wilayah:sync
php artisan wilayah:cache-clear
```

Jangan menjalankan `migrate:fresh` pada database berisi data aplikasi karena perintah tersebut menghapus seluruh tabel.
