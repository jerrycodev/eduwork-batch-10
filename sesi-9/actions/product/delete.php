<?php
require $_SERVER['DOCUMENT_ROOT'] . '/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    $_SESSION['error_message'] = 'Metode atau ID produk tidak valid.';
    header("Location: " . BASE_URL . "products/index.php");
    exit;
}

$id = $_POST['id'];

// 1. Ambil nama gambar lalu hapus filenya jika ada
$stmt = $pdo->prepare('SELECT image FROM products WHERE id = :id');
$stmt->execute([':id' => $id]);
$image = $stmt->fetchColumn();

if ($image && file_exists(UPLOAD_DIR . $image)) {
    unlink(UPLOAD_DIR . $image);
}

// 2. Hapus data produk dari database
$pdo->prepare('DELETE FROM products WHERE id = :id')->execute([':id' => $id]);

$_SESSION['success_message'] = 'Produk berhasil dihapus.';
header("Location: " . REFERER);
exit;
