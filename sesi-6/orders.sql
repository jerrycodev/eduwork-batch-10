-- Tabel Orders
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT NOT NULL,
    total INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Create: Menambahkan pesanan baru
INSERT INTO orders (user_id, product_id, quantity, total) VALUES
(1, 1, 1, 12000000),
(1, 2, 2, 500000);

-- Read: Membaca data pesanan
SELECT * FROM orders;
SELECT o.*, u.nama AS nama_customer, p.nama_produk 
FROM orders o
JOIN users u ON o.user_id = u.id
JOIN products p ON o.product_id = p.id;

-- Update: Mengubah data pesanan
UPDATE orders SET quantity = 3, total = 750000 WHERE order_id = 2;

-- Delete: Menghapus data pesanan
DELETE FROM orders WHERE order_id = 2;