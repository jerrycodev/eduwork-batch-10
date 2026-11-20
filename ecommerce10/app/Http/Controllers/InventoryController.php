<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * Menampilkan daftar data dengan filter dan relasi yang diperlukan untuk kebutuhan operasional.
     */
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category', 'variants'])
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->boolean('low_stock'), fn ($query) => $query->where('stock', '<=', 5))
            ->orderBy('stock')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $movements = StockMovement::query()
            ->with(['product:id,name', 'variant:id,name,sku', 'creator:id,name'])
            ->latest()
            ->limit(30)
            ->get();

        $summary = [
            'products' => Product::query()->count(),
            'out_of_stock' => Product::query()->where('stock', 0)->count(),
            'low_stock' => Product::query()->whereBetween('stock', [1, 5])->count(),
            'units' => (int) Product::query()->sum('stock'),
        ];

        return view('admin.inventory.index', compact('products', 'movements', 'summary'));
    }

    /**
     * Memvalidasi dan memproses permintaan pembuatan data baru sesuai aturan bisnis.
     */
    public function store(Request $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:in,out,adjustment'],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'product_variant_id' => ['nullable', 'uuid', 'exists:product_variants,id'],
        ]);

        $variant = $validated['product_variant_id'] ?? $product->variants()->where('is_default', true)->value('id');
        if (! $variant || ! $product->variants()->whereKey($variant)->exists()) {
            return back()->withErrors(['product_variant_id' => 'Variant produk tidak valid.'])->withInput();
        }

        $quantity = (int) $validated['quantity'];
        if ($validated['type'] !== 'adjustment' && $quantity < 1) {
            return back()->withErrors(['quantity' => 'Jumlah harus lebih dari nol.'])->withInput();
        }

        if ($validated['type'] === 'adjustment') {
            $inventory->adjustTo(
                productId: $product->id,
                target: $quantity,
                variantId: (string) $variant,
                userId: $request->user()->id,
                notes: $validated['notes'] ?? null,
            );
        } else {
            $inventory->change(
                productId: $product->id,
                variantId: (string) $variant,
                delta: $validated['type'] === 'in' ? $quantity : -$quantity,
                type: $validated['type'],
                userId: $request->user()->id,
                notes: $validated['notes'] ?? null,
            );
        }

        return back()->with('success', 'Stok '.$product->name.' berhasil diperbarui.');
    }
}
