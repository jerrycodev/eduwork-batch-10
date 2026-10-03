<?php
require $_SERVER['DOCUMENT_ROOT'] . '/koneksi.php';

// Ambil ID produk dari parameter URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    $_SESSION['error_message'] = 'Invalid product ID.';
    header('Location: ./index.php');
    exit;
}

// Jalankan query dengan INNER JOIN untuk mendapatkan nama kategori
$sql = 'SELECT p.*, c.name as category_name FROM products p 
        INNER JOIN categories c ON p.category_id = c.id 
        WHERE p.id = :id';
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika produk tidak ditemukan di database
if (!$product) {
    $_SESSION['error_message'] = 'Product not found.';
    header('Location: ./index.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'template/head.php' ?>
    <title>Product Detail - <?= htmlspecialchars($product['name']) ?></title>
</head>

<body>

    <?php include 'template/header.php' ?>

    <main class="container mb-5">
        <!-- Tombol Kembali -->
        <div class="mb-4">
            <a href="index.php" class="text-decoration-none text-secondary small fw-semibold">← Back to Products</a>
        </div>

        <?php include 'template/alert.php' ?>

        <div class="row g-5">
            <!-- Kolom Kiri: Gambar Produk -->
            <div class="col-md-6">
                <div class="card border-0 bg-white p-3 shadow-sm">
                    <?php if (!empty($product['image']) && file_exists(UPLOAD_DIR . $product['image'])): ?>
                        <img src="/uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img-detail img-fluid">
                    <?php else: ?>
                        <div class="img-placeholder-detail">No Image Available</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Kolom Kanan: Detail & Form Pembelian -->
            <div class="col-md-6">
                <div class="d-flex flex-column justify-content-between h-100">
                    <div>
                        <!-- Kategori Badge -->
                        <span class="badge bg-light text-secondary text-uppercase fw-normal mb-2 px-3 py-2 border"><?= htmlspecialchars($product['category_name']) ?></span>

                        <!-- Nama Produk -->
                        <h1 class="fs-3 fw-bold mb-2 text-dark"><?= htmlspecialchars($product['name']) ?></h1>

                        <!-- Harga -->
                        <h2 class="fs-4 fw-bold text-dark mb-4">Rp <?= number_format($product['price'], 0, ',', '.') ?></h2>

                        <!-- Garis Pembatas -->
                        <hr class="text-muted my-4">

                        <!-- Deskripsi -->
                        <h5 class="fs-6 fw-bold text-uppercase text-secondary tracking-wider mb-2" style="font-size: 0.75rem !important;">Description</h5>
                        <p class="text-secondary small lh-base mb-4" style="white-space: pre-line;"><?= htmlspecialchars($product['description']) ?></p>

                        <!-- Info Stok -->
                        <div class="mb-4 small">
                            <span class="text-secondary">Availability:</span>
                            <?php if ($product['stock'] > 0): ?>
                                <span class="text-success fw-bold">In Stock (<?= $product['stock'] ?> units)</span>
                            <?php else: ?>
                                <span class="text-danger fw-bold">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="">
                        <?php if ($product['stock'] > 0): ?>
                            <div class="card card-body border-0 bg-white p-4 shadow-sm mt-auto">
                                <form action="/actions/cart/update.php" method="POST">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                                    <div class="row g-3 align-items-end">
                                        <div class="col-4 col-md-3">
                                            <label class="form-label small text-secondary fw-semibold">Quantity</label>
                                            <input type="number" name="quantity" class="form-control form-control-sm bg-light border-0 text-center" value="1" min="1" max="<?= $product['stock'] ?>" required>
                                        </div>
                                        <div class="col-8 col-md-9">
                                            <button type="submit" class="btn btn-sm btn-dark-custom w-100 py-2 fw-semibold">+ Add to Cart</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <?php include 'template/footer.php' ?>
</body>

</html>
