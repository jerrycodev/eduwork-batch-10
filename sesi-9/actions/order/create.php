<?php
require $_SERVER['DOCUMENT_ROOT'] . '/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// Pastikan keranjang tidak kosong
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    $_SESSION['error_message'] = 'Your cart is empty.';
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// Sanitasi & Validasi Input Form
$user_name = trim($_POST['user_name'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$address   = trim($_POST['address'] ?? '');

if (empty($user_name) || empty($phone) || empty($address)) {
    $_SESSION['error_message'] = 'All shipping fields are required.';
    header("Location: " . BASE_URL . "checkout.php");
    exit;
}

try {
    // Mulai Database Transaction
    $pdo->beginTransaction();

    // 1. Ambil info produk berdasarkan ID di session cart
    $cart_keys = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($cart_keys), '?'));
    $sql_products = "SELECT id, name, price, stock FROM products WHERE id IN ($placeholders) FOR UPDATE";

    $stmt_products = $pdo->prepare($sql_products);
    $stmt_products->execute($cart_keys);
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

    // Hitung total harga riil dari server side
    $total_price = 0;
    $order_details = [];

    if (count($products) !== count($cart_keys)) {
        throw new RuntimeException('Salah satu produk di keranjang sudah tidak tersedia.');
    }

    foreach ($products as $product) {
        $qty = (int)$_SESSION['cart'][$product['id']];
        if ($qty < 1 || $qty > (int) $product['stock']) {
            throw new RuntimeException("Stok produk {$product['name']} tidak mencukupi.");
        }
        $subtotal = $product['price'] * $qty;
        $total_price += $subtotal;

        // Simpan data item sementara
        $order_details[] = [
            'product_id'   => $product['id'],
            'product_name' => $product['name'],
            'quantity'     => $qty,
            'total_price'  => $subtotal
        ];

        $stmt_stock = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id');
        $stmt_stock->execute([
            ':quantity' => $qty,
            ':id' => $product['id'],
        ]);
    }

    // 2. Insert data ke dalam tabel orders
    $sql_order = "INSERT INTO orders (user_name, address, phone, total_price, status, created_at) 
                  VALUES (:user_name, :address, :phone, :total_price, 'pending', NOW())";

    $stmt_order = $pdo->prepare($sql_order);
    $stmt_order->execute([
        ':user_name'   => $user_name,
        ':address'     => $address,
        ':phone'       => $phone,
        ':total_price' => $total_price
    ]);

    // Dapatkan ID order terbaru
    $order_id = $pdo->lastInsertId();

    // 3. Insert rincian produk ke tabel order_items
    $sql_item = "INSERT INTO order_items (order_id, product_id, product_name, quantity, total_price) 
                 VALUES (:order_id, :product_id, :product_name, :quantity, :total_price)";
    $stmt_item = $pdo->prepare($sql_item);

    foreach ($order_details as $item) {
        $stmt_item->execute([
            ':order_id'     => $order_id,
            ':product_id'   => $item['product_id'],
            ':product_name' => $item['product_name'],
            ':quantity'     => $item['quantity'],
            ':total_price'  => $item['total_price']
        ]);
    }

    // Jika sukses, commit perubahan ke database
    $pdo->commit();

    // Kosongkan keranjang belanja
    unset($_SESSION['cart']);

    $_SESSION['success_message'] = "Thank you! Your order has been placed successfully.";
    header("Location: " . BASE_URL . "/invoice.php?order_id=" . $order_id);
    exit;
} catch (Exception $e) {
    // Jika gagal, batalkan semua transaksi data
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error_message'] = 'Failed to process order. Error: ' . $e->getMessage();
    header("Location: " . BASE_URL . "/checkout.php");
    exit;
}
