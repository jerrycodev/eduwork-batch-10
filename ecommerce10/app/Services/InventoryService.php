<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /** Apply a stock delta to one sellable variant and keep the product stock cache in sync. */
    public function change(
        string $productId,
        int $delta,
        string $type,
        ?string $userId = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $notes = null,
        ?string $variantId = null,
    ): StockMovement {
        if (! in_array($type, ['in', 'out', 'adjustment'], true) || $delta === 0) {
            throw ValidationException::withMessages(['quantity' => 'Perubahan stok tidak valid.']);
        }

        return DB::transaction(function () use ($productId, $delta, $type, $userId, $referenceType, $referenceId, $notes, $variantId): StockMovement {
            $product = Product::query()->lockForUpdate()->findOrFail($productId);
            $variant = $variantId
                ? ProductVariant::query()->where('product_id', $product->id)->lockForUpdate()->findOrFail($variantId)
                : ProductVariant::query()->where('product_id', $product->id)->where('is_default', true)->lockForUpdate()->first();

            if (! $variant) {
                $before = (int) $product->stock;
                $after = $before + $delta;
                if ($after < 0) {
                    throw ValidationException::withMessages(['quantity' => 'Stok tidak mencukupi untuk pengeluaran tersebut.']);
                }
                $product->update(['stock' => $after]);

                return StockMovement::query()->create([
                    'product_id' => $product->id,
                    'type' => $type,
                    'quantity' => abs($delta),
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'notes' => $notes,
                    'created_by' => $userId,
                ]);
            }

            $before = (int) $variant->stock;
            $after = $before + $delta;
            if ($after < 0) {
                throw ValidationException::withMessages(['quantity' => 'Stok variant tidak mencukupi untuk pengeluaran tersebut.']);
            }

            $variant->update(['stock' => $after]);
            $product->update(['stock' => (int) $product->variants()->sum('stock')]);

            return StockMovement::query()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'type' => $type,
                'quantity' => abs($delta),
                'stock_before' => $before,
                'stock_after' => $after,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'created_by' => $userId,
            ]);
        });
    }

    public function adjustTo(string $productId, int $target, ?string $userId = null, ?string $notes = null, ?string $variantId = null): StockMovement
    {
        return DB::transaction(function () use ($productId, $target, $userId, $notes, $variantId): StockMovement {
            $product = Product::query()->lockForUpdate()->findOrFail($productId);
            $variant = $variantId
                ? ProductVariant::query()->where('product_id', $product->id)->lockForUpdate()->findOrFail($variantId)
                : ProductVariant::query()->where('product_id', $product->id)->where('is_default', true)->lockForUpdate()->first();

            if (! $variant) {
                $before = (int) $product->stock;
                $delta = $target - $before;
                if ($delta === 0) {
                    throw ValidationException::withMessages(['quantity' => 'Stok saat ini sudah sama dengan jumlah penyesuaian.']);
                }
                $product->update(['stock' => $target]);

                return StockMovement::query()->create([
                    'product_id' => $product->id,
                    'type' => 'adjustment',
                    'quantity' => abs($delta),
                    'stock_before' => $before,
                    'stock_after' => $target,
                    'notes' => $notes,
                    'created_by' => $userId,
                ]);
            }

            $before = (int) $variant->stock;
            $delta = $target - $before;
            if ($delta === 0) {
                throw ValidationException::withMessages(['quantity' => 'Stok variant saat ini sudah sama dengan jumlah penyesuaian.']);
            }

            $variant->update(['stock' => $target]);
            $product->update(['stock' => (int) $product->variants()->sum('stock')]);

            return StockMovement::query()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'type' => 'adjustment',
                'quantity' => abs($delta),
                'stock_before' => $before,
                'stock_after' => $target,
                'notes' => $notes,
                'created_by' => $userId,
            ]);
        });
    }
}
