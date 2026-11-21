# Demo Flow RevStore

Panduan ini menyusun demonstrasi RevStore dari persiapan toko oleh admin, pengalaman belanja customer, pemrosesan pesanan, hingga monitoring operasional. Urutan tersebut memperlihatkan hubungan antarf fitur dalam satu siklus transaksi, bukan sekadar menampilkan halaman secara terpisah.

## 1. Tujuan dan cakupan demo

Setelah demonstrasi, audiens diharapkan memahami bagaimana RevStore menangani:

- Pengelolaan katalog, kategori, varian, dan inventori.
- Pengalaman customer saat mencari produk, checkout, dan membayar.
- Pemrosesan pesanan, pembuatan pengiriman, dan pelacakan.
- Pembatalan, retur, dan penyelesaian refund.
- Audit aktivitas, rekonsiliasi pembayaran, dan monitoring pengiriman.

Gunakan satu produk dan satu pesanan demo yang sama ketika berpindah dari sesi customer ke sesi admin agar hubungan setiap proses mudah diikuti.

## 2. Persiapan sebelum demo

### 2.1 Aplikasi dan akun

- [ ] Aplikasi dapat dibuka dan database berisi data demo.
- [ ] Akun admin dapat digunakan untuk masuk melalui `/admin/login`.
- [ ] Akun customer dapat digunakan untuk masuk melalui `/login`.
- [ ] Customer sudah memiliki alamat pengiriman yang valid, atau siapkan waktu untuk menambahkannya saat demo.
- [ ] Siapkan satu produk aktif dengan varian aktif, harga, SKU, berat, dan stok yang cukup.
- [ ] Pastikan kategori, gambar produk, dan informasi storefront sudah layak ditampilkan.
- [ ] Pastikan akun admin dan customer menggunakan sesi/browser terpisah agar pergantian peran tidak membingungkan.

### 2.2 Integrasi dan layanan pendukung

- [ ] Untuk demo pembayaran, gunakan kredensial dan mode **sandbox/test Midtrans**, bukan transaksi produksi.
- [ ] Pastikan callback/webhook Midtrans dapat mencapai aplikasi jika ingin memperlihatkan pembaruan status pembayaran secara langsung.
- [ ] Untuk demo ongkir, shipment, dan tracking, siapkan konfigurasi Biteship serta alamat warehouse yang valid.
- [ ] Pastikan endpoint webhook Biteship dapat dijangkau jika ingin memperlihatkan status pengiriman yang diperbarui otomatis.
- [ ] Jalankan queue worker jika notifikasi atau email menggunakan queue.
- [ ] Jalankan scheduler jika ingin memperlihatkan pekerjaan terjadwal seperti auto-complete pesanan.
- [ ] Realtime order updates bersifat opsional. Aktifkan konfigurasi realtime dan broadcasting/Reverb hanya jika akan didemokan.

> **Catatan keamanan:** Jangan menampilkan API key, server key, secret webhook, kredensial akun, atau data pribadi customer di layar presentasi. Jangan memakai kartu, akun, atau transaksi pembayaran sungguhan tanpa persetujuan dan persiapan khusus.

### 2.3 Data demo yang disarankan

Siapkan data berikut sebelum presentasi:

| Data | Kebutuhan |
|---|---|
| Admin | Satu akun dengan akses dashboard |
| Customer | Satu akun customer yang dapat login dan checkout |
| Kategori | Minimal satu kategori aktif |
| Produk | Minimal satu produk aktif dan memiliki gambar |
| Varian | Minimal satu varian aktif dengan stok cukup; idealnya beberapa pilihan seperti warna atau ukuran |
| Alamat | Satu alamat customer dengan kode pos dan nomor telepon valid |
| Pesanan | Buat saat demo customer agar prosesnya dapat diikuti dari awal |

Jangan menjalankan `migrate:fresh` atau seeder yang menghapus data pada database demo/bersama hanya untuk menyiapkan presentasi. Gunakan database demo terpisah atau siapkan data melalui aplikasi.

## 3. Sesi pertama — Admin menyiapkan toko

Masuk melalui `http://localhost:8000/admin/login` menggunakan akun admin.

### 3.1 Dashboard

1. Buka `/dashboard`.
2. Tunjukkan ringkasan kondisi toko dan informasi operasional yang tersedia.
3. Jelaskan bahwa dashboard menjadi titik awal admin untuk memantau aktivitas toko.

### 3.2 Kategori produk

