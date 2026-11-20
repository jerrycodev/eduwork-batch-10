<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function makeVariantCategory(): ProductCategory
{
    return ProductCategory::query()->create([
        'name' => 'Variant Category',
        'slug' => 'variant-category',
    ]);
}

test('admin can create color variants with hex swatches and per-variant inventory', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $category = makeVariantCategory();

    $configuration = [
        'options' => [
            [
                'name' => 'Color',
                'code' => 'color',
                'display_type' => 'color',
                'values' => [
                    ['label' => 'Royal Brown', 'value' => 'royal-brown', 'color_hex' => '#5A4632'],
                    ['label' => 'Black', 'value' => 'black', 'color_hex' => '#171D21'],
                ],
            ],
        ],
        'variants' => [
            ['sku' => 'TSHIRT-BROWN', 'price' => 120000, 'stock' => 10, 'weight_gram' => 500, 'options' => ['color' => 'royal-brown']],
            ['sku' => 'TSHIRT-BLACK', 'price' => 125000, 'stock' => 5, 'weight_gram' => 520, 'options' => ['color' => 'black']],
        ],
    ];

    $this->actingAs($admin, 'admin')->get(route('products.create'))
        ->assertOk()
        ->assertSee('data-variant-builder')
        ->assertSee('product-variants.builder');

    $this->actingAs($admin, 'admin')->post(route('products.store'), [
        'name' => 'Premium T-Shirt',
        'slug' => 'premium-t-shirt',
        'sku' => 'TSHIRT-BASE',
        'description' => 'Premium cotton t-shirt.',
        'image' => 'https://example.test/tshirt.jpg',
        'price' => 120000,
        'stock' => 0,
        'weight_gram' => 500,
        'category_id' => $category->id,
        'options_json' => json_encode($configuration),
        'variant_images' => [0 => [UploadedFile::fake()->image('royal-brown.jpg')]],
    ])->assertRedirect(route('products.index'));

    $product = Product::query()->where('slug', 'premium-t-shirt')->firstOrFail();

    expect($product->stock)->toBe(15)
        ->and($product->variants()->count())->toBe(2)
        ->and($product->options()->where('code', 'color')->firstOrFail()->values()->where('value', 'royal-brown')->value('color_hex'))->toBe('#5A4632')
        ->and($product->variants()->where('sku', 'TSHIRT-BROWN')->firstOrFail()->images()->count())->toBe(1);

    expect(Storage::disk('public')->allFiles('products'))->not->toBeEmpty();

    $this->get(route('product-detail', $product->slug))
        ->assertOk()
        ->assertSee('#5A4632')
        ->assertSee('Royal Brown')
        ->assertSee('Black');
});

test('products without options still create a default variant with its own pricing inventory and dimensions', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = makeVariantCategory();

    $configuration = [
        'options' => [],
        'variants' => [[
            'options' => [],
            'sku' => 'MUG-DEFAULT',
            'price' => 75000,
            'compare_at_price' => 90000,
            'cost_price' => 30000,
            'stock' => 12,
            'weight_gram' => 350,
            'length_cm' => 12,
            'width_cm' => 8,
            'height_cm' => 10,
            'is_active' => true,
        ]],
    ];

    $this->actingAs($admin, 'admin')->post(route('products.store'), [
        'name' => 'Default Variant Mug',
        'slug' => 'default-variant-mug',
        'sku' => 'MUG-BASE',
        'description' => 'A mug without selectable options.',
        'image' => 'https://example.test/mug.jpg',
        'price' => 75000,
        'compare_at_price' => 90000,
        'cost_price' => 30000,
        'stock' => 12,
        'weight_gram' => 350,
        'length_cm' => 12,
        'width_cm' => 8,
        'height_cm' => 10,
        'category_id' => $category->id,
        'options_json' => json_encode($configuration),
    ])->assertRedirect(route('products.index'));

    $product = Product::query()->where('slug', 'default-variant-mug')->firstOrFail();
    $variant = $product->variants()->firstOrFail();

    expect($product->sku)->toBe('MUG-BASE')
        ->and($product->price)->toBe(75000)
        ->and($product->stock)->toBe(12)
        ->and($variant->name)->toBe('Default')
        ->and($variant->sku)->toBe('MUG-DEFAULT')
        ->and($variant->compare_at_price)->toBe(90000)
        ->and($variant->cost_price)->toBe(30000)
        ->and((float) $variant->length_cm)->toBe(12.0)
        ->and((float) $variant->width_cm)->toBe(8.0)
        ->and((float) $variant->height_cm)->toBe(10.0);
});

test('customer cart stores the selected variant and uses its price and stock', function () {
    $user = User::factory()->create();
    $category = makeVariantCategory();
    $product = Product::query()->create([
        'name' => 'Variant Cart Product',
        'slug' => 'variant-cart-product',
        'description' => 'Product with color variants.',
        'image' => 'https://example.test/product.jpg',
        'price' => 100000,
        'stock' => 0,
        'weight_gram' => 400,
        'category_id' => $category->id,
    ]);

    $option = $product->options()->create([
        'name' => 'Color',
        'code' => 'color',
        'display_type' => 'color',
    ]);
    $brown = $option->values()->create([
        'label' => 'Royal Brown',
        'value' => 'royal-brown',
        'color_hex' => '#5A4632',
    ]);

    $product->defaultVariant()->delete();
    $variant = $product->variants()->create([
        'sku' => 'CART-BROWN',
        'name' => 'Royal Brown',
        'price' => 135000,
        'stock' => 6,
        'weight_gram' => 450,
        'is_default' => true,
        'is_active' => true,
    ]);
    $variant->optionValues()->sync([$brown->id]);
    $product->update(['stock' => 6]);

    $this->actingAs($user, 'customer')->post(route('cart.store', $product), [
        'quantity' => 2,
        'product_variant_id' => $variant->id,
    ])->assertRedirect(route('cart'));

    $cart = Cart::query()->where('user_id', $user->id)->firstOrFail();

    expect($cart->product_variant_id)->toBe($variant->id)
        ->and($cart->quantity)->toBe(2);

    $this->get(route('cart'))
        ->assertOk()
        ->assertSee('Royal Brown')
        ->assertSee('270.000');
});

test('admin can edit each variant without mixing stock between variants', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $category = makeVariantCategory();
    $product = Product::query()->create([
        'name' => 'Editable Variant Product',
        'slug' => 'editable-variant-product',
        'sku' => 'EDIT-BASE',
        'description' => 'Editable product variants.',
        'image' => 'https://example.test/editable.jpg',
        'price' => 100000,
        'stock' => 0,
        'weight_gram' => 500,
        'category_id' => $category->id,
    ]);
    $option = $product->options()->create(['name' => 'Color', 'code' => 'color', 'display_type' => 'color']);
    $brown = $option->values()->create(['label' => 'Brown', 'value' => 'brown', 'color_hex' => '#5A4632']);
    $black = $option->values()->create(['label' => 'Black', 'value' => 'black', 'color_hex' => '#171D21']);
    $product->defaultVariant()->delete();

    $variants = collect([
        ['sku' => 'EDIT-BROWN', 'price' => 100000, 'stock' => 4, 'value' => $brown],
        ['sku' => 'EDIT-BLACK', 'price' => 110000, 'stock' => 6, 'value' => $black],
    ])->map(function (array $data, int $index) use ($product) {
        $variant = $product->variants()->create([
            'sku' => $data['sku'],
            'name' => $data['value']->label,
            'price' => $data['price'],
            'stock' => $data['stock'],
            'weight_gram' => 500,
            'is_default' => $index === 0,
            'is_active' => true,
        ]);
        $variant->optionValues()->sync([$data['value']->id]);

        return $variant;
    });
    $product->update(['stock' => 10]);

    $this->actingAs($admin, 'admin')->get(route('products.edit', $product))
        ->assertOk()
        ->assertSee('EDIT-BROWN')
        ->assertSee('EDIT-BLACK');

    $payload = [
        'name' => $product->name,
        'slug' => $product->slug,
        'sku' => $product->sku,
        'description' => $product->description,
        'image' => $product->image,
        'price' => $product->price,
        'stock' => 10,
        'weight_gram' => $product->weight_gram,
        'category_id' => $category->id,
        'variants' => [
            $variants[0]->id => ['sku' => 'EDIT-BROWN', 'price' => 105000, 'stock' => 7, 'weight_gram' => 550, 'length_cm' => 30, 'width_cm' => 25, 'height_cm' => 3, 'is_active' => 1],
            $variants[1]->id => ['sku' => 'EDIT-BLACK', 'price' => 115000, 'stock' => 2, 'weight_gram' => 600, 'length_cm' => 40, 'width_cm' => 35, 'height_cm' => 5, 'is_active' => 1],
        ],
        'variant_images' => [$variants[1]->id => [UploadedFile::fake()->image('black.jpg')]],
    ];

    $this->actingAs($admin, 'admin')->put(route('products.update', $product), $payload)
        ->assertRedirect(route('products.index'));

    $variants[0]->refresh();
    $variants[1]->refresh();
    $product->refresh();

    expect($variants[0]->price)->toBe(105000)
        ->and($variants[0]->stock)->toBe(7)
        ->and($variants[1]->price)->toBe(115000)
        ->and($variants[1]->stock)->toBe(2)
        ->and((float) $variants[0]->length_cm)->toBe(30.0)
        ->and((float) $variants[1]->length_cm)->toBe(40.0)
        ->and((float) $variants[1]->height_cm)->toBe(5.0)
        ->and($product->stock)->toBe(9)
        ->and($product->price)->toBe(105000)
        ->and($variants[1]->images()->count())->toBe(1)
        ->and($product->stockMovements()->where('product_variant_id', $variants[0]->id)->exists())->toBeTrue();

    expect(Storage::disk('public')->allFiles('products'))->not->toBeEmpty();
});

test('admin can add a new option value and variant while editing a product', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $category = makeVariantCategory();
    $product = Product::query()->create([
        'name' => 'Extendable Variant Product',
        'slug' => 'extendable-variant-product',
        'sku' => 'EXTEND-BASE',
        'description' => 'Product whose variants can be extended.',
        'image' => 'https://example.test/extendable.jpg',
        'price' => 100000,
        'stock' => 0,
        'weight_gram' => 500,
        'category_id' => $category->id,
    ]);
    $option = $product->options()->create(['name' => 'Color', 'code' => 'color', 'display_type' => 'color']);
    $brown = $option->values()->create(['label' => 'Brown', 'value' => 'brown', 'color_hex' => '#5A4632']);
    $black = $option->values()->create(['label' => 'Black', 'value' => 'black', 'color_hex' => '#171D21']);
    $product->defaultVariant()->delete();
    $existing = $product->variants()->create([
        'sku' => 'EXTEND-BROWN',
        'name' => 'Brown',
        'price' => 100000,
        'stock' => 4,
        'weight_gram' => 500,
        'is_default' => true,
        'is_active' => true,
    ]);
    $existing->optionValues()->sync([$brown->id]);
    $product->update(['stock' => 4]);

    $this->actingAs($admin, 'admin')->get(route('products.edit', $product))
        ->assertOk()
        ->assertSee('data-edit-variant-builder')
        ->assertSee('Tambah Variant Baru')
        ->assertSee('product-variants.edit');

    $configuration = [
        'options' => [[
            'id' => $option->id,
            'name' => 'Color',
            'code' => 'color',
            'display_type' => 'color',
            'values' => [
                ['id' => $brown->id, 'label' => 'Brown', 'value' => 'brown', 'color_hex' => '#5A4632'],
                ['id' => $black->id, 'label' => 'Black', 'value' => 'black', 'color_hex' => '#171D21'],
            ],
        ]],
        'variants' => [[
            'options' => ['color' => 'black'],
            'sku' => 'EXTEND-BLACK',
            'price' => 110000,
            'stock' => 3,
            'weight_gram' => 550,
            'length_cm' => 30,
            'width_cm' => 25,
            'height_cm' => 3,
        ]],
    ];

    $this->actingAs($admin, 'admin')->put(route('products.update', $product), [
        'name' => $product->name,
        'slug' => $product->slug,
        'sku' => $product->sku,
        'description' => $product->description,
        'image' => $product->image,
        'price' => $product->price,
        'stock' => 4,
        'weight_gram' => $product->weight_gram,
        'category_id' => $category->id,
        'variants' => [
            $existing->id => [
                'sku' => $existing->sku,
                'price' => $existing->price,
                'stock' => 4,
                'weight_gram' => 500,
                'is_active' => 1,
            ],
        ],
        'additional_variants_json' => json_encode($configuration),
        'new_variant_images' => [0 => [UploadedFile::fake()->image('black-variant.jpg')]],
    ])->assertRedirect(route('products.index'));

    $newVariant = $product->variants()->where('sku', 'EXTEND-BLACK')->firstOrFail();
    $product->refresh();

    expect($product->variants()->count())->toBe(2)
        ->and($product->stock)->toBe(7)
        ->and($newVariant->name)->toBe('Black')
        ->and($newVariant->stock)->toBe(3)
        ->and($newVariant->is_default)->toBeFalse()
        ->and($newVariant->optionValues()->whereKey($black->id)->exists())->toBeTrue()
        ->and($newVariant->images()->count())->toBe(1)
        ->and($product->stockMovements()->where('product_variant_id', $newVariant->id)->exists())->toBeTrue()
        ->and($existing->fresh()->exists)->toBeTrue();

    expect(Storage::disk('public')->allFiles('products'))->not->toBeEmpty();
});

test('adding a brand-new option preserves existing variants by assigning its first value', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = makeVariantCategory();
    $product = Product::query()->create([
        'name' => 'New Option Product',
        'slug' => 'new-option-product',
        'sku' => 'NEWOPT-BASE',
        'description' => 'Existing default variant receives a safe option default.',
        'image' => 'https://example.test/new-option.jpg',
        'price' => 90000,
        'stock' => 2,
        'weight_gram' => 400,
        'category_id' => $category->id,
    ]);
    $existing = $product->variants()->firstOrFail();

    $configuration = [
        'options' => [[
            'id' => null,
            'name' => 'Size',
            'code' => 'size',
            'display_type' => 'button',
            'values' => [
                ['id' => null, 'label' => 'Regular', 'value' => 'regular'],
                ['id' => null, 'label' => 'Large', 'value' => 'large'],
            ],
        ]],
        'variants' => [[
            'options' => ['size' => 'large'],
            'sku' => 'NEWOPT-LARGE',
            'price' => 100000,
            'stock' => 5,
            'weight_gram' => 450,
        ]],
    ];

    $this->actingAs($admin, 'admin')->put(route('products.update', $product), [
        'name' => $product->name,
        'slug' => $product->slug,
        'sku' => $product->sku,
        'description' => $product->description,
        'image' => $product->image,
        'price' => $product->price,
        'stock' => $product->stock,
        'weight_gram' => $product->weight_gram,
        'category_id' => $category->id,
        'variants' => [
            $existing->id => [
                'sku' => $existing->sku,
                'price' => $existing->price,
                'stock' => 2,
                'weight_gram' => 400,
                'is_active' => 1,
            ],
        ],
        'additional_variants_json' => json_encode($configuration),
    ])->assertRedirect(route('products.index'));

    $option = $product->options()->where('code', 'size')->firstOrFail();
    $regular = $option->values()->where('value', 'regular')->firstOrFail();
    $large = $option->values()->where('value', 'large')->firstOrFail();
    $existing->refresh();
    $newVariant = $product->variants()->where('sku', 'NEWOPT-LARGE')->firstOrFail();

    expect($existing->optionValues()->whereKey($regular->id)->exists())->toBeTrue()
        ->and($newVariant->optionValues()->whereKey($large->id)->exists())->toBeTrue()
        ->and($product->variants()->count())->toBe(2)
        ->and($product->fresh()->stock)->toBe(7);
});
