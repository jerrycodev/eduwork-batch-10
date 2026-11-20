<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description', 'image', 'price', 'stock', 'weight_gram', 'click', 'category_id', 'sku', 'barcode', 'compare_at_price', 'cost_price', 'is_active', 'is_featured', 'meta_title', 'meta_description', 'length_cm', 'width_cm', 'height_cm'])]
class Product extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_featured' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::created(function (Product $product): void {
            if ($product->variants()->exists()) {
                return;
            }

            $sku = $product->sku ?: 'PRD-'.strtoupper(Str::random(10));
            if (! $product->sku) {
                $product->forceFill(['sku' => $sku])->saveQuietly();
            }

            $product->variants()->create([
                'sku' => $sku,
                'barcode' => $product->barcode,
                'name' => 'Default',
                'price' => $product->price,
                'compare_at_price' => $product->compare_at_price,
                'cost_price' => $product->cost_price,
                'stock' => (int) $product->stock,
                'weight_gram' => $product->weight_gram ?? 1000,
                'length_cm' => $product->length_cm,
                'width_cm' => $product->width_cm,
                'height_cm' => $product->height_cm,
                'is_default' => true,
                'is_active' => true,
            ]);
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->whereNull('product_variant_id')->orderBy('sort_order');
    }

    public function allImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasMany
    {
        return $this->images()->where('is_primary', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('is_default', 'desc')->orderBy('id');
    }

    public function defaultVariant(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_default', true);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->with('values');
    }

    public function getMainImageAttribute(): ?string
    {
        return $this->relationLoaded('images')
            ? ($this->images->firstWhere('is_primary', true)?->path ?? $this->images->first()?->path ?? $this->image)
            : ($this->images()->where('is_primary', true)->value('path') ?? $this->image);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
