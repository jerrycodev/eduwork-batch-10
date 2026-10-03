<?php
require 'koneksi.php';

$search_query = $_GET['search'] ?? '';
$category_filter = $_GET['category_id'] ?? null;

$categories_sql = 'SELECT * FROM categories';
$categories_stmt = $pdo->prepare($categories_sql);
$categories_stmt->execute();
$categories_result = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

$products_sql = 'SELECT p.*, c.name as category_name FROM products p INNER JOIN categories c ON p.category_id = c.id';
$product_params = [];
$conditions = [];

if ($category_filter) {
    $conditions[] = 'p.category_id = :category';
    $product_params[':category'] = $category_filter;
}

if ($search_query) {
    $conditions[] = 'p.name LIKE :search';
    $product_params[':search'] = "%$search_query%";
}

if (count($conditions) > 0) {
    $products_sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$products_stmt = $pdo->prepare($products_sql);
$products_stmt->execute($product_params);
$products_result = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <?php include 'template/alert.php' ?>
        <div class="row g-4">

            <!-- Kolom Kiri: Filter & Kategori -->
            <aside class="col-lg-3">
                <!-- Form Pencarian & Filter -->
                <div class="card card-body border-0 bg-white p-4 mb-4 shadow-sm">
                    <h5 class="fs-6 fw-bold mb-3 text-uppercase text-secondary tracking-wider" style="font-size: 0.8rem !important;">Filter Products</h5>
                    <form method="GET" action="">
                        <div class="mb-3">
                            <input type="text" name="search" class="form-control form-control-sm bg-light border-0" placeholder="Search products..." value="<?= htmlspecialchars($search_query) ?>">
                        </div>
                        <div class="mb-3">
                            <select name="category_id" class="form-select form-select-sm bg-light border-0" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories_result as $row): ?>
                                    <option value="<?= $row['id'] ?>" <?= $category_filter == $row['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-dark-custom w-100">Apply Filter</button>
                    </form>
                    <!-- Reset filters -->
                    <a href="./" class="btn btn-sm btn-outline-secondary w-100 mt-2">Reset Filters</a>
                </div>

                <!-- Daftar Kategori Sidebar -->
                <div class="card card-body border-0 bg-white p-4 shadow-sm">
                    <h5 class="fs-6 fw-bold mb-3 text-uppercase text-secondary tracking-wider" style="font-size: 0.8rem !important;">Categories</h5>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($categories_result as $row): ?>
                            <li class="py-1 text-secondary small">
                                <a href="?category_id=<?= $row['id'] ?>" class="text-decoration-none text-reset <?= $category_filter == $row['id'] ? 'fw-bold text-dark' : '' ?>">
                                    # <?= htmlspecialchars($row['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>

            <!-- Kolom Kanan: Daftar Produk -->
            <section class="col-lg-9">
                <h1 class="fs-4 fw-bold mb-4">Products</h1>

                <div class="row row-cols-1 row-cols-md-3 g-3">
                    <?php foreach ($products_result as $row): ?>
                        <div class="col">
                            <div class="card h-100 border-0 bg-white p-3 shadow-sm transition-all">
                                <div class="card-body p-0 d-flex flex-column justify-content-between">
                                    <a href="/detail_product.php?id=<?= $row['id'] ?>" class="text-decoration-none text-reset">
                                        <!-- Menampilkan gambar produk   -->
                                        <?php if (!empty($row['image']) && file_exists(UPLOAD_DIR . $row['image'])): ?>
                                            <img src="/uploads/<?= htmlspecialchars($row['image']) ?>" alt="Produk" class="product-img img-fluid">
                                        <?php else: ?>
                                            <div class="img-placeholder">No Img</div>
                                        <?php endif; ?>
                                        <!-- Menampilkan Nama Produk -->
                                        <h3 class="fs-6 fw-bold mb-1 text-dark"><?= htmlspecialchars($row['name']) ?></h3>
                                        <!-- Menampilkan Nama Kategori (Hasil INNER JOIN c.name as category_name) -->
                                        <span class="badge bg-light text-secondary fw-normal mb-3"><?= htmlspecialchars($row['category_name']) ?></span>
                                    </a>
                                    <form action="/actions/cart/update.php" method="post">
                                        <div class="d-flex justify-content-between align-items-center mt-3 border-top pt-2">
                                            <span class="fw-bold small">Rp <?= number_format($row['price'] ?? 0, 0, ',', '.') ?></span>
                                            <button class="btn btn-sm btn-outline-dark py-1 px-2" style="font-size: 0.75rem;">+ Add</button>
                                        </div>
                                        <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="quantity" value="1">
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Jika Produk Kosong -->
                    <?php if (empty($products_result)): ?>
                        <div class="col-100 w-100 text-center py-5">
                            <p class="text-muted mb-0">No products found matching your criteria.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </main>

    <?php include 'template/footer.php' ?>
</body>

</html>