1. Buka **Product Categories** atau `/dashboard/product-categories`.
2. Tunjukkan daftar kategori.
3. Jika diperlukan, buat atau edit satu kategori demo.
4. Jelaskan bahwa kategori digunakan untuk membantu customer menelusuri katalog.

### 3.3 Produk, varian, dan inventori

1. Buka **Products** atau `/dashboard/products`.
2. Tunjukkan daftar produk, lalu buka halaman tambah atau edit produk.
3. Perlihatkan informasi dasar produk seperti nama, deskripsi, kategori, gambar, dan status aktif.
4. Buka bagian **Variants & Inventory**.
5. Tunjukkan pengaturan opsi/varian, kombinasi varian, SKU, harga, harga pembanding, biaya, stok, berat, dimensi, gambar varian, dan status aktif.
6. Jika relevan, perlihatkan penambahan varian baru pada halaman edit produk.
7. Buka **Inventory** atau `/dashboard/inventory` untuk memperlihatkan ringkasan stok dan pergerakan inventori.

**Poin yang disampaikan:** produk yang dijual menggunakan varian sebagai unit yang memiliki SKU, harga, dan stok masing-masing. Pastikan varian yang akan dipilih customer berstatus aktif dan stoknya mencukupi.

### 3.4 Fitur admin tambahan (opsional)

Jika waktu memungkinkan, tunjukkan secara singkat:

- **Reviews** (`/dashboard/reviews`): admin memoderasi ulasan yang dikirim customer.
- **Users** (`/dashboard/users`): pengelolaan akun admin.
- **Profile** (`/dashboard/profile`): pengaturan profil admin.

Setelah katalog siap, berpindah ke sesi customer. Gunakan produk demo yang baru saja ditunjukkan.

## 4. Sesi kedua — Customer berbelanja

Gunakan browser atau sesi terpisah dan buka `http://localhost:8000`.

### 4.1 Registrasi dan login

1. Jika belum memiliki akun, buka `/register` dan daftarkan customer demo.
2. Login melalui `/login`.
3. Jika akun sudah disiapkan sebelumnya, langsung login agar waktu demo lebih efisien.
4. Tunjukkan bahwa halaman autentikasi customer terpisah dari halaman login admin.

### 4.2 Menjelajahi katalog

1. Buka halaman utama `/`.
2. Masuk ke `/shop` untuk melihat katalog.
3. Pilih kategori atau telusuri produk yang disiapkan admin.
4. Buka halaman detail produk melalui kartu produk.
5. Tunjukkan gambar, informasi produk, harga, ketersediaan, pilihan varian, dan ulasan jika tersedia.
6. Pilih varian yang stoknya mencukupi.

### 4.3 Keranjang belanja

1. Tambahkan varian produk ke keranjang.
2. Buka `/cart`.
3. Ubah kuantitas untuk memperlihatkan pembaruan subtotal.
4. Tunjukkan validasi stok dan opsi menghapus item dari keranjang.
5. Lanjutkan ke checkout.

### 4.4 Alamat, ongkir, dan checkout

1. Pastikan customer memiliki alamat pengiriman. Kelola alamat melalui `/addresses` jika perlu.
2. Buka `/checkout`.
3. Pilih alamat/tujuan pengiriman dan lengkapi informasi penerima.
4. Minta pilihan tarif pengiriman yang tersedia.
5. Pilih kurir dan layanan pengiriman, lalu periksa ongkir dan total pembayaran.
6. Tunjukkan ringkasan produk, varian, jumlah, alamat, dan biaya sebelum membuat pesanan.
7. Buat pesanan.

**Poin yang disampaikan:** aplikasi memvalidasi kembali ketersediaan produk dan varian ketika pesanan dibuat. Stok varian akan berkurang sebagai bagian dari pencatatan transaksi.

### 4.5 Pembayaran

1. Lanjutkan ke halaman/instruksi pembayaran Midtrans.
2. Selesaikan pembayaran menggunakan skenario uji sandbox yang telah disiapkan.
3. Tunjukkan halaman hasil pembayaran dan detail pesanan.
4. Jika callback tersedia, perlihatkan perubahan status pembayaran setelah notifikasi diterima aplikasi.

Jangan menyatakan pembayaran berhasil hanya berdasarkan halaman redirect. Status pembayaran yang tersimpan dan notifikasi/callback provider menjadi acuan verifikasi.

## 5. Sesi ketiga — Admin memproses pesanan

