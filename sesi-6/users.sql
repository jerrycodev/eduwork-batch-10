-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Create: Menambahkan user baru
INSERT INTO users (nama, email, password) VALUES
('Budi Santoso', 'budi@email.com', 'password123'),
('Ani Lestari', 'ani@email.com', 'password123');

-- Read: Membaca data user
SELECT * FROM users;
SELECT * FROM users WHERE id = 1;

-- Update: Mengubah data user
UPDATE users SET nama = 'Budi Setiawan' WHERE id = 1;

-- Delete: Menghapus data user
DELETE FROM users WHERE id = 2;