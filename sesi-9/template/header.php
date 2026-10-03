<?php
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

<header class="py-4 border-bottom bg-white mb-5">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="/" class="fw-bold tracking-tight text-uppercase">Jerryco Store</a>
        <!-- Info Cart Minimalis -->
        <a href="/cart.php" class="text-secondary small">
            Cart: <span class="fw-bold text-dark"><?= $cart_count ?> items</span>
        </a>
    </div>
</header>