# Tugas Sesi 8 : PHP & MySQL

## Deskripsi

Tugas sesi 8 adalah projek sederhana berbasis PHP untuk menghubungkan aplikasi ke database MySQL menggunakan PDO dan menampilkan data pengguna dari tabel `users`.

## Fitur

- Koneksi database MySQL menggunakan PDO
- Pengambilan data pengguna dari tabel `users`
- Menampilkan nama, email, dan alamat pengguna
- Penggunaan prepared statement dan `htmlspecialchars` untuk menampilkan data dengan aman
- Inisialisasi session dan flash message

## Dibangun Menggunakan

- PHP
- MySQL
- PDO
- HTML5

## Cara Menjalankan

1. Import file `database.sql` ke MySQL.
2. Sesuaikan nama database, username, dan password pada `koneksi.php`.
3. Jalankan PHP built-in server dari dalam folder `sesi-8`:

```bash
php -S localhost:8000
```

Kemudian buka `http://localhost:8000` di browser.

## Struktur File

```
├── index.php      # Menampilkan data pengguna
├── koneksi.php    # Konfigurasi koneksi PDO ke database
├── session.php    # Inisialisasi session
├── database.sql   # Struktur dan contoh data database
└── README.md      # Deskripsi
```
