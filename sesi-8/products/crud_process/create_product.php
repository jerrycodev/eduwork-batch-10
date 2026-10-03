<?php
require __DIR__ . '/../../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../products/index.php');
    exit;
}

// 1. Ambil & Sanitisasi Input
$name        = trim($_POST['name'] ?? '');
$category    = trim($_POST['category'] ?? '');
$price       = (float) ($_POST['price'] ?? 0);
$stock       = (int) ($_POST['stock'] ?? 0);
$description = trim($_POST['description'] ?? '');
$image       = isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK ? $_FILES['image'] : null;

// Simpan backup input untuk form jika terjadi error balik
$_SESSION['product_data'] = compact('name', 'category', 'price', 'stock', 'description') + ['image' => $image ? $image['name'] : null];

// 2. Validasi Input (Redirect ke halaman form jika kosong/tidak valid)
if (!$name || !$category || $price < 0 || $stock < 1 || !$description) {
    $_SESSION['error_message'] = 'Semua field wajib diisi dengan benar.';
    header('Location: ../../products/form/index.php');
    exit;
}

// 3. Proses Upload Gambar (Opsional)
$uploadPath = null;
if ($image) {
    $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    
    // Validasi Ekstensi & Ukuran File
    $isImage = @getimagesize($image['tmp_name']) !== false;
    if (!$isImage || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || $image['size'] > 2 * 1024 * 1024) {
        $_SESSION['error_message'] = 'Gambar tidak valid (Maks 2MB, format JPG/JPEG/PNG/WEBP).';
        header('Location: ../../products/form/index.php');
        exit;
    }

    $uploadDir = UPLOAD_DIR;
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $fileName = uniqid('product_', true) . '.' . $ext;
    if (move_uploaded_file($image['tmp_name'], $uploadDir . $fileName)) {
        $uploadPath = 'uploads/' . $fileName;
    } else {
        $_SESSION['error_message'] = 'Gambar gagal diunggah.';
        header('Location: ../../products/form/index.php');
        exit;
    }
}

// 4. Simpan ke Database
$sql = 'INSERT INTO products (name, category, price, stock, description, image) 
        VALUES (:name, :category, :price, :stock, :description, :image)';

$stmt_data = compact('name', 'category', 'price', 'stock', 'description') + ['image' => $uploadPath];
$pdo->prepare($sql)->execute($stmt_data);

// 5. Selesai & Redirect balik ke Index
$_SESSION['success_message'] = 'Produk berhasil ditambahkan.';
$_SESSION['product_data'] = [];

header('Location: ../../products/index.php');
exit;
