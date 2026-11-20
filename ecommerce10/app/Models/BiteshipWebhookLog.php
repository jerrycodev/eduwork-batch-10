<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiteshipWebhookLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'event', 'reference_id', 'biteship_order_id', 'remote_status',
        'signature_valid', 'result', 'http_status', 'message', 'payload', 'received_at', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
