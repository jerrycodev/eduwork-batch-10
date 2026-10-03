<?php

require __DIR__ . '/../../koneksi.php';

$id = (int) ($_GET['id'] ?? 0);

// Ambil data jika ID ada (Mode Edit), atau fallback ke data session jika ada error
if ($id) {
    $sql = 'SELECT * FROM products WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        $_SESSION['error_message'] = 'Produk tidak ditemukan.';
        header('Location: ' . BASE_URL . 'products/index.php');
        exit;
    }

    if (is_array($result) && !empty($_SESSION['product_data'])) {
        $result = array_merge($result, $_SESSION['product_data']);
    }
} else {
    $result = [
        'name'        => $_SESSION['product_data']['name'] ?? '',
        'category'    => $_SESSION['product_data']['category'] ?? '',
        'price'       => $_SESSION['product_data']['price'] ?? '',
        'stock'       => $_SESSION['product_data']['stock'] ?? '',
        'description' => $_SESSION['product_data']['description'] ?? '',
        'image'       => $_SESSION['product_data']['image'] ?? '',
    ];
}

$title = $id ? 'Edit Produk' : 'Tambah Produk';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Tugas Sesi 8</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous" />
    <style>
        body {
            background-color: #f8f9fa;
        }

        .form-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 2.5rem;
            margin-top: 2rem;
            margin-bottom: 3rem;
        }

        .img-preview {
            max-width: 120px;
            max-height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="form-card">

                    <!-- Header dengan Navigasi -->
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <h2 class="h4 text-dark mb-0 fw-bold"><?= htmlspecialchars($title) ?></h2>
                        <a href="../index.php" class="btn btn-sm btn-outline-secondary">&larr; Kembali</a>
                    </div>

                    <!-- Notifikasi Pesan Global -->
                    <?php if (!empty($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['success_message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['error_message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <!-- Notifikasi Validasi Field Spesifik -->
                    <?php if (!empty($_SESSION['errors'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($_SESSION['errors'] as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['errors']); ?>
                    <?php endif; ?>

                    <!-- Form Utama -->
                    <form action="../crud_process/<?= $id ? 'update_product' : 'create_product' ?>.php" method="post" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" minlength="3" value="<?= htmlspecialchars($result['name'] ?? '') ?>" placeholder="Masukkan nama produk..." required>
                        </div>

                        <div class="mb-3">
                            <label for="category" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach (['Elektronik', 'Pakaian', 'Makanan', 'Aksesoris'] as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>" <?= ($result['category'] ?? '') === $category ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label fw-semibold">Harga (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="price" name="price" min="0" value="<?= htmlspecialchars($result['price'] ?? '') ?>" placeholder="0" required>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="stock" class="form-label fw-semibold">Stok <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="stock" name="stock" min="1" value="<?= htmlspecialchars($result['stock'] ?? '') ?>" placeholder="1" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Tuliskan spesifikasi atau deskripsi lengkap produk..." required><?= htmlspecialchars($result['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Gambar Produk</label>

                            <!-- Pratinjau gambar jika ada (Khusus Mode Edit) -->
                            <?php if ($id && !empty($result['image']) && file_exists(UPLOAD_DIR . basename($result['image']))): ?>
                                <div class="mb-2">
                                    <p class="small text-muted mb-1">Gambar saat ini:</p>
                                    <img src="<?= BASE_URL . htmlspecialchars($result['image']) ?>" alt="Preview" class="img-preview">
                                </div>
                            <?php endif; ?>

                            <input type="file" class="form-control" id="image" name="image" accept="image/jpeg, image/png, image/webp">
                            <div class="form-text">Format didukung: JPG, PNG, WEBP. Ukuran maksimal file: 2MB.</div>
                        </div>

                        <div class="d-grid mt-4">
                            <?php if ($id): ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                                <button type="submit" class="btn btn-primary py-2 fw-semibold">Simpan Perubahan</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-success py-2 fw-semibold">Tambah Produk Baru</button>
                            <?php endif; ?>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>
