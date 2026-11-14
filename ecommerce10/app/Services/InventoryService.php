<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /** Apply a stock delta and record the resulting movement. Caller may already be in a transaction. */
    public function change(
        string $productId,
        int $delta,
        string $type,
        ?string $userId = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $notes = null,
    ): StockMovement {
        if (! in_array($type, ['in', 'out', 'adjustment'], true) || $delta === 0) {
            throw ValidationException::withMessages(['quantity' => 'Perubahan stok tidak valid.']);
        }

        return DB::transaction(function () use ($productId, $delta, $type, $userId, $referenceType, $referenceId, $notes): StockMovement {
            $product = Product::query()->lockForUpdate()->findOrFail($productId);
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
        });
    }

    public function adjustTo(string $productId, int $target, ?string $userId = null, ?string $notes = null): StockMovement
    {
        return DB::transaction(function () use ($productId, $target, $userId, $notes): StockMovement {
            $product = Product::query()->lockForUpdate()->findOrFail($productId);
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
        });
    }
}
