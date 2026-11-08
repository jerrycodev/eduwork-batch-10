<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    /** Display the authenticated customer's own orders. */
    public function index(): View
    {
        abort_unless(Auth::user()?->role !== 'admin', 404);

        $orders = Order::query()
            ->where('user_id', Auth::id())
            ->withCount('orderItems')
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    /** Display all orders in the admin area. */
    public function adminIndex(): View
    {
        $orders = Order::query()
            ->withCount('orderItems')
            ->latest()
            ->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    /** Create an order immediately from the authenticated user's cart. */
    public function store(Request $request): RedirectResponse
    {
        $order = DB::transaction(function (): Order {
            $cartItems = Cart::query()
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Keranjang belanja kosong. Tambahkan produk sebelum checkout.',
                ]);
            }

            $orderLines = [];
            $total = 0;

            foreach ($cartItems as $cartItem) {
                $product = Product::query()->lockForUpdate()->find($cartItem->product_id);

                if (! $product || $cartItem->quantity < 1 || $cartItem->quantity > $product->stock) {
                    throw ValidationException::withMessages([
                        'cart' => 'Stok produk '.($product?->name ?? 'yang dipilih').' tidak mencukupi. Perbarui keranjang lalu coba lagi.',
                    ]);
                }

                $lineTotal = (int) $product->price * (int) $cartItem->quantity;
                $total += $lineTotal;
                $orderLines[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $cartItem->quantity,
                    'price' => $product->price,
                    'total' => $lineTotal,
                ];
            }

            $user = Auth::user();

            $order = Order::query()->create([
                'user_id' => $user->id,
                'cart_id' => null,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_phone' => null,
                'address' => null,
                'city' => null,
                'state' => null,
                'zip' => null,
                'country' => null,
                'total' => $total,
                'status' => 'pending',
            ]);

            $order->orderItems()->createMany($orderLines);

            foreach ($cartItems as $cartItem) {
                Product::query()->whereKey($cartItem->product_id)
                    ->decrement('stock', $cartItem->quantity);
            }

            Cart::query()->where('user_id', $user->id)->delete();

            return $order;
        });

        return redirect()->route('orders.customer.show', $order)
            ->with('success', 'Pesanan berhasil dibuat. Pesanan Anda sudah tercatat.');
    }

    /** Display one order belonging to the authenticated customer. */
    public function show(Order $order): View
    {
        abort_unless(Auth::user()?->role !== 'admin', 404);
        abort_unless($order->user_id === Auth::id(), 403);

        $order->load('orderItems.product');

        return view('orders.show', compact('order'));
    }

    /** Display an order in the admin dashboard. */
    public function adminShow(Order $order): View
    {
        $order->load('user', 'orderItems.product');

        return view('admin.orders.show', compact('order'));
    }
}
