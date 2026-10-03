<?php
require __DIR__ . '/../../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    $_SESSION['error_message'] = 'Metode atau ID produk tidak valid.';
    header('Location: ../../products/index.php');
    exit;
}

$id = (int) $_POST['id'];

// 1. Ambil nama gambar lalu hapus filenya jika ada
$stmt = $pdo->prepare('SELECT image FROM products WHERE id = :id');
$stmt->execute([':id' => $id]);
$image = $stmt->fetchColumn();

if ($image && file_exists(UPLOAD_DIR . basename($image))) {
    unlink(UPLOAD_DIR . basename($image));
}

// 2. Hapus data produk dari database
$pdo->prepare('DELETE FROM products WHERE id = :id')->execute([':id' => $id]);

$_SESSION['success_message'] = 'Produk berhasil dihapus.';
header('Location: ../../products/index.php');
exit;
