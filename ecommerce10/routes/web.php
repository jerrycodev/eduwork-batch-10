<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/products', function () {
    echo 'products';
});

Route::get('/cart', function () {
    echo 'cart';
});

Route::get('/orders', function () {
    echo 'orders';
});

Route::get('/checkout', function () {
    echo 'checkout';
});