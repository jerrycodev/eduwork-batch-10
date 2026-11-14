<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_return_request_id', 'requested_by', 'message', 'required_documents',
    'evidence_paths', 'evidence_original_names', 'customer_note', 'status',
    'submitted_at', 'reviewed_at',
])]
class OrderReturnRevision extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'required_documents' => 'array',
            'evidence_paths' => 'array',
            'evidence_original_names' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(OrderReturnRequest::class, 'order_return_request_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
