<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentStatusResolution extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'admin_id', 'reason', 'previous_status', 'new_status',
        'remote_status', 'remote_order_id', 'remote_transaction_id', 'remote_amount', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['remote_amount' => 'decimal:2', 'resolved_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
