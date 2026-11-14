<?php

use Aliziodev\Wilayah\Models\District;
use Aliziodev\Wilayah\Models\Province;
use Aliziodev\Wilayah\Models\Regency;
use Aliziodev\Wilayah\Models\Village;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\MidtransPaymentService;

function addressPayload(array $overrides = []): array
{
    $province = Province::query()->firstOrCreate(['code' => '99'], ['name' => 'TEST PROVINCE']);
    $regency = Regency::query()->firstOrCreate(['code' => '99.01'], ['province_id' => $province->id, 'name' => 'TEST CITY', 'type' => 1]);
    $district = District::query()->firstOrCreate(['code' => '99.01.01'], ['regency_id' => $regency->id, 'name' => 'TEST DISTRICT']);
    $village = Village::query()->firstOrCreate(['code' => '99.01.01.2001'], ['district_id' => $district->id, 'name' => 'TEST VILLAGE', 'type' => 1, 'postal_code' => '12345']);

    return array_merge([
        'label' => 'Rumah',
        'recipient_name' => 'Budi Santoso',
        'phone' => '081234567890',
        'address_line' => 'Jl. Melati No. 10, RT 01/RW 02',
        'province_code' => $province->code,
        'regency_code' => $regency->code,
        'district_code' => $district->code,
        'village_code' => $village->code,
        'city' => $regency->name,
        'district' => $district->name,
        'village' => $village->name,
        'province' => $province->name,
        'postal_code' => $village->postal_code,
        'country' => 'Indonesia',
    ], $overrides);
}

function addressTestProduct(): Product
{
    $category = ProductCategory::query()->create([
        'name' => 'Address Test Category',
        'slug' => 'address-test-'.uniqid(),
    ]);

    return Product::query()->create([
        'name' => 'Address Test Product',
        'slug' => 'address-test-product-'.uniqid(),
        'description' => 'Product used for address checkout tests.',
        'image' => '/storage/address-test.png',
        'price' => 50000,
        'stock' => 5,
        'category_id' => $category->id,
    ]);
}

test('user can save multiple addresses and the first address becomes default', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('addresses.store'), addressPayload())
        ->assertRedirect(route('addresses.index'));
    $this->post(route('addresses.store'), addressPayload([
        'label' => 'Kantor',
        'city' => 'Jakarta Selatan',
    ]))->assertRedirect(route('addresses.index'));

    $addresses = $user->addresses()->orderBy('id')->get();
    expect($addresses)->toHaveCount(2)
        ->and($addresses[0]->is_default)->toBeTrue()
        ->and($addresses[1]->is_default)->toBeFalse();
});

test('user can change the default address and deleting the default promotes another address', function () {
    $user = User::factory()->create();
    $first = $user->addresses()->create(addressPayload(['is_default' => true]));
    $second = $user->addresses()->create(addressPayload(['label' => 'Kantor']));

    $this->actingAs($user)->patch(route('addresses.default', $second))
        ->assertRedirect(route('addresses.index'));
    expect($first->fresh()->is_default)->toBeFalse()
        ->and($second->fresh()->is_default)->toBeTrue();

    $this->delete(route('addresses.destroy', $second))->assertRedirect(route('addresses.index'));
    expect($first->fresh()->is_default)->toBeTrue();
});

test('user cannot view or modify another users address', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $address = $owner->addresses()->create(addressPayload(['is_default' => true]));

    $this->actingAs($otherUser)->get(route('addresses.edit', $address))->assertNotFound();
    $this->put(route('addresses.update', $address), addressPayload(['label' => 'Hijack']))->assertNotFound();
    $this->delete(route('addresses.destroy', $address))->assertNotFound();

    expect($address->fresh()->label)->toBe('Rumah');
});

test('checkout can select one of the users saved addresses and snapshots its details', function () {
    $user = User::factory()->create();
    $address = $user->addresses()->create(addressPayload([
        'label' => 'Rumah',
        'recipient_name' => 'Siti Aminah',
        'phone' => '081299998888',
        'is_default' => true,
    ]));
    $product = addressTestProduct();
    Cart::query()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);

    $gateway = Mockery::mock(MidtransPaymentService::class);
    $gateway->shouldReceive('createPayment')->once()->andReturnUsing(fn (Order $order) => $order);
    $this->app->instance(MidtransPaymentService::class, $gateway);

    $quote = checkoutShippingQuote($this, $user, $address);
    $response = $this->post(route('checkout.store'), checkoutShippingPayload($quote));
    $order = Order::query()->firstOrFail();

    $response->assertRedirect(route('orders.customer.show', $order));
    expect($order->recipient_name)->toBe('Siti Aminah')
        ->and($order->user_phone)->toBe('081299998888')
        ->and($order->city)->toBe('TEST CITY')
        ->and($order->state)->toBe('TEST PROVINCE')
        ->and($order->zip)->toBe('12345')
        ->and($order->address)->toBe('Jl. Melati No. 10, RT 01/RW 02, TEST VILLAGE, TEST DISTRICT')
        ->and($order->country)->toBe('Indonesia');
});

test('checkout rejects an address belonging to another user', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $address = $owner->addresses()->create(addressPayload(['is_default' => true]));
    $product = addressTestProduct();
    Cart::query()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($user)->post(route('checkout.store'), ['address_id' => $address->id, 'shipping_quote_id' => 'invalid', 'shipping_rate_key' => 'invalid'])
        ->assertSessionHasErrors('address_id');

    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
});

test('cart checkout displays saved addresses and address management link', function () {
    $user = User::factory()->create();
    $address = $user->addresses()->create(addressPayload(['is_default' => true]));
    $product = addressTestProduct();
    Cart::query()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($user)->get(route('cart'))
        ->assertOk()
        ->assertSee('Kelola alamat')
        ->assertSee('checkout-address')
        ->assertSee('Rumah')
        ->assertSee((string) $address->id);
});

test('region dropdown endpoints return dependent Indonesian administrative regions', function () {
    $payload = addressPayload();

    $this->actingAs(User::factory()->create())->get(route('wilayah.provinces'))
        ->assertOk()->assertJsonFragment(['value' => $payload['province_code'], 'label' => 'TEST PROVINCE']);
    $this->get(route('wilayah.regencies', ['province' => $payload['province_code']]))
        ->assertOk()->assertJsonFragment(['value' => $payload['regency_code'], 'label' => 'TEST CITY']);
    $this->get(route('wilayah.districts', ['regency' => $payload['regency_code']]))
        ->assertOk()->assertJsonFragment(['value' => $payload['district_code'], 'label' => 'TEST DISTRICT']);
    $this->get(route('wilayah.villages', ['district' => $payload['district_code']]))
        ->assertOk()->assertJsonFragment(['value' => $payload['village_code'], 'label' => 'TEST VILLAGE (Kelurahan)', 'postal_code' => '12345']);
});

test('address validation rejects a village that does not belong to the selected district', function () {
    $user = User::factory()->create();
    $payload = addressPayload(['district_code' => '99.01.02']);

    $this->actingAs($user)->post(route('addresses.store'), $payload)
        ->assertSessionHasErrors('village_code');

    $this->assertDatabaseCount('user_addresses', 0);
});
