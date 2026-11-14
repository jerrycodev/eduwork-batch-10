<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReconciliationAlert extends Model
{
    protected $fillable = ['order_id', 'finding_code', 'fingerprint', 'notified_at'];

    protected function casts(): array
    {
        return ['notified_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
