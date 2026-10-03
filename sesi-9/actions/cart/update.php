<?php
require $_SERVER['DOCUMENT_ROOT'] . '/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : BASE_URL . "/index.php";

$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
// Jika input quantity tidak diisi atau kosong, anggap nilainya 0
$product_qty = isset($_POST['quantity']) && $_POST['quantity'] !== '' ? (int) $_POST['quantity'] : 0;

// 1. Jika quantity kosong atau 0, hapus produk dari cart (jika ada)
if ($product_qty <= 0) {
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['success_message'] = 'Product removed from cart.';
    } else {
        $_SESSION['error_message'] = 'Product is not in your cart.';
    }
    // Alihkan kembali ke halaman asal (cart.php atau index.php)
    header("Location: $referer");
    exit;
}

// 2. Ambil data produk untuk validasi keberadaan barang di database
$sql = 'SELECT * FROM products WHERE id = :id';
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $product_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    $_SESSION['error_message'] = 'Product not found.';
    header("Location: $referer");
    exit;
}

if ($product_qty > (int) $result['stock']) {
    $_SESSION['error_message'] = 'Jumlah produk melebihi stok yang tersedia.';
    header("Location: $referer");
    exit;
}

// 3. Fitur Update & Tambah Otomatis
if (isset($_SESSION['cart'][$product_id])) {
    // Jika dikirim dari halaman cart (biasanya untuk update total spesifik), 
    // atau jika ingin menambah kuantitas yang sudah ada:
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $_SESSION['cart'][$product_id] = $product_qty; // Timpa dengan quantity baru
        $_SESSION['success_message'] = 'Cart updated successfully.';
    } else {
        $new_qty = $_SESSION['cart'][$product_id] + $product_qty;
        if ($new_qty > (int) $result['stock']) {
            $_SESSION['error_message'] = 'Jumlah produk melebihi stok yang tersedia.';
            header("Location: $referer");
            exit;
        }
        $_SESSION['cart'][$product_id] = $new_qty; // Akumulasikan (+1, +2, dst)
        $_SESSION['success_message'] = 'Product quantity updated in cart.';
    }
} else {
    // Jika product_id belum ada di cart, langsung tambahkan baru
    $_SESSION['cart'][$product_id] = $product_qty;
    $_SESSION['success_message'] = 'Product added to cart.';
}

// Alihkan kembali ke halaman terakhir pengguna mengakses tombol
header("Location: $referer");
exit;
