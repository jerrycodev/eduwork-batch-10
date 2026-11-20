<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * Display the authenticated user's shopping cart.
     */
    public function index(): View
    {
        $cartItems = Auth::check()
            ? Cart::query()
                ->with(['product.category', 'variant.optionValues.option', 'variant.images'])
                ->where('user_id', Auth::id())
                ->latest()
                ->get()
                ->filter(fn (Cart $item) => $item->product !== null)
                ->values()
            : new Collection;

        $totalQuantity = $cartItems->sum('quantity');
        $totalPrice = $cartItems->sum(
            fn (Cart $item) => $item->quantity * ($item->variant?->price ?? $item->product->price)
        );

        $addresses = Auth::check()
            ? UserAddress::query()->where('user_id', Auth::id())->orderByDesc('is_default')->orderBy('label')->get()
            : collect();

        return view('cart.index', compact('cartItems', 'totalQuantity', 'totalPrice', 'addresses'));
    }

    /**
     * Add a product to the authenticated user's cart.
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        if (! $product->is_active) {
            return back()->with('error', 'Produk ini sedang tidak tersedia.');
        }

        if (! Auth::check()) {
            return redirect()->route('login', ['redirect_to' => route('product-detail', $product->slug, absolute: false)])
                ->with('error', 'Silakan masuk terlebih dahulu untuk menambahkan produk ke keranjang.');
        }

        if (Auth::user()->role === 'admin') {
            return redirect()->route('product-detail', $product->slug)
                ->with('error', 'Admin tidak dapat menambahkan produk ke keranjang.');
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'product_variant_id' => ['nullable', 'uuid'],
        ], [
            'quantity.required' => 'Jumlah produk wajib diisi.',
            'quantity.integer' => 'Jumlah produk harus berupa angka.',
            'quantity.min' => 'Jumlah produk minimal 1.',
        ]);

        $stockError = DB::transaction(function () use ($product, $validated): ?int {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $variant = isset($validated['product_variant_id'])
                ? $lockedProduct->variants()->where('is_active', true)->find($validated['product_variant_id'])
                : $lockedProduct->variants()->where('is_default', true)->where('is_active', true)->first();

            if (! $variant) {
                return 0;
            }

            $cartItem = Cart::query()
                ->where('user_id', Auth::id())
                ->where('product_id', $lockedProduct->id)
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            $newQuantity = ($cartItem?->quantity ?? 0) + $validated['quantity'];

            if ($newQuantity > $variant->stock) {
                return (int) $variant->stock;
            }

            if ($cartItem) {
                $cartItem->quantity = $newQuantity;
                $cartItem->save();
            } else {
                Cart::query()->create([
                    'user_id' => Auth::id(),
                    'product_id' => $lockedProduct->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $validated['quantity'],
                ]);
            }

            return null;
        });

        if ($stockError !== null) {
            return back()->with('error', "Jumlah melebihi stok tersedia ({$stockError}).");
        }

        $cartItem = Cart::query()->where('user_id', Auth::id())->where('product_id', $product->id)->where('product_variant_id', $validated['product_variant_id'] ?? $product->variants()->where('is_default', true)->value('id'))->first();
        request()->attributes->set('customer_activity_subject', $cartItem);
        request()->attributes->set('customer_activity_metadata', ['product_name' => $product->name, 'quantity' => (int) ($cartItem?->quantity ?? $validated['quantity']), 'change_quantity' => (int) $validated['quantity']]);

        return redirect()->route('cart')->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }

    /**
     * Update the quantity of a cart item owned by the current user.
     */
    public function update(Request $request, Cart $cart): RedirectResponse
    {
        $this->authorizeCartItem($cart);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'product_variant_id' => ['nullable', 'uuid'],
        ], [
            'quantity.required' => 'Jumlah produk wajib diisi.',
            'quantity.integer' => 'Jumlah produk harus berupa angka.',
            'quantity.min' => 'Jumlah produk minimal 1.',
        ]);

        $cart->loadMissing('product');
        abort_if($cart->product === null, 404);

        $availableStock = (int) ($cart->variant?->stock ?? $cart->product->stock);
        if (! $cart->product->is_active || ($cart->variant && ! $cart->variant->is_active)) {
            return back()->with('error', 'Produk atau variant ini sedang tidak tersedia.');
        }
        if ($validated['quantity'] > $availableStock) {
            return back()->with('error', "Jumlah melebihi stok tersedia ({$availableStock}).");
        }

        $previousQuantity = (int) $cart->quantity;
        $cart->update(['quantity' => $validated['quantity']]);
        request()->attributes->set('customer_activity_subject', $cart->fresh());
        request()->attributes->set('customer_activity_metadata', ['previous_quantity' => $previousQuantity, 'new_quantity' => (int) $validated['quantity']]);

        return redirect()->route('cart')->with('success', 'Jumlah produk di keranjang berhasil diperbarui.');
    }

    /**
     * Remove a cart item owned by the current user.
     */
    public function destroy(Cart $cart): RedirectResponse
    {
        $this->authorizeCartItem($cart);
        $cart->loadMissing(['product', 'variant']);
        request()->attributes->set('customer_activity_subject', $cart);
        request()->attributes->set('customer_activity_metadata', ['product_name' => $cart->product?->name, 'product_sku' => $cart->variant?->sku ?? $cart->product?->sku, 'quantity' => (int) $cart->quantity]);

        $cart->delete();

        return redirect()->route('cart')->with('success', 'Produk berhasil dihapus dari keranjang.');
    }

    private function authorizeCartItem(Cart $cart): void
    {
        abort_unless(Auth::check(), 401);
        abort_unless((string) $cart->user_id === (string) Auth::id(), 403);
    }
}
