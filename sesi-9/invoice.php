<?php
require 'koneksi.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    $_SESSION['error_message'] = 'Invalid invoice ID.';
    header('Location: ./index.php');
    exit;
}

// 1. Ambil data utama dari tabel orders
$order_sql = "SELECT * FROM orders WHERE id = :id";
$order_stmt = $pdo->prepare($order_sql);
$order_stmt->execute([':id' => $order_id]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error_message'] = 'Invoice not found.';
    header('Location: ./index.php');
    exit;
}

// 2. Ambil data item/produk terkait dari tabel order_items
$items_sql = "SELECT * FROM order_items WHERE order_id = :order_id";
$items_stmt = $pdo->prepare($items_sql);
$items_stmt->execute([':order_id' => $order_id]);
$order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'template/head.php' ?>
    <title>Invoice #<?= $order['id'] ?> - Store</title>
</head>

<body>
    
    <?php include 'template/header.php' ?>

    <main class="container mb-5" style="max-width: 800px;">

    <?php include 'template/alert.php' ?>

        <!-- Konten Utama Invoice -->
        <div class="card border-0 bg-white p-4 p-md-5 shadow-sm">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-4 mb-4">
                <div>
                    <h2 class="fs-4 fw-bold text-uppercase invoice-title m-0">Invoice</h2>
                    <span class="text-secondary small">Order ID: #<?= $order['id'] ?></span>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-secondary text-uppercase fw-normal py-2 px-3"><?= htmlspecialchars($order['status']) ?></span>
                    <div class="text-secondary small mt-1"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></div>
                </div>
            </div>

            <!-- Detail Pengiriman -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6">
                    <h6 class="text-uppercase text-secondary fw-bold small tracking-wider mb-2" style="font-size: 0.75rem;">Billed To:</h6>
                    <p class="small m-0 fw-bold text-dark"><?= htmlspecialchars($order['user_name']) ?></p>
                    <p class="small text-secondary m-0"><?= htmlspecialchars($order['phone']) ?></p>
                </div>
                <div class="col-sm-6">
                    <h6 class="text-uppercase text-secondary fw-bold small tracking-wider mb-2" style="font-size: 0.75rem;">Shipping Address:</h6>
                    <p class="small text-secondary m-0" style="white-space: pre-line;"><?= htmlspecialchars($order['address']) ?></p>
                </div>
            </div>

            <!-- Tabel Daftar Produk -->
            <div class="table-responsive mb-4">
                <table class="table table-borderless align-middle mb-0">
                    <thead>
                        <tr class="border-bottom text-secondary" style="font-size: 0.8rem;">
                            <th scope="col" class="pb-2 text-uppercase">Product</th>
                            <th scope="col" class="pb-2 text-uppercase text-center" style="width: 100px;">Quantity</th>
                            <th scope="col" class="pb-2 text-uppercase text-end" style="width: 150px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr class="border-bottom small">
                                <td class="py-3 fw-semibold text-dark">
                                    <?= htmlspecialchars($item['product_name']) ?>
                                </td>
                                <td class="py-3 text-center">
                                    <?= $item['quantity'] ?>
                                </td>
                                <td class="py-3 text-end fw-semibold">
                                    Rp <?= number_format($item['total_price'], 0, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Total -->
                        <tr>
                            <td colspan="2" class="text-end pt-4 text-secondary fw-bold small">Total Paid:</td>
                            <td class="text-end pt-4 fw-bold fs-5 text-dark">
                                Rp <?= number_format($order['total_price'], 0, ',', '.') ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tombol Aksi Kembali -->
        <div class="mt-4 text-center">
            <a href="index.php" class="btn btn-sm btn-dark-custom px-4 py-2 fw-semibold">Continue Shopping</a>
        </div>
    </main>
    <?php include 'template/footer.php' ?>
</body>

</html>