<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [HomeController::class, 'index'])->name('shop');

Route::get('/products', [HomeController::class, 'index'])->name('home');
Route::get('/product/{id}', [HomeController::class, 'productDetail'])->name('product-detail');

Route::get('/cart', [HomeController::class, 'cart'])->name('cart');

Route::get('/orders', [HomeController::class, 'index'])->name('orders');

Route::get('/checkout', [HomeController::class, 'index'])->name('checkout');
