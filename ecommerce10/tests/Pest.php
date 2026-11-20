<?php

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function checkoutShippingQuote($test, User $user, UserAddress $address): array
{
    config([
        'biteship.api_key' => 'biteship_test.fake-key',
        'biteship.warehouse.postal_code' => '16431',
        'biteship.couriers' => 'jne,sicepat,jnt',
    ]);
    Http::fake([
        'api.biteship.com/v1/rates/couriers' => Http::response([
            'success' => true,
            'pricing' => [[
                'courier_code' => 'jne',
                'courier_name' => 'JNE',
                'courier_service_code' => 'REG',
                'courier_service_name' => 'Regular',
                'price' => 15000,
                'duration' => '2 - 3 days',
            ]],
        ], 200),
    ]);

    return $test->actingAs($user, 'customer')
        ->postJson(route('checkout.shipping-rates'), ['address_id' => $address->id])
        ->assertOk()
        ->json();
}

function checkoutShippingPayload(array $quote): array
{
    return [
        'address_id' => $quote['address_id'] ?? null,
        'shipping_quote_id' => $quote['quote_id'],
        'shipping_rate_key' => $quote['rates'][0]['rate_key'],
    ];
}
