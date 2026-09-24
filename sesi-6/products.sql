-- Tabel Products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(255) NOT NULL,
    harga INT NOT NULL,
    deskripsi TEXT,
    stok INT DEFAULT 0
);

-- Create: Menambahkan produk baru
INSERT INTO products (nama_produk, harga, deskripsi, stok) VALUES
('Laptop ASUS', 12000000, 'Laptop untuk kebutuhan kerja', 10),
('Mouse Logitech', 250000, 'Mouse wireless ergonomis', 50);

-- Read: Membaca data produk
SELECT * FROM products;
SELECT * FROM products WHERE id = 1;

-- Update: Mengubah data produk
UPDATE products SET harga = 11500000 WHERE id = 1;

-- Delete: Menghapus data produk
DELETE FROM products WHERE id = 2;