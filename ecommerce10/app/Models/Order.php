<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable(['order_number', 'user_id', 'cart_id', 'user_name', 'user_email', 'recipient_name', 'user_phone', 'address', 'city', 'state', 'zip', 'country', 'total', 'subtotal', 'shipping_cost', 'shipping_courier', 'shipping_courier_code', 'shipping_service', 'shipping_service_code', 'shipping_etd', 'tracking_number', 'shipped_at', 'delivered_at', 'confirmed_at', 'completed_at', 'auto_complete_at', 'cancellation_reason', 'stock_restored_at', 'status', 'payment_status', 'payment_order_id', 'snap_token', 'snap_redirect_url', 'transaction_id', 'payment_type', 'paid_at', 'payment_payload'])]
class Order extends Model
{
    use HasUuids;

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (filled($order->order_number)) {
                return;
            }

            do {
                $date = ($order->created_at ?? now())->format('Ymd');
                $orderNumber = 'ORD-'.$date.'-'.Str::upper(Str::random(6));
            } while (static::query()->where('order_number', $orderNumber)->exists());

            $order->order_number = $orderNumber;
        });
    }

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'auto_complete_at' => 'datetime',
            'payment_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function cancellationRequest(): HasOne
    {
        return $this->hasOne(OrderCancellationRequest::class);
    }

    public function returnRequest(): HasOne
    {
        return $this->hasOne(OrderReturnRequest::class);
    }

    public function paymentReconciliationChecks(): HasMany
    {
        return $this->hasMany(PaymentReconciliationCheck::class)->latest('checked_at');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
