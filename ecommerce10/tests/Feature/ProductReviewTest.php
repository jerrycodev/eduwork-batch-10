<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function makePaidReviewPurchase(User $user, Product $product): Order
{
    $order = Order::query()->create([
        'user_id' => $user->id,
        'cart_id' => null,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'total' => $product->price,
        'status' => 'completed',
        'payment_status' => 'paid',
        'paid_at' => now(),
    ]);

    $order->orderItems()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => $product->price,
        'total' => $product->price,
    ]);

    return $order;
}

function makeReviewTestProduct(array $attributes = []): Product
{
    $category = ProductCategory::query()->create([
        'name' => 'Review Category',
        'slug' => 'review-category-'.uniqid(),
    ]);

    return Product::query()->create(array_merge([
        'name' => 'Review Product',
        'slug' => 'review-product-'.uniqid(),
        'description' => 'Product used for review tests.',
        'image' => null,
        'price' => 100000,
        'stock' => 10,
        'category_id' => $category->id,
    ], $attributes));
}

test('customer review is stored pending and is not displayed publicly until approved', function () {
    $customer = User::factory()->create();
    $product = makeReviewTestProduct();
    makePaidReviewPurchase($customer, $product);

    $this->actingAs($customer)
        ->post(route('product-reviews.store', $product), [
            'rating' => 5,
            'title' => 'Produk bagus',
            'comment' => 'Kualitas produk sangat baik.',
        ])
        ->assertRedirect();

    $review = ProductReview::query()->firstOrFail();
    $this->assertFalse($review->is_approved);
    $this->assertDatabaseHas('product_reviews', [
        'id' => $review->id,
        'product_id' => $product->id,
        'user_id' => $customer->id,
        'rating' => 5,
        'is_approved' => false,
    ]);

    $this->get(route('product-detail', $product))
        ->assertOk()
        ->assertDontSee('Kualitas produk sangat baik.');
});

test('customer cannot submit more than one review for the same product', function () {
    $customer = User::factory()->create();
    $product = makeReviewTestProduct();
    makePaidReviewPurchase($customer, $product);
    ProductReview::query()->create([
        'product_id' => $product->id,
        'user_id' => $customer->id,
        'rating' => 4,
        'comment' => 'Review awal yang sudah dikirim.',
    ]);

    $this->actingAs($customer)
        ->from(route('product-detail', $product))
        ->post(route('product-reviews.store', $product), [
            'rating' => 5,
            'comment' => 'Mencoba mengirim ulasan kedua.',
        ])
        ->assertRedirect(route('product-detail', $product))
        ->assertSessionHasErrors('review');

    $this->assertDatabaseCount('product_reviews', 1);
});

test('admin can approve and hide reviews and only approved reviews affect product rating', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create();
    $product = makeReviewTestProduct();
    $review = ProductReview::query()->create([
        'product_id' => $product->id,
        'user_id' => $customer->id,
        'rating' => 5,
        'comment' => 'Review untuk moderasi admin.',
    ]);

    $this->actingAs($admin)
        ->get(route('reviews.index'))
        ->assertOk()
        ->assertSee('Review untuk moderasi admin.');

    $this->patch(route('reviews.update', $review), ['is_approved' => 1])
        ->assertRedirect();
    $this->assertDatabaseHas('product_reviews', ['id' => $review->id, 'is_approved' => true]);

    $this->get(route('product-detail', $product))
        ->assertOk()
        ->assertSee('Review untuk moderasi admin.')
        ->assertSee('5.0');

    $this->patch(route('reviews.update', $review), ['is_approved' => 0])
        ->assertRedirect();
    $this->get(route('product-detail', $product))
        ->assertOk()
        ->assertDontSee('Review untuk moderasi admin.');
});

test('non-admin cannot moderate reviews and guests cannot submit reviews', function () {
    $customer = User::factory()->create();
    $otherCustomer = User::factory()->create();
    $product = makeReviewTestProduct();
    $review = ProductReview::query()->create([
        'product_id' => $product->id,
        'user_id' => $customer->id,
        'rating' => 3,
        'comment' => 'Review untuk pengujian akses.',
    ]);

    $this->post(route('product-reviews.store', $product), [
        'rating' => 5,
        'comment' => 'Guest mencoba mengirim review.',
    ])->assertRedirect(route('login'));

    $this->actingAs($otherCustomer)
        ->get(route('reviews.index'))
        ->assertRedirect(route('home'));

    $this->patch(route('reviews.update', $review), ['is_approved' => 1])
        ->assertRedirect(route('home'));

    $this->assertDatabaseHas('product_reviews', ['id' => $review->id, 'is_approved' => false]);
});

