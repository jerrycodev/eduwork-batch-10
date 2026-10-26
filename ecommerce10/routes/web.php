<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [HomeController::class, 'index'])->name('shop');
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::get('/orders', [HomeController::class, 'index'])->name('orders');
Route::get('/checkout', [HomeController::class, 'index'])->name('checkout');
Route::get('/product/{product}', [HomeController::class, 'productDetail'])->name('product-detail');

Route::prefix('/dashboard')->middleware(['admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('products', ProductController::class);
    Route::resource('product-categories', ProductCategoryController::class);
    Route::post('file-uploads', [FileUploadController::class, 'store'])->name('file-uploads.store');
    Route::delete('file-uploads', [FileUploadController::class, 'destroy'])->name('file-uploads.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
