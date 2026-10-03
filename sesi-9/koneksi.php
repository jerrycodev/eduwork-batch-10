<?php

require 'session.php';
require 'config.php';
// PDO connection
$host = '127.0.0.1';
$db   = 'eduwork_batch_10';
$user = 'root';
$pass = 'password_rahasia_anda';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo 'Koneksi gagal: ' . $e->getMessage();
    exit;
}
