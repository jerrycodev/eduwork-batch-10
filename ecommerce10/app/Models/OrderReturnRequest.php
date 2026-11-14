<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['order_id', 'user_id', 'reason', 'description', 'evidence_paths', 'status', 'admin_note', 'reviewed_at', 'resolved_at', 'return_recipient', 'return_phone', 'return_address', 'return_instructions', 'return_courier', 'return_tracking_number', 'item_received_at', 'inspection_note', 'resolution_type', 'resolution_status', 'refund_amount', 'refund_method', 'refund_reference', 'refund_proof_path', 'refund_proof_original_name', 'replacement_courier', 'replacement_tracking_number', 'replacement_shipped_at', 'replacement_delivered_at'])]
class OrderReturnRequest extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'evidence_paths' => 'array',
            'reviewed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'item_received_at' => 'datetime',
            'replacement_shipped_at' => 'datetime',
            'replacement_delivered_at' => 'datetime',
            'refund_amount' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(OrderReturnRevision::class, 'order_return_request_id')->latest();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
