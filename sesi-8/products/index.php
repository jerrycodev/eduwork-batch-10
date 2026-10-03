<?php

require __DIR__ . '/../koneksi.php';

$sql = 'SELECT * FROM products';
$stmt = $pdo->prepare($sql);
$stmt->execute();

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Produk - Tugas Sesi 8</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous" />
    <style>
        body {
            background-color: #f8f9fa;
        }

        .main-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-top: 2rem;
            margin-bottom: 3rem;
        }

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }

        .img-placeholder {
            width: 60px;
            height: 60px;
            background-color: #e9ecef;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 0.75rem;
            text-align: center;
            line-height: 1.2;
        }

        .table>tbody {
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <div class="container-fluid px-md-5">
        <div class="main-card">

            <!-- Header Halaman -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4 border-bottom pb-3">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">Daftar Produk</h1>
                    <p class="text-muted small mb-0">Manajemen data katalog dan stok produk Anda.</p>
                </div>
                <div>
                    <a href="/products/form/index.php" class="btn btn-success d-inline-flex align-items-center gap-2 px-3 fw-semibold">
                        <span>+</span> Tambah Produk Baru
                    </a>
                </div>
            </div>

            <!-- Notifikasi Sistem -->
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

            <!-- Tabel Data Produk -->
            <div class="table-responsive">
                <table class="table table-hover border-top">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th scope="col" style="width: 5%">#</th>
                            <th scope="col" style="width: 10%">Gambar</th>
                            <th scope="col" style="width: 20%">Nama Produk</th>
                            <th scope="col" style="width: 15%">Kategori</th>
                            <th scope="col" style="width: 15%">Harga</th>
                            <th scope="col" style="width: 10%">Stok</th>
                            <th scope="col" style="width: 15%">Deskripsi</th>
                            <th scope="col" style="width: 10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $row): ?>
                            <tr>
                                <th scope="row" class="text-muted"><?= $row['id'] ?></th>
                                <td>
                                    <?php if (!empty($row['image']) && file_exists(UPLOAD_DIR . basename($row['image']))): ?>
                                        <img src="<?= BASE_URL . htmlspecialchars($row['image']) ?>" alt="Produk" class="product-img">
                                    <?php else: ?>
                                        <div class="img-placeholder">No Img</div>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                <td><span class="badge bg-secondary-subtle text-secondary-emphasis px-2.5 py-1.5 rounded"><?= htmlspecialchars($row['category']) ?></span></td>
                                <td class="fw-medium text-dark">Rp <?= number_format($row['price'], 0, ',', '.') ?></td>
                                <td>
                                    <!-- Logika warna untuk indikasi stok kritis -->
                                    <?php if ($row['stock'] <= 5): ?>
                                        <span class="text-danger fw-bold"><?= htmlspecialchars($row['stock']) ?> <small>(Kritis)</small></span>
                                    <?php else: ?>
                                        <span class="text-dark"><?= htmlspecialchars($row['stock']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 180px;" title="<?= htmlspecialchars($row['description']) ?>">
                                        <?= htmlspecialchars($row['description']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="/products/form/index.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary px-3">Ubah</a>

                                        <form action="/products/crud_process/delete_process.php" method="post" onsubmit="return confirm('Apakah anda yakin ingin menghapus produk ini?')">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger px-3">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Kondisi jika database kosong -->
                        <?php if (empty($result)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="fs-5 mb-2">📦</div>
                                    Belum ada data produk yang terdaftar.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>
