<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'label', 'recipient_name', 'phone', 'address_line',
    'city', 'district', 'village', 'province', 'postal_code', 'country', 'is_default',
    'province_code', 'regency_code', 'district_code', 'village_code',
])]
class UserAddress extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedAddress(): string
    {
        return implode(', ', array_filter([
            $this->address_line,
            $this->village,
            $this->district,
            $this->city,
            $this->province,
            $this->postal_code,
            $this->country,
        ]));
    }
}