test('customer without a completed paid order cannot submit a review', function () {
    $customer = User::factory()->create();
    $product = makeReviewTestProduct();

    $this->actingAs($customer)
        ->from(route('product-detail', $product))
        ->post(route('product-reviews.store', $product), [
            'rating' => 5,
            'comment' => 'Saya belum membeli produk ini.',
        ])
        ->assertRedirect(route('product-detail', $product))
        ->assertSessionHasErrors('review');

    $this->assertDatabaseCount('product_reviews', 0);
});

test('paid customer can attach multiple images to a pending review', function () {
    Storage::fake('public');
    $customer = User::factory()->create();
    $product = makeReviewTestProduct();
    makePaidReviewPurchase($customer, $product);

    $this->actingAs($customer)
        ->post(route('product-reviews.store', $product), [
            'rating' => 5,
            'comment' => 'Produk diterima dalam kondisi baik.',
            'media' => [
                UploadedFile::fake()->image('produk-1.jpg'),
                UploadedFile::fake()->image('produk-2.png'),
            ],
        ])
        ->assertRedirect();

    $review = ProductReview::query()->with('media')->firstOrFail();
    expect($review->media)->toHaveCount(2);
    foreach ($review->media as $media) {
        Storage::disk('public')->assertExists($media->path);
        expect($media->media_type)->toBe('image');
    }
});

test('shop filters by category and price and sorts products by approved rating', function () {
    $category = ProductCategory::query()->create(['name' => 'Filter Category', 'slug' => 'filter-category']);
    $low = makeReviewTestProduct(['name' => 'Low Price', 'slug' => 'low-price', 'price' => 50000, 'category_id' => $category->id]);
    $high = makeReviewTestProduct(['name' => 'High Price', 'slug' => 'high-price', 'price' => 150000, 'category_id' => $category->id]);
    $customer = User::factory()->create();
    ProductReview::query()->create(['product_id' => $low->id, 'user_id' => $customer->id, 'rating' => 5, 'comment' => 'Rating tertinggi produk murah.', 'is_approved' => true]);
    ProductReview::query()->create(['product_id' => $high->id, 'user_id' => User::factory()->create()->id, 'rating' => 3, 'comment' => 'Rating lebih rendah produk mahal.', 'is_approved' => true]);

    $this->get(route('shop', ['category' => $category->slug, 'min_price' => 40000, 'max_price' => 100000, 'sort' => 'rating_desc']))
        ->assertOk()
        ->assertSee('Low Price')
        ->assertDontSee('High Price');

    $this->get(route('shop', ['sort' => 'rating_desc']))
        ->assertOk()
        ->assertSeeInOrder(['Low Price', 'High Price']);
});

test('confirmed paid order detail shows a review form for each product', function () {
    $customer = User::factory()->create();
    $product = makeReviewTestProduct();
    $order = makePaidReviewPurchase($customer, $product);

    $this->actingAs($customer)
        ->get(route('orders.customer.show', $order))
        ->assertOk()
        ->assertSee('Ulasan Produk')
        ->assertSee('Bagikan pengalamanmu setelah menerima produk.')
        ->assertSee(route('product-reviews.store', $product), false)
        ->assertSee('Kirim Ulasan')
        ->assertSee('review-media-'.$order->orderItems()->first()->id);
});

test('order detail shows submitted review state instead of another review form', function () {
    $customer = User::factory()->create();
    $product = makeReviewTestProduct();
    $order = makePaidReviewPurchase($customer, $product);
    ProductReview::query()->create([
        'product_id' => $product->id,
        'user_id' => $customer->id,
        'rating' => 4,
        'title' => 'Sesuai deskripsi',
        'comment' => 'Produk diterima dengan baik dan sesuai deskripsi.',
        'is_approved' => false,
    ]);

    $this->actingAs($customer)
        ->get(route('orders.customer.show', $order))
        ->assertOk()
        ->assertSee('Menunggu moderasi')
        ->assertSee('Sesuai deskripsi')
        ->assertSee('Produk diterima dengan baik dan sesuai deskripsi.')
        ->assertDontSee('Kirim Ulasan');
});

test('unconfirmed order does not show product review section', function () {
    $customer = User::factory()->create();
    $product = makeReviewTestProduct();
    $order = makePaidReviewPurchase($customer, $product);
    $order->update(['status' => 'delivered', 'confirmed_at' => null, 'completed_at' => null]);

    $this->actingAs($customer)
        ->get(route('orders.customer.show', $order))
        ->assertOk()
        ->assertDontSee('Ulasan Produk')
        ->assertDontSee('Kirim Ulasan');
});
