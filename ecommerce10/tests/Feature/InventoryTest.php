<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\User;

function inventoryTestProduct(int $stock = 0): Product
{
    $category = ProductCategory::query()->create([
        'name' => 'Inventory Category',
        'slug' => 'inventory-category',
    ]);

    return Product::query()->create([
        'name' => 'Inventory Test Product',
        'slug' => 'inventory-test-product-'.uniqid(),
        'description' => 'Product used for inventory tests.',
        'image' => 'https://example.test/product.png',
        'price' => 100000,
        'stock' => $stock,
        'weight_gram' => 500,
        'category_id' => $category->id,
    ]);
}

test('admin can restock an out of stock product and see its movement history', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $product = inventoryTestProduct();

    $this->actingAs($admin)
        ->get(route('inventory.index'))
        ->assertOk()
        ->assertSee('Inventory Management')
        ->assertSee('Out of stock');

    $this->post(route('inventory.movements.store', $product), [
        'type' => 'in',
        'quantity' => 12,
        'notes' => 'Restock from supplier',
    ])->assertRedirect()->assertSessionHas('success');

    expect($product->fresh()->stock)->toBe(12);
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'in',
        'quantity' => 12,
        'stock_before' => 0,
        'stock_after' => 12,
        'created_by' => $admin->id,
        'notes' => 'Restock from supplier',
    ]);
});

test('stock out cannot reduce inventory below zero', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $product = inventoryTestProduct(stock: 3);

    $this->post(route('inventory.movements.store', $product), [
        'type' => 'out',
        'quantity' => 4,
    ])->assertSessionHasErrors('quantity');

    expect($product->fresh()->stock)->toBe(3)
        ->and(StockMovement::query()->count())->toBe(0);
});

test('stock adjustment sets the exact quantity and records the delta', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $product = inventoryTestProduct(stock: 8);

    $this->post(route('inventory.movements.store', $product), [
        'type' => 'adjustment',
        'quantity' => 5,
        'notes' => 'Stock opname',
    ])->assertRedirect()->assertSessionHas('success');

    expect($product->fresh()->stock)->toBe(5);
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'adjustment',
        'quantity' => 3,
        'stock_before' => 8,
        'stock_after' => 5,
    ]);
});

test('non-admin users cannot access inventory management', function () {
    $product = inventoryTestProduct();

    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->get(route('inventory.index'))
        ->assertRedirect(route('home'));

    $this->post(route('inventory.movements.store', $product), [
        'type' => 'in',
        'quantity' => 5,
    ])->assertRedirect(route('home'));
});
