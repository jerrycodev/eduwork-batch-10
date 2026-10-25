<?php

use App\Models\User;

test('the dashboard renders the charts and product click metrics', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Orders Analytic')
        ->assertSee('Operating System')
        ->assertSee('dashboard-chart-data', false)
        ->assertSee('orders-chart', false)
        ->assertSee('operating-system-chart', false)
        ->assertSee('Products Clicks')
        ->assertSee('Last Month')
        ->assertSee('This Month')
        ->assertSee('Total Clicks');
});
