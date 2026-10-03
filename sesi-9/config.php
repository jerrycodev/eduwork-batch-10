<?php

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$protocol = $https ? 'https://' : 'http://';

define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . '/');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('REFERER', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : BASE_URL . "index.php");