Kembali ke sesi browser admin dan buka `http://localhost:8000/dashboard/orders`.

### 5.1 Memeriksa pesanan masuk

1. Buka daftar pesanan.
2. Cari pesanan berdasarkan nomor pesanan customer demo.
3. Buka detail pesanan.
4. Tunjukkan data customer, alamat penerima, item, varian/SKU, kuantitas, subtotal, ongkir, total, serta status pesanan dan pembayaran.
5. Jelaskan bahwa pesanan yang belum terverifikasi pembayarannya tidak boleh dilanjutkan ke fulfillment.

### 5.2 Memproses pesanan dan membuat pengiriman

1. Pastikan status pembayaran pesanan sudah `paid`.
2. Pada pesanan berstatus `pending`, ubah status ke `processing`.
3. Jelaskan bahwa aplikasi akan mencoba membuat shipment Biteship menggunakan pilihan kurir dan layanan dari checkout.
4. Tunjukkan informasi shipment/tracking yang tersimpan pada detail pesanan jika pembuatan shipment berhasil.
5. Jika pembuatan shipment gagal, tunjukkan pesan kegagalan dan halaman **Biteship audit** untuk pemeriksaan atau percobaan ulang setelah penyebabnya diperbaiki.

**Batasan alur status:** admin tidak mengubah status `shipped` atau `delivered` secara manual melalui dropdown status pesanan. Status pengiriman tersebut diperbarui dari webhook Biteship. Status `completed` ditetapkan melalui konfirmasi penerimaan customer atau proses otomatis aplikasi.

### 5.3 Tracking dan pembaruan status

1. Buka kembali detail pesanan admin.
2. Tunjukkan ID pengiriman, status Biteship, nomor resi jika tersedia, dan riwayat tracking.
3. Jelaskan bahwa pembaruan status dari kurir diterima melalui webhook dan disimpan pada pesanan.
4. Jika lingkungan demo mendukung webhook langsung, perlihatkan perubahan status setelah event pengiriman diterima.

## 6. Sesi keempat — Customer memantau pesanan

Kembali ke sesi customer.

1. Buka `/orders` untuk melihat daftar pesanan.
2. Pilih pesanan yang baru dibuat.
3. Tunjukkan tab **Ringkasan**, **Produk & Pembayaran**, **Pengiriman**, dan **Pengajuan** pada detail pesanan.
4. Perlihatkan progres pesanan dan informasi tracking yang tersedia.
5. Jika webhook Biteship telah mengirim pembaruan, tunjukkan status terbaru dan riwayat perjalanan paket.
6. Jika fitur realtime aktif, perlihatkan pembaruan status tanpa memuat ulang halaman.
7. Tunjukkan invoice atau bukti pembayaran yang dapat diunduh.

Customer dapat mengonfirmasi penerimaan ketika pesanan sudah berstatus `delivered`. Setelah konfirmasi, pesanan menjadi `completed`. Jika customer tidak melakukan konfirmasi, sistem memiliki proses auto-complete sesuai jadwal aplikasi.

## 7. Sesi kelima — Pembatalan, retur, dan refund

Bagian ini sebaiknya didemokan menggunakan pesanan demo kedua atau data yang memang disiapkan khusus. Jangan mengubah pesanan utama yang sedang dipakai untuk menunjukkan alur fulfillment normal.

### 7.1 Pembatalan pesanan

1. Dari detail pesanan customer, ajukan pembatalan ketika pesanan masih memenuhi syarat.
2. Untuk pesanan belum dibayar, pembatalan dapat diproses langsung sesuai aturan aplikasi.
3. Untuk pesanan yang sudah dibayar, customer mengirim permintaan pembatalan dan admin meninjaunya melalui detail pesanan.
4. Admin dapat menyetujui atau menolak permintaan. Penolakan harus disertai catatan.
5. Jika disetujui, pesanan ditahan sampai proses refund diselesaikan.
6. Refund dapat diproses melalui Midtrans jika transaksi memenuhi syarat, atau secara manual dengan mencatat referensi dan bukti yang diperlukan.
7. Setelah refund benar-benar berhasil dan tercatat, admin menyelesaikan pembatalan.

Jangan menandai pembatalan berbayar selesai sebelum refund benar-benar diproses. Untuk refund Midtrans, perhatikan status permintaan dan webhook/konfirmasi hasil refund.

### 7.2 Pengembalian barang (return)

