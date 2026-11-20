<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'user_id', 'reason', 'description', 'status', 'admin_note', 'refund_proof_path', 'refund_proof_original_name', 'refund_method', 'refund_amount', 'refund_reference', 'refund_request_status', 'refund_requested_at', 'reviewed_at', 'resolved_at'])]
class OrderCancellationRequest extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'refund_requested_at' => 'datetime',
            'refund_amount' => 'integer',
            'resolved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
