<?php

session_start();

// Tolak akses selain POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed.');
}

// Sanitasi input (TIDAK htmlspecialchars di sini, escaping dilakukan saat output)
$name        = trim(strip_tags($_POST['product_name'] ?? ''));
$category    = trim($_POST['product_category'] ?? '');
$price       = (float) ($_POST['product_price'] ?? 0);
$stock       = (int) ($_POST['product_stock'] ?? 0);
$description = trim($_POST['product_description'] ?? '');

$old = [
    'product_name'        => $name,
    'product_category'    => $category,
    'product_price'       => $price,
    'product_stock'       => $stock,
    'product_description' => $description,
];

$errors = [];

if (strlen($name) < 3) {
    $errors['product_name'] = 'Nama produk minimal 3 karakter.';
}
if (!in_array($category, ['Elektronik', 'Pakaian', 'Makanan', 'Aksesoris'], true)) {
    $errors['product_category'] = 'Kategori produk tidak valid.';
}
if ($price <= 0) {
    $errors['product_price'] = 'Harga harus berupa angka lebih dari 0.';
}
if ($stock <= 0) {
    $errors['product_stock'] = 'Stok harus berupa angka lebih dari 0.';
}
if (strlen($description) < 10) {
    $errors['product_description'] = 'Deskripsi minimal 10 karakter.';
}

// Validasi & simpan gambar (opsional)
$image = $_FILES['product_image'] ?? null;
if ($image && $image['error'] === UPLOAD_ERR_OK) {
    $ext      = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    $maxSize  = 2 * 1024 * 1024;

    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $errors['product_image'] = 'Format gambar harus JPG, PNG, atau WEBP.';
    } elseif ($image['size'] > $maxSize) {
        $errors['product_image'] = 'Ukuran gambar maksimal 2MB.';
    } else {
        $dir = __DIR__ . '/uploads/products/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Nama file unik agar tidak menimpa file lama
        $fileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($image['tmp_name'], $dir . $fileName)) {
            $errors['product_image'] = 'Gagal menyimpan gambar ke server.';
        }
    }
}

// Kembalikan data & error ke form, lalu redirect (PRG)
if ($errors) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $old;
} else {
    unset($_SESSION['errors'], $_SESSION['old']);
    $_SESSION['success'] = 'Produk berhasil ditambahkan!';
}

header('Location: product_input_form.php');
exit;
