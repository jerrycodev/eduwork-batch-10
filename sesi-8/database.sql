-- Database: `eduwork_batch_10`
CREATE DATABASE IF NOT EXISTS `eduwork_batch_10` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `eduwork_batch_10`;

-- Tabel data pengguna
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contoh Data Pengguna
INSERT INTO users (name, email, address, created_at, updated_at) VALUES
('John Doe', 'johndoe@gmail.com', '123 Main St, Anytown, USA', NOW(), NOW());

-- Tabel data produk
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `category` ENUM('Elektronik', 'Pakaian', 'Makanan', 'Aksesoris') NOT NULL,
  `price` DECIMAL(10, 0) NOT NULL,
  `stock` INT NOT NULL,
  `description` TEXT NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contoh Data Produk
INSERT INTO products (name, category, price, stock, description, image, created_at, updated_at) VALUES
('Laptop Asus ROG Strix', 'Elektronik', 18500000.00, 10, 'Laptop gaming spesifikasi tinggi dengan prosesor Intel Core i7, RAM 16GB, dan SSD 512GB.', 'laptop_asus_rog.jpg', NOW(), NOW()),
('Kemeja Flanel Kotak-Kotak', 'Pakaian', 145000.00, 50, 'Kemeja flanel lengan panjang berbahan katun premium, nyaman digunakan untuk kasual.', 'kemeja_flanel_red.jpg', NOW(), NOW()),
('Keripik Singkong Pedas 200g', 'Makanan', 15000.00, 100, 'Camilan keripik singkong renyah dengan bumbu cabai asli yang pedas dan gurih.', 'keripik_singkong.jpg', NOW(), NOW()),
('Jam Tangan Digital Sport', 'Aksesoris', 350000.00, 25, 'Jam tangan tahan air (water resistant) dilengkapi fitur stopwatch, alarm, dan lampu LED.', 'jam_tangan_sport.jpg', NOW(), NOW()),
('Wireless Earbuds Bluetooth 5.3', 'Elektronik', 275000.00, 35, 'TWS dengan kualitas suara bass mantap, baterai tahan hingga 20 jam, dan delay rendah.', 'tws_bluetooth.jpg', NOW(), NOW());
