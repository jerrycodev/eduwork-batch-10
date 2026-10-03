<?php
require 'koneksi.php';

// Redirect jika keranjang kosong
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    $_SESSION['error_message'] = 'Your cart is empty. Please add products first.';
    header('Location: ' . BASE_URL . "/index.php");
    exit;
}

// Tarik data produk yang ada di cart
$cart_keys = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($cart_keys), '?'));

$products_sql = "SELECT p.*, c.name as category_name FROM products p 
                 INNER JOIN categories c ON p.category_id = c.id 
                 WHERE p.id IN ($placeholders)";

$products_stmt = $pdo->prepare($products_sql);
$products_stmt->execute($cart_keys);
$products_result = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

$grand_total = 0;
$cart_count = count($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'template/head.php' ?>
    <title>Tugas Sesi 9 - Jerryco</title>
</head>

<body>
    <!-- Header Minimalis -->
    <header class="py-4 border-bottom bg-white mb-5">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="fw-bold tracking-tight text-uppercase text-decoration-none text-dark">Store</a>
            <a href="cart.php" class="text-secondary small text-decoration-none">
                Cart: <span class="fw-bold text-dark"><?= $cart_count ?> items</span>
            </a>
        </div>
    </header>

    <main class="container mb-5">
        <h1 class="fs-4 fw-bold mb-4">Checkout</h1>

        <div class="row g-4">
            <!-- Kolom Kiri: Form Alamat Pengiriman -->
            <div class="col-lg-7">
                <div class="card border-0 bg-white p-4 shadow-sm">
                    <h5 class="fs-6 fw-bold mb-4 text-uppercase text-secondary tracking-wider" style="font-size: 0.8rem !important;">Shipping Information</h5>

                    <form method="POST" action="/actions/order/create.php">
                        <div class="mb-3">
                            <label class="form-label small text-secondary fw-semibold">Full Name</label>
                            <input type="text" name="user_name" class="form-control form-control-sm bg-light border-0" required placeholder="e.g. John Doe">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-secondary fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control form-control-sm bg-light border-0" required placeholder="e.g. 081234567890">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-secondary fw-semibold">Shipping Address</label>
                            <textarea name="address" rows="4" class="form-control form-control-sm bg-light border-0" required placeholder="Full shipping address details..."></textarea>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                            <a href="cart.php" class="text-decoration-none text-dark small fw-semibold">← Back to Cart</a>
                            <button type="submit" class="btn btn-sm btn-dark-custom px-4 py-2 fw-semibold">Place Order</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Kolom Kanan: Ringkasan Order -->
            <div class="col-lg-5">
                <div class="card border-0 bg-white p-4 shadow-sm">
                    <h5 class="fs-6 fw-bold mb-3 text-uppercase text-secondary tracking-wider" style="font-size: 0.8rem !important;">Order Summary</h5>

                    <div class="mb-3">
                        <?php foreach ($products_result as $product):
                            $qty = $_SESSION['cart'][$product['id']];
                            $subtotal = $product['price'] * $qty;
                            $grand_total += $subtotal;
                        ?>
                            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                <div class="d-flex align-items-center gap-3">
                                    <?php if (!empty($product['image']) && file_exists(UPLOAD_DIR . $product['image'])): ?>
                                        <img src="/uploads/<?= htmlspecialchars($product['image']) ?>" class="product-thumb img-thumbnail" style="width: 60px;" alt="">
                                    <?php endif; ?>
                                    <div>
                                        <span class="fw-bold d-block small text-dark"><?= htmlspecialchars($product['name']) ?></span>
                                        <span class="text-secondary small">Qty: <?= $qty ?></span>
                                    </div>
                                </div>
                                <span class="small fw-semibold text-dark">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <span class="fw-bold text-secondary small">Total Amount:</span>
                        <span class="fw-bold fs-5 text-dark">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

</html>