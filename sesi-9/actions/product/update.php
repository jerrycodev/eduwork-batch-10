<?php
require $_SERVER['DOCUMENT_ROOT'] . '/koneksi.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "products/index.php");
    exit;
}

// 1. Ambil & Sanitisasi Input
$id          = (int) ($_POST['id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$category_id = (int) ($_POST['category_id'] ?? 0);
$price       = (float) ($_POST['price'] ?? 0);
$stock       = (int) ($_POST['stock'] ?? 0);
$description = trim($_POST['description'] ?? '');
$image       = isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK ? $_FILES['image'] : null;

// Hapus error lama agar pesan dari request sebelumnya tidak ikut terbawa.
$_SESSION['errors'] = [];

// Simpan backup input untuk form jika terjadi error balik
$_SESSION['product_data'] = compact('id', 'name', 'category_id', 'price', 'stock', 'description') + ['image' => $image ? $image['name'] : null];

// 2. Validasi Input
if ($id < 1)        $_SESSION['errors']['id'] = 'ID produk tidak valid.';
if (!$name)        $_SESSION['errors']['name'] = 'Nama produk wajib diisi.';
if ($category_id < 1) $_SESSION['errors']['category_id'] = 'Kategori produk wajib diisi.';
if ($price < 0)    $_SESSION['errors']['price'] = 'Harga produk tidak boleh negatif.';
if ($stock < 1)    $_SESSION['errors']['stock'] = 'Stok produk minimal 1.';
if (!$description) $_SESSION['errors']['description'] = 'Deskripsi produk wajib diisi.';

if (!empty($_SESSION['errors'])) {
    $_SESSION['error_message'] = 'Semua field wajib diisi dengan benar.';
    header("Location: " . REFERER . "?id=" . urlencode($id));
    exit;
}

// 3. Proses Upload Gambar Baru
$fileName = null;
if ($image) {
    $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));

    // Validasi Ekstensi & Ukuran File
    $isImage = @getimagesize($image['tmp_name']) !== false;
    if (!$isImage || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || $image['size'] > 2 * 1024 * 1024) {
        $_SESSION['error_message'] = 'Gambar tidak valid (Maks 2MB, format JPG/JPEG/PNG/WEBP).';
        header("Location: " . REFERER . "?id=" . urlencode($id));
        exit;
    }

    $uploadDir = UPLOAD_DIR;
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $fileName = uniqid('product_', true) . '.' . $ext;
    if (move_uploaded_file($image['tmp_name'], $uploadDir . $fileName)) {
        // Hapus Gambar Lama dari Folder jika upload baru berhasil
        $stmt_old = $pdo->prepare('SELECT image FROM products WHERE id = :id');
        $stmt_old->execute([':id' => $id]);
        $old_image = $stmt_old->fetchColumn();
        if ($old_image && file_exists(UPLOAD_DIR . $old_image)) {
            unlink(UPLOAD_DIR . $old_image);
        }
    } else {
        $_SESSION['error_message'] = 'Gambar gagal diunggah.';
        header("Location: " . REFERER . "?id=" . urlencode($id));
        exit;
    }
}

// 4. Update Database
$sql = 'UPDATE products SET name = :name, category_id = :category_id, price = :price, stock = :stock, description = :description'
    . ($image ? ', image = :image' : '')
    . ' WHERE id = :id';

$stmt_data = compact('name', 'category_id', 'price', 'stock', 'description', 'id');
if ($image) $stmt_data['image'] = $fileName;

$pdo->prepare($sql)->execute($stmt_data);

// 5. Selesai & Redirect balik ke Index
$_SESSION['success_message'] = 'Produk berhasil diubah.';
$_SESSION['product_data'] = [];

header("Location: " . BASE_URL . "products/index.php");
exit;
