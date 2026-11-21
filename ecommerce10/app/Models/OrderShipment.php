<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShipment extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'attempt', 'reference_id', 'biteship_order_id', 'biteship_tracking_id',
        'waybill_id', 'status', 'price', 'created_at_biteship', 'last_synced_at', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'price' => 'integer',
            'created_at_biteship' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