1. Gunakan pesanan berstatus `delivered` atau `completed` yang masih berada dalam batas pengajuan retur, yaitu tujuh hari sejak diterima.
2. Customer mengirim alasan, deskripsi, dan bukti pendukung jika diperlukan.
3. Admin meninjau pengajuan, lalu menyetujui atau menolak. Persetujuan memerlukan informasi alamat pengembalian.
4. Jika disetujui, customer mengirim barang kembali dan mengisi kurir serta nomor resi.
5. Admin mencatat bahwa barang retur telah diterima.
6. Admin memilih penyelesaian berupa refund atau replacement.
7. Untuk refund, pilih metode Midtrans jika transaksi memenuhi syarat atau metode manual dengan referensi dan bukti transfer. Untuk replacement, catat kurir dan nomor resi pengiriman pengganti.
8. Selesaikan proses setelah langkah penyelesaian benar-benar dilakukan.

## 8. Penutup — Monitoring dan audit operasional

Tutup demo dengan kembali ke dashboard admin dan tunjukkan beberapa halaman berikut:

| Halaman | URL | Yang diperlihatkan |
|---|---|---|
| Payment audit | `/dashboard/payment-reconciliation` | Pemeriksaan status pembayaran dan rekonsiliasi transaksi |
| Biteship audit | `/dashboard/biteship-reconciliation` | Pemeriksaan status shipment, webhook, dan kendala sinkronisasi |
| Admin activity | `/dashboard/activity-logs` | Jejak aktivitas administrator |
| Customer activity | `/dashboard/customer-activity-logs` | Aktivitas customer yang dicatat aplikasi |
| Email delivery logs | `/dashboard/email-delivery-logs` | Riwayat pengiriman email dan opsi retry |
| Notifications | `/dashboard/notifications` | Notifikasi untuk admin |

Jelaskan bahwa audit dan rekonsiliasi membantu admin menindaklanjuti kondisi yang membutuhkan pemeriksaan, bukan menggantikan verifikasi transaksi pada provider.

## 9. Checklist akhir demo

- [ ] Alur admin menyiapkan kategori, produk, varian, dan stok sudah ditunjukkan.
- [ ] Customer berhasil memilih produk dan varian yang benar.
- [ ] Customer berhasil checkout dengan alamat dan layanan pengiriman.
- [ ] Status pembayaran diverifikasi dari data/callback yang diterima aplikasi.
- [ ] Admin berhasil memproses pesanan dan membuat shipment jika integrasi tersedia.
- [ ] Status dan tracking pengiriman ditunjukkan pada sisi admin dan customer.
- [ ] Pembatalan/retur dijelaskan menggunakan pesanan khusus, bukan pesanan utama.
- [ ] Halaman audit dan monitoring diperlihatkan.
- [ ] Tidak ada kredensial, secret, atau data pribadi yang terekspos di layar.
- [ ] Semua transaksi uji menggunakan lingkungan sandbox/test.

## 10. Catatan teknis untuk presenter

- Route admin berada di bawah prefix `/dashboard` dan memerlukan autentikasi admin.
- Route customer menggunakan autentikasi customer; checkout dan pembuatan pesanan juga memerlukan email terverifikasi.
- Status pembayaran dan status pengiriman adalah dua hal berbeda. Pembayaran diverifikasi melalui integrasi pembayaran, sedangkan status pengiriman diperbarui melalui integrasi kurir.
- Ketika admin mengubah pesanan `pending` menjadi `processing`, aplikasi mencoba membuat shipment Biteship. Jika gagal, periksa konfigurasi, kelengkapan warehouse, dan halaman Biteship audit sebelum mencoba ulang.
- Status `shipped` dan `delivered` berasal dari pembaruan Biteship. Jangan memaksakan perubahan status manual untuk mempercepat demo.
- Realtime order status memerlukan konfigurasi `REALTIME_ENABLED` dan `REALTIME_ORDER_STATUS_ENABLED`, koneksi broadcasting yang sesuai, serta layanan Reverb jika dipakai. Fitur realtime tidak aktif secara default pada konfigurasi contoh.
- Jalankan queue worker bila proses notifikasi/email menggunakan queue. Jalankan scheduler untuk pekerjaan berkala seperti auto-complete.
- Jika integrasi eksternal tidak tersedia saat presentasi, jelaskan bagian yang tidak dapat dijalankan dan gunakan data demo yang sudah tersimpan. Jangan mengklaim webhook, pembayaran, atau tracking berhasil tanpa bukti pada aplikasi.
