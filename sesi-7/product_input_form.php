<?php
session_start();

/** Escape output supaya aman dari XSS. */
function e(string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Class Bootstrap untuk menandai input yang gagal validasi. */
function invalid(array $errors, string $key): string
{
    return isset($errors[$key]) ? 'is-invalid' : '';
}

// Ambil flash message dari session, lalu langsung hapus agar tidak muncul lagi saat refresh
$errors  = $_SESSION['errors']  ?? [];
$old     = $_SESSION['old']     ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['success']);

$categories = ['Elektronik', 'Pakaian', 'Makanan', 'Aksesoris'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas Sesi 7 - Jerryco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h4 class="mb-0">Tambah Produk</h4>
                    </div>

                    <div class="card-body p-4">

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= e($success) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="product_input_process.php" method="post" enctype="multipart/form-data" id="form">

                            <div class="mb-3">
                                <label for="product_name" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= invalid($errors, 'product_name') ?>"
                                       id="product_name" name="product_name" minlength="3"
                                       value="<?= e($old['product_name'] ?? '') ?>"
                                       placeholder="Contoh: Sepatu Lari Pria" required>
                                <?php if (isset($errors['product_name'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['product_name']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="product_category" class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select <?= invalid($errors, 'product_category') ?>"
                                        id="product_category" name="product_category" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= e($category) ?>"
                                            <?= ($old['product_category'] ?? '') === $category ? 'selected' : '' ?>>
                                            <?= e($category) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['product_category'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['product_category']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="product_price" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control <?= invalid($errors, 'product_price') ?>"
                                           id="product_price" name="product_price" step="0.01" min="0"
                                           value="<?= e($old['product_price'] ?? '') ?>"
                                           placeholder="150000" required>
                                    <?php if (isset($errors['product_price'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['product_price']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="product_stock" class="form-label">Stok <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control <?= invalid($errors, 'product_stock') ?>"
                                           id="product_stock" name="product_stock" min="1"
                                           value="<?= e($old['product_stock'] ?? '') ?>"
                                           placeholder="25" required>
                                    <?php if (isset($errors['product_stock'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['product_stock']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="product_description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                <textarea class="form-control <?= invalid($errors, 'product_description') ?>"
                                          id="product_description" name="product_description" rows="4"
                                          placeholder="Tuliskan spesifikasi produk..." required><?= e($old['product_description'] ?? '') ?></textarea>
                                <?php if (isset($errors['product_description'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['product_description']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <label for="product_image" class="form-label">Gambar Produk (opsional)</label>
                                <input type="file" class="form-control <?= invalid($errors, 'product_image') ?>"
                                       id="product_image" name="product_image" accept="image/jpeg, image/png, image/webp">
                                <div class="form-text">Format JPG, PNG, atau WEBP &mdash; maksimal 2MB.</div>
                                <?php if (isset($errors['product_image'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['product_image']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Simpan Produk</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cegah submit ganda
        document.getElementById('form').addEventListener('submit', function () {
            this.querySelector('button[type=submit]').disabled = true;
        });
    </script>
</body>

</html>
