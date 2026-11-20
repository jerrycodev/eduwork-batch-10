<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WishlistController extends Controller
{
    /**
     * Menampilkan daftar data dengan filter dan relasi yang diperlukan untuk kebutuhan operasional.
     */
    public function index(): View
    {
        abort_if(Auth::user()->role === 'admin', 403);

        $wishlistItems = WishlistItem::query()
            ->with('product.category')
            ->where('user_id', Auth::id())
            ->latest()
            ->get()
            ->filter(fn (WishlistItem $item) => $item->product !== null)
            ->values();

        return view('wishlist.index', compact('wishlistItems'));
    }

    public function toggle(Product $product): RedirectResponse
    {
        abort_if(Auth::user()->role === 'admin', 403);

        $wishlistItem = WishlistItem::query()
            ->where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        if ($wishlistItem) {
            $wishlistItem->delete();

            return back()->with('success', 'Produk dihapus dari wishlist.');
        }

        WishlistItem::query()->create([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
        ]);

        return back()->with('success', 'Produk berhasil ditambahkan ke wishlist.');
    }

    /**
     * Memeriksa ketentuan penghapusan lalu menghapus data yang diizinkan.
     */
    public function destroy(WishlistItem $wishlistItem): RedirectResponse
    {
        abort_if(Auth::user()->role === 'admin', 403);
        abort_unless($wishlistItem->user_id === Auth::id(), 403);

        $wishlistItem->delete();

        return back()->with('success', 'Produk dihapus dari wishlist.');
    }
}
