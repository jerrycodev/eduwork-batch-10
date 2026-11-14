<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderCancellationRequestController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderReturnRequestController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [HomeController::class, 'shop'])->name('shop');
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{cart}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{cart}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::get('/product/{product}', [HomeController::class, 'productDetail'])->name('product-detail');
Route::post('/product/{product}/reviews', [ProductReviewController::class, 'store'])->middleware('auth')->name('product-reviews.store');
Route::get('/checkout', [PaymentController::class, 'checkout'])->name('checkout');
Route::post('/midtrans/callback', [PaymentController::class, 'callback'])->name('midtrans.callback');

Route::prefix('/dashboard')->middleware(['admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/{product}/movements', [InventoryController::class, 'store'])->name('inventory.movements.store');
    Route::get('/orders', [OrderController::class, 'adminIndex'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'adminShow'])->name('orders.show');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status.update');
    Route::patch('/order-returns/{returnRequest}', [OrderReturnRequestController::class, 'update'])->name('order-returns.update');
    Route::post('/order-returns/{returnRequest}/revisions', [OrderReturnRequestController::class, 'requestRevision'])->name('order-returns.revisions.request');
    Route::get('/order-returns/{returnRequest}/revisions/{revision}/evidence/{index}', [OrderReturnRequestController::class, 'revisionEvidence'])->whereNumber('index')->name('order-returns.revisions.evidence');
    Route::post('/order-returns/{returnRequest}/receive', [OrderReturnRequestController::class, 'receive'])->name('order-returns.receive');
    Route::post('/order-returns/{returnRequest}/resolve', [OrderReturnRequestController::class, 'resolve'])->name('order-returns.resolve');
    Route::post('/order-returns/{returnRequest}/complete', [OrderReturnRequestController::class, 'completeResolution'])->name('order-returns.complete');
    Route::patch('/order-cancellations/{cancellationRequest}', [OrderCancellationRequestController::class, 'update'])->name('order-cancellations.update');
    Route::get('/order-returns/{returnRequest}/evidence/{index}', [OrderReturnRequestController::class, 'evidence'])->whereNumber('index')->name('order-returns.evidence');
    Route::resource('products', ProductController::class);
    Route::get('/reviews', [ProductReviewController::class, 'adminIndex'])->name('reviews.index');
    Route::patch('/reviews/{review}', [ProductReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ProductReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::resource('product-categories', ProductCategoryController::class);
    Route::post('file-uploads', [FileUploadController::class, 'store'])->name('file-uploads.store');
    Route::delete('file-uploads', [FileUploadController::class, 'destroy'])->name('file-uploads.destroy');
});

Route::middleware('auth')->group(function () {
    Route::post('/orders/returns/{returnRequest}/revisions/{revision}', [OrderReturnRequestController::class, 'submitRevision'])->name('orders.returns.revisions.submit');
    Route::get('/orders/returns/{returnRequest}/revisions/{revision}/evidence/{index}', [OrderReturnRequestController::class, 'revisionEvidence'])->whereNumber('index')->name('orders.returns.revisions.evidence');
    Route::post('/orders/returns/{returnRequest}/shipment', [OrderReturnRequestController::class, 'submitReturnShipment'])->name('orders.returns.shipment');
    Route::get('/order-returns/{returnRequest}/refund-proof', [OrderReturnRequestController::class, 'refundProof'])->name('order-returns.refund-proof');
    Route::prefix('wilayah')->name('wilayah.')->group(function () {
        Route::get('/provinces', [WilayahController::class, 'provinces'])->name('provinces');
        Route::get('/regencies', [WilayahController::class, 'regencies'])->name('regencies');
        Route::get('/districts', [WilayahController::class, 'districts'])->name('districts');
        Route::get('/villages', [WilayahController::class, 'villages'])->name('villages');
    });

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/wishlist/{wishlistItem}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

    Route::resource('addresses', UserAddressController::class)->except(['show']);
    Route::patch('/addresses/{address}/default', [UserAddressController::class, 'makeDefault'])->name('addresses.default');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Order routes (create and read only)
    Route::post('/checkout/shipping-rates', [OrderController::class, 'shippingRates'])->name('checkout.shipping-rates');
    Route::post('/checkout', [OrderController::class, 'store'])->name('checkout.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.customer.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.customer.show');
    Route::get('/order-cancellations/{cancellationRequest}/refund-proof', [OrderCancellationRequestController::class, 'proof'])->name('order-cancellations.proof');
    Route::post('/orders/{order}/payment', [PaymentController::class, 'create'])->name('orders.payment.create');
    Route::post('/orders/{order}/confirm-received', [OrderController::class, 'confirmReceived'])->name('orders.confirm-received');
    Route::post('/orders/{order}/returns', [OrderReturnRequestController::class, 'store'])->name('orders.returns.store');
    Route::post('/orders/{order}/cancel', [OrderCancellationRequestController::class, 'store'])->name('orders.cancel');
});

require __DIR__.'/auth.php';
