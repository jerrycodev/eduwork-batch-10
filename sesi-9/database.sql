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

-- Tabel data kategori
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contoh Data Kategori
INSERT INTO categories (name, created_at, updated_at) VALUES
('Elektronik', NOW(), NOW()),
('Pakaian', NOW(), NOW()),
('Makanan', NOW(), NOW()),
('Aksesoris', NOW(), NOW());

-- Tabel data produk
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `category_id` INT NOT NULL,
  `price` DECIMAL(10, 0) NOT NULL,
  `stock` INT NOT NULL,
  `description` TEXT NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contoh Data Produk
INSERT INTO products (name, category_id, price, stock, description, image, created_at, updated_at) VALUES
('Laptop Asus ROG Strix', 1, 18500000.00, 10, 'Laptop gaming spesifikasi tinggi dengan prosesor Intel Core i7, RAM 16GB, dan SSD 512GB.', 'laptop_asus_rog.jpg', NOW(), NOW()),
('Kemeja Flanel Kotak-Kotak', 2, 145000.00, 50, 'Kemeja flanel lengan panjang berbahan katun premium, nyaman digunakan untuk kasual.', 'kemeja_flanel_red.jpg', NOW(), NOW()),
('Keripik Singkong Pedas 200g', 3, 15000.00, 100, 'Camilan keripik singkong renyah dengan bumbu cabai asli yang pedas dan gurih.', 'keripik_singkong.jpg', NOW(), NOW()),
('Jam Tangan Digital Sport', 4, 350000.00, 25, 'Jam tangan tahan air (water resistant) dilengkapi fitur stopwatch, alarm, dan lampu LED.', 'jam_tangan_sport.jpg', NOW(), NOW()),
('Wireless Earbuds Bluetooth 5.3', 1, 275000.00, 35, 'TWS dengan kualitas suara bass mantap, baterai tahan hingga 20 jam, dan delay rendah.', 'tws_bluetooth.jpg', NOW(), NOW());

-- Tabel data pesanan
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_name` VARCHAR(150) NOT NULL,
  `address` TEXT NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `total_price` DECIMAL(12,0) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `total_price` DECIMAL(12,0) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `fk_order_items_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
