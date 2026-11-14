<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, string $id): bool {
    return (string) $user->id === $id;
});

Broadcast::channel('orders.{orderId}', function (User $user, string $orderId): bool {
    if ($user->role === 'admin') {
        return Order::query()->whereKey($orderId)->exists();
    }

    return Order::query()
        ->whereKey($orderId)
        ->where('user_id', $user->id)
        ->exists();
});
