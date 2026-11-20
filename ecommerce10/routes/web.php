<?php

use App\Http\Controllers\AdminActivityLogController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminNewPasswordController;
use App\Http\Controllers\AdminPasswordResetLinkController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\BiteshipReconciliationController;
use App\Http\Controllers\BiteshipWebhookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailDeliveryLogController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderCancellationRequestController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderInvoiceController;
use App\Http\Controllers\OrderReturnRequestController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentReconciliationController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\WishlistController;
use App\Http\Middleware\LogAdminActivity;
use App\Http\Middleware\LogCustomerActivity;
use App\Http\Middleware\RedirectAuthenticatedFromGuestPages;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [HomeController::class, 'shop'])->name('shop');
Route::get('/product/{product:slug}', [HomeController::class, 'productDetail'])->name('product-detail');
Route::post('/product/{product}/reviews', [ProductReviewController::class, 'store'])->middleware(['auth:customer', 'customer'])->name('product-reviews.store');
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/{product}', [CartController::class, 'store'])->middleware(LogCustomerActivity::class)->name('cart.store');
Route::get('/checkout', [PaymentController::class, 'checkout'])->middleware(['auth:customer', 'customer', 'verified'])->name('checkout');
Route::post('/midtrans/callback', [PaymentController::class, 'callback'])->name('midtrans.callback');
Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/failed', [PaymentController::class, 'failed'])->name('payment.failed');
Route::post('/webhooks/biteship', [BiteshipWebhookController::class, 'handle'])->name('webhooks.biteship');

Route::prefix('/admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->middleware(RedirectAuthenticatedFromGuestPages::class)->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [AdminPasswordResetLinkController::class, 'create'])->middleware(RedirectAuthenticatedFromGuestPages::class)->name('password.request');
    Route::post('/forgot-password', [AdminPasswordResetLinkController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [AdminNewPasswordController::class, 'create'])->middleware(RedirectAuthenticatedFromGuestPages::class)->name('password.reset');
    Route::post('/reset-password', [AdminNewPasswordController::class, 'store'])->name('password.store');
});
Route::post('/admin/logout', [AdminAuthController::class, 'destroy'])->middleware('auth:admin')->name('admin.logout');

Route::prefix('/dashboard')->middleware(['auth:admin', 'admin', LogAdminActivity::class])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'adminEdit'])->name('admin.profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('admin.profile.destroy');
    Route::get('/notifications', [NotificationController::class, 'adminIndex'])->name('admin.notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('admin.notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('admin.notifications.read-all');
    Route::get('/activity-logs', [AdminActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/customer-activity-logs', [CustomerActivityLogController::class, 'index'])->name('customer-activity-logs.index');
    Route::resource('users', AdminUserController::class)->parameters(['users' => 'adminUser'])->names('admin-users')->middleware(HandlePrecognitiveRequests::class);
    Route::get('/email-delivery-logs', [EmailDeliveryLogController::class, 'index'])->name('email-delivery-logs.index');
    Route::get('/payment-reconciliation', [PaymentReconciliationController::class, 'index'])->name('payment-reconciliation.index');
    Route::get('/biteship-reconciliation', [BiteshipReconciliationController::class, 'index'])->name('biteship-reconciliation.index');
    Route::post('/orders/{order}/check-biteship', [BiteshipReconciliationController::class, 'check'])->name('biteship-reconciliation.check');
    Route::post('/orders/{order}/retry-biteship', [BiteshipReconciliationController::class, 'retry'])->name('biteship-reconciliation.retry');
    Route::post('/orders/{order}/reconcile-payment', [PaymentReconciliationController::class, 'check'])->name('payment-reconciliation.check');
    Route::post('/orders/{order}/resolve-payment', [PaymentReconciliationController::class, 'resolve'])->name('payment-reconciliation.resolve');
    Route::post('/email-delivery-logs/{emailDeliveryLog}/retry', [EmailDeliveryLogController::class, 'retry'])->name('email-delivery-logs.retry');
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
    Route::resource('products', ProductController::class)->middleware(HandlePrecognitiveRequests::class);
    Route::get('/reviews', [ProductReviewController::class, 'adminIndex'])->name('reviews.index');
    Route::patch('/reviews/{review}', [ProductReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ProductReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::resource('product-categories', ProductCategoryController::class)->middleware(HandlePrecognitiveRequests::class);
    Route::post('file-uploads', [FileUploadController::class, 'store'])->name('file-uploads.store');
    Route::delete('file-uploads', [FileUploadController::class, 'destroy'])->name('file-uploads.destroy');
});

Route::get('/order-cancellations/{cancellationRequest}/refund-proof', [OrderCancellationRequestController::class, 'proof'])->middleware('customer-or-admin')->name('order-cancellations.proof');

Route::middleware(['auth:customer', 'customer', LogCustomerActivity::class])->group(function () {
    Route::get('/account', fn () => redirect()->route('orders.customer.index'))->name('account');
    Route::patch('/cart/{cart}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{cart}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
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

    Route::resource('addresses', UserAddressController::class)->except(['show'])->middleware(HandlePrecognitiveRequests::class);
    Route::patch('/addresses/{address}/default', [UserAddressController::class, 'makeDefault'])->name('addresses.default');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware(HandlePrecognitiveRequests::class)->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Order routes (create and read only)
    Route::middleware('verified')->group(function () {
        Route::post('/checkout/shipping-rates', [OrderController::class, 'shippingRates'])->name('checkout.shipping-rates');
        Route::post('/checkout', [OrderController::class, 'store'])->name('checkout.store');
    });
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.customer.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.customer.show');
    Route::get('/orders/{order}/invoice', OrderInvoiceController::class)->name('orders.invoice');
    Route::post('/orders/{order}/payment', [PaymentController::class, 'create'])->name('orders.payment.create');
    Route::post('/orders/{order}/confirm-received', [OrderController::class, 'confirmReceived'])->name('orders.confirm-received');
    Route::post('/orders/{order}/returns', [OrderReturnRequestController::class, 'store'])->name('orders.returns.store');
    Route::post('/orders/{order}/cancel', [OrderCancellationRequestController::class, 'store'])->name('orders.cancel');
});

require __DIR__.'/auth.php';
