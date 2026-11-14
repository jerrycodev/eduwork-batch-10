<?php

use App\Models\AdminActivityLog;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

function activityLogTestProduct(): Product
{
    $category = ProductCategory::query()->create([
        'name' => 'Activity Log Category',
        'slug' => 'activity-log-category',
    ]);

    return Product::query()->create([
        'name' => 'Activity Log Product',
        'slug' => 'activity-log-product-'.uniqid(),
        'description' => 'Product for admin activity log tests.',
        'image' => 'https://example.test/activity-log.png',
        'price' => 100000,
        'stock' => 4,
        'weight_gram' => 500,
        'category_id' => $category->id,
    ]);
}

test('admin mutations are recorded with actor route subject and response metadata', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $product = activityLogTestProduct();

    $this->actingAs($admin)
        ->post(route('inventory.movements.store', $product), [
            'type' => 'in',
            'quantity' => 3,
            'notes' => 'Sensitive free-form notes are not copied to the audit log',
        ])
        ->assertRedirect();

    $log = AdminActivityLog::query()->sole();

    expect($log->user_id)->toBe($admin->id)
        ->and($log->actor_name)->toBe($admin->name)
        ->and($log->actor_email)->toBe($admin->email)
        ->and($log->action)->toBe('inventory.movements.store')
        ->and($log->route_name)->toBe('inventory.movements.store')
        ->and($log->method)->toBe('POST')
        ->and($log->subject_type)->toBe(Product::class)
        ->and($log->subject_id)->toBe($product->id)
        ->and($log->response_status)->toBe(302)
        ->and($log->metadata)->not->toHaveKey('notes')
        ->and($log->metadata)->not->toHaveKey('password');
});

test('admin activity log page supports filtering and is restricted to administrators', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    AdminActivityLog::query()->create([
        'user_id' => $admin->id,
        'actor_name' => $admin->name,
        'actor_email' => $admin->email,
        'action' => 'inventory.movements.store',
        'subject_type' => Product::class,
        'subject_id' => 'subject-123',
        'route_name' => 'inventory.movements.store',
        'method' => 'POST',
        'response_status' => 302,
        'ip_address' => '127.0.0.1',
        'metadata' => ['path' => '/dashboard/inventory/subject-123/movements'],
        'created_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('activity-logs.index', ['search' => 'subject-123', 'action' => 'inventory.movements.store']))
        ->assertOk()
        ->assertSee('Admin Activity Logs')
        ->assertSee($admin->email)
        ->assertSee('subject-123')
        ->assertSee('HTTP 302');

    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->get(route('activity-logs.index'))
        ->assertRedirect(route('home'));
});

test('read-only admin page views do not create activity log entries', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('dashboard'))->assertOk();

    expect(AdminActivityLog::query()->count())->toBe(0);
});
