<?php
require 'koneksi.php';

$products_sql = 'SELECT p.*, c.name as category_name FROM products p INNER JOIN categories c ON p.category_id = c.id';
$product_params = [];
$conditions = [];

if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
    $cart_keys = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($cart_keys), '?'));
    $conditions[] = "p.id IN ($placeholders)";
    $product_params = $cart_keys;
}

if (!empty($conditions)) {
    $products_sql .= ' WHERE ' . implode(' AND ', $conditions);
    $products_stmt = $pdo->prepare($products_sql);
    $products_stmt->execute($product_params);
    $products_result = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $products_result = [];
}

$grand_total = 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'template/head.php' ?>
    <title>Tugas Sesi 9 - Jerryco</title>
</head>

<body>

    <?php include 'template/header.php' ?>

    <main class="container mb-5">
        <div class="row">
            <div class="col-12">
                <h1 class="fs-4 fw-bold mb-4">Shopping Cart</h1>

                <?php if (empty($products_result)): ?>
                    <!-- Tampilan Jika Keranjang Kosong -->
                    <div class="card border-0 bg-white p-5 shadow-sm text-center">
                        <p class="text-secondary mb-4">Your shopping cart is empty.</p>
                        <div class="d-inline-block">
                            <a href="index.php" class="btn btn-sm btn-dark-custom px-4 py-2">Go Shopping</a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Tampilan Tabel Keranjang Minimalis -->
                    <div class="card border-0 bg-white p-4 shadow-sm mb-4">
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle mb-0">
                                <thead>
                                    <tr class="border-bottom text-secondary">
                                        <th scope="col" colspan="2" class="pb-3 fw-bold text-uppercase">Product</th>
                                        <th scope="col" class="pb-3 fw-bold text-uppercase">Category</th>
                                        <th scope="col" class="pb-3 fw-bold text-uppercase text-end">Price</th>
                                        <th scope="col" class="pb-3 fw-bold text-uppercase text-center">Quantity</th>
                                        <th scope="col" class="pb-3 fw-bold text-uppercase text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products_result as $product):
                                        $qty = $_SESSION['cart'][$product['id']];
                                        $subtotal = $product['price'] * $qty;
                                        $grand_total += $subtotal;
                                    ?>
                                        <tr class="border-bottom">
                                            <!-- Gambar Mini Produk -->
                                            <td style="width: 80px;" class="py-3">
                                                <?php if (!empty($product['image']) && file_exists(UPLOAD_DIR . $product['image'])): ?>
                                                    <img src="/uploads/<?= htmlspecialchars($product['image']) ?>" alt="Product image" class="product-thumb img-thumbnail" style="width: 60px;">
                                                <?php else: ?>
                                                    <div class="thumb-placeholder">No Img</div>
                                                <?php endif; ?>
                                            </td>
                                            <!-- Nama Produk -->
                                            <td class="py-3">
                                                <span class="fw-bold d-block text-dark small"><?= htmlspecialchars($product['name']) ?></span>
                                            </td>
                                            <!-- Kategori -->
                                            <td class="py-3">
                                                <span class="badge bg-light text-secondary fw-normal"><?= htmlspecialchars($product['category_name']) ?></span>
                                            </td>
                                            <!-- Harga Satuan -->
                                            <td class="py-3 text-end small">
                                                Rp <?= number_format($product['price'], 0, ',', '.') ?>
                                            </td>
                                            <!-- Kuantitas -->
                                            <td class="py-3 text-center small fw-semibold" style="max-width: 130px;">
                                                <form method="POST" action="/actions/cart/update.php" class="d-flex align-items-center gap-1 cart-form">
                                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                    <input type="hidden" name="action" value="update">

                                                    <!-- Tombol Minus (-) -->
                                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 btn-minus" style="font-size: 0.75rem;">-</button>

                                                    <!-- Input Angka -->
                                                    <input type="number" name="quantity" min="0" value="<?= $qty ?>"
                                                        data-product-name="<?= htmlspecialchars($product['name']) ?>"
                                                        class="form-control form-control-sm bg-light border-0 text-center px-1 input-qty">

                                                    <!-- Tombol Plus (+) -->
                                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 btn-plus" style="font-size: 0.75rem;">+</button>
                                                </form>
                                            </td>


                                            <!-- Subtotal Item -->
                                            <td class="py-3 text-end fw-bold small">
                                                Rp <?= number_format($subtotal, 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- Baris Total Keseluruhan -->
                                    <tr>
                                        <td colspan="5" class="text-end pt-4 text-secondary small fw-bold">Total:</td>
                                        <td class="text-end pt-4 fw-bold fs-5 text-dark">
                                            Rp <?= number_format($grand_total, 0, ',', '.') ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tombol Aksi Bawah -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="index.php" class="text-decoration-none text-dark small fw-semibold">← Continue Shopping</a>
                        <a href="checkout.php" class="btn btn-sm btn-dark-custom px-4 py-2 fs-6 fw-semibold">Proceed to Checkout</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // 1. Logika untuk Klik Tombol Minus (-)
            document.querySelectorAll('.btn-minus').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.nextElementSibling;
                    const productName = input.getAttribute('data-product-name');
                    let currentVal = parseInt(input.value) || 0;

                    if (currentVal === 1) {
                        if (confirm(`Delete ${productName} from cart?`)) {
                            input.value = 0;
                            this.form.submit();
                        }
                    } else if (currentVal > 1) {
                        input.value = currentVal - 1;
                        this.form.submit();
                    }
                });
            });

            // 2. Logika untuk Klik Tombol Plus (+)
            document.querySelectorAll('.btn-plus').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    let currentVal = parseInt(input.value) || 0;

                    input.value = currentVal + 1;
                    this.form.submit();
                });
            });

            // 3. Logika untuk Perubahan Input Manual (Ketik Angka)
            document.querySelectorAll('.input-qty').forEach(input => {
                // Simpan nilai awal untuk mengembalikan angka jika user klik 'Cancel'
                let previousVal = input.value;

                input.addEventListener('change', function() {
                    const productName = this.getAttribute('data-product-name');
                    let currentVal = parseInt(this.value);

                    if (currentVal === 0) {
                        if (confirm(`Delete ${productName} from cart?`)) {
                            this.form.submit();
                        } else {
                            this.value = previousVal; // Kembalikan ke nilai sebelum diubah
                        }
                    } else if (currentVal > 0) {
                        this.form.submit();
                    } else {
                        this.value = previousVal; // Mencegah angka minus manual
                    }
                });
            });

        });
    </script>
    <?php include 'template/footer.php' ?>
</body>

</html>
