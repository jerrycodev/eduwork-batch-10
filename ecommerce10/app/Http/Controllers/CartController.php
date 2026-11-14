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
                ->with('product.category')
                ->where('user_id', Auth::id())
                ->latest()
                ->get()
                ->filter(fn (Cart $item) => $item->product !== null)
                ->values()
            : new Collection;

        $totalQuantity = $cartItems->sum('quantity');
        $totalPrice = $cartItems->sum(
            fn (Cart $item) => $item->quantity * $item->product->price
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
        if (! Auth::check()) {
            return redirect()->route('login', ['redirect_to' => route('product-detail', $product->slug, absolute: false)])
                ->with('error', 'Silakan masuk terlebih dahulu untuk menambahkan produk ke keranjang.');
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ], [
            'quantity.required' => 'Jumlah produk wajib diisi.',
            'quantity.integer' => 'Jumlah produk harus berupa angka.',
            'quantity.min' => 'Jumlah produk minimal 1.',
        ]);

        if ($product->stock < 1) {
            return back()->with('error', 'Maaf, produk ini sedang habis.');
        }

        $stockError = DB::transaction(function () use ($product, $validated): ?int {
            // Serialize additions for the same product to prevent stock overselling.
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);

            $cartItem = Cart::query()
                ->where('user_id', Auth::id())
                ->where('product_id', $lockedProduct->id)
                ->lockForUpdate()
                ->first();

            $newQuantity = ($cartItem?->quantity ?? 0) + $validated['quantity'];

            if ($newQuantity > $lockedProduct->stock) {
                return $lockedProduct->stock;
            }

            if ($cartItem) {
                $cartItem->quantity = $newQuantity;
                $cartItem->save();
            } else {
                Cart::query()->create([
                    'user_id' => Auth::id(),
                    'product_id' => $lockedProduct->id,
                    'quantity' => $validated['quantity'],
                ]);
            }

            return null;
        });

        if ($stockError !== null) {
            return back()->with('error', "Jumlah melebihi stok tersedia ({$stockError}).");
        }

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
        ], [
            'quantity.required' => 'Jumlah produk wajib diisi.',
            'quantity.integer' => 'Jumlah produk harus berupa angka.',
            'quantity.min' => 'Jumlah produk minimal 1.',
        ]);

        $cart->loadMissing('product');
        abort_if($cart->product === null, 404);

        if ($validated['quantity'] > $cart->product->stock) {
            return back()->with('error', "Jumlah melebihi stok tersedia ({$cart->product->stock}).");
        }

        $cart->update(['quantity' => $validated['quantity']]);

        return redirect()->route('cart')->with('success', 'Jumlah produk di keranjang berhasil diperbarui.');
    }

    /**
     * Remove a cart item owned by the current user.
     */
    public function destroy(Cart $cart): RedirectResponse
    {
        $this->authorizeCartItem($cart);

        $cart->delete();

        return redirect()->route('cart')->with('success', 'Produk berhasil dihapus dari keranjang.');
    }

    private function authorizeCartItem(Cart $cart): void
    {
        abort_unless(Auth::check(), 401);
        abort_unless((string) $cart->user_id === (string) Auth::id(), 403);
    }
}
