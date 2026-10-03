<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['product_data'])) {
    $_SESSION['product_data'] = [];
}
if (!isset($_SESSION['success_message'])) {
    $_SESSION['success_message'] = '';
}
if (!isset($_SESSION['errors'])) {
    $_SESSION['errors'] = [];
}
if (!isset($_SESSION['error_message'])) {
    $_SESSION['error_message'] = '';
}
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}