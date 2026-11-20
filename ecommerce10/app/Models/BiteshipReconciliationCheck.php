<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiteshipReconciliationCheck extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'checked_by', 'result', 'http_status', 'biteship_order_id',
        'remote_status', 'message', 'checked_at',
    ];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
