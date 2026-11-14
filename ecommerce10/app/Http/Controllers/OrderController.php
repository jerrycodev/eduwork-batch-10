<?php

namespace App\Http\Controllers;

use App\Events\OrderStatusUpdated;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Services\BiteshipShippingService;
use App\Services\InventoryService;
use App\Services\MidtransPaymentService;
use App\Services\OrderNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    /** Display the authenticated customer's own orders. */
    public function index(): View|RedirectResponse
    {
        if (Auth::user()?->role === 'admin') {
            return redirect()->route('orders.index');
        }

        $orders = Order::query()
            ->where('user_id', Auth::id())
            ->withCount('orderItems')
            ->with('cancellationRequest')
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    /** Display all orders in the admin area. */
    public function adminIndex(): View
    {
        $orders = Order::query()
            ->withCount('orderItems')
            ->with('cancellationRequest')
            ->latest()
            ->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    /** Return live Biteship rates for the authenticated customer cart and saved address. */
    public function shippingRates(Request $request, BiteshipShippingService $shipping): JsonResponse
    {
        $validated = $request->validate(['address_id' => ['required', 'uuid']]);
        $address = $request->user()->addresses()->find($validated['address_id']);
        if (! $address) {
            return response()->json(['message' => 'Alamat pengiriman tidak ditemukan.'], 422);
        }

        $cartItems = Cart::query()->with('product')->where('user_id', $request->user()->id)->get();
        if ($cartItems->isEmpty() || $cartItems->contains(fn (Cart $item) => ! $item->product)) {
            return response()->json(['message' => 'Keranjang kosong atau berisi produk yang tidak tersedia.'], 422);
        }

        try {
            $rates = $shipping->getRates($cartItems, $address);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => $exception->getMessage()], 422);
        }

        if ($rates === []) {
            return response()->json(['message' => 'Tidak ada layanan kurir untuk alamat tujuan ini.'], 422);
        }

        $quoteId = Str::random(40);
        $request->session()->put('checkout_shipping_quote', [
            'id' => $quoteId,
            'address_id' => $address->id,
            'rates' => $rates,
            'created_at' => now()->timestamp,
        ]);

        return response()->json([
            'quote_id' => $quoteId,
            'address_id' => $address->id,
            'rates' => collect($rates)->map(fn (array $rate) => $rate + [
                'rate_key' => hash('sha256', $rate['courier_code'].'|'.$rate['service_code'].'|'.$rate['price']),
            ])->values(),
        ]);
    }

    /** Create an order immediately from the authenticated user's cart. */
    public function store(Request $request, MidtransPaymentService $payments, BiteshipShippingService $shipping): RedirectResponse
    {
        if (! Cart::query()->where('user_id', $request->user()->id)->exists()) {
            throw ValidationException::withMessages([
                'cart' => 'Keranjang belanja kosong. Tambahkan produk sebelum checkout.',
            ]);
        }

        $validated = $request->validate([
            'address_id' => ['required', 'uuid'],
            'shipping_quote_id' => ['required', 'string'],
            'shipping_rate_key' => ['required', 'string'],
        ], [
            'address_id.required' => 'Pilih alamat pengiriman terlebih dahulu.',
            'shipping_quote_id.required' => 'Silakan hitung ongkos kirim terlebih dahulu.',
            'shipping_rate_key.required' => 'Pilih layanan pengiriman terlebih dahulu.',
        ]);

        $user = Auth::user();
        $address = $user->addresses()->find($validated['address_id']);
        if (! $address) {
            return back()->withErrors(['address_id' => 'Alamat yang dipilih tidak ditemukan.'])->withInput();
        }

        $quote = $request->session()->get('checkout_shipping_quote');
        if (
            ! is_array($quote) ||
            ! hash_equals((string) ($quote['id'] ?? ''), $validated['shipping_quote_id']) ||
            (string) ($quote['address_id'] ?? '') !== (string) $address->id ||
            now()->timestamp - (int) ($quote['created_at'] ?? 0) > 600
        ) {
            return back()->withErrors(['shipping_rate_key' => 'Tarif ongkir sudah kedaluwarsa. Silakan hitung ulang.'])->withInput();
        }

        $selectedRate = collect($quote['rates'] ?? [])->first(function (array $rate) use ($validated): bool {
            $key = hash('sha256', $rate['courier_code'].'|'.$rate['service_code'].'|'.$rate['price']);

            return hash_equals($key, $validated['shipping_rate_key']);
        });
        if (! $selectedRate) {
            return back()->withErrors(['shipping_rate_key' => 'Pilihan layanan pengiriman tidak valid. Silakan hitung ulang.'])->withInput();
        }

        // Recheck the live rate at order creation; never trust a client-supplied shipping amount.
        $currentCart = Cart::query()->with('product')->where('user_id', $user->id)->get();
        try {
            $freshRates = $shipping->getRates($currentCart, $address);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['shipping_rate_key' => 'Tarif tidak dapat diverifikasi. Silakan coba lagi.'])->withInput();
        }
        $verifiedRate = collect($freshRates)->first(fn (array $rate) => $rate['courier_code'] === $selectedRate['courier_code'] &&
            $rate['service_code'] === $selectedRate['service_code'] &&
            (int) $rate['price'] === (int) $selectedRate['price']
        );
        if (! $verifiedRate) {
            $request->session()->forget('checkout_shipping_quote');

            return back()->withErrors(['shipping_rate_key' => 'Tarif pengiriman berubah. Silakan hitung ulang dan pilih layanan kembali.'])->withInput();
        }

        $shippingCost = (int) $verifiedRate['price'];

        $shippingAddress = implode(', ', array_filter([
            $address->address_line,
            $address->village,
            $address->district,
        ]));
        $recipientName = $address?->recipient_name ?? $user->name;
        $recipientPhone = $address?->phone;

        $order = DB::transaction(function () use ($user, $shippingAddress, $address, $recipientName, $recipientPhone, $verifiedRate, $shippingCost): Order {
            $cartItems = Cart::query()
                ->where('user_id', $user->id)
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
                $product = Product::query()
                    ->lockForUpdate()
                    ->find($cartItem->product_id);

                if (
                    ! $product ||
                    $cartItem->quantity < 1 ||
                    $cartItem->quantity > $product->stock
                ) {
                    throw ValidationException::withMessages([
                        'cart' => 'Stok produk '.($product?->name ?? 'yang dipilih').
                            ' tidak mencukupi. Perbarui keranjang lalu coba lagi.',
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

            $order = Order::query()->create([
                'user_id' => $user->id,
                'cart_id' => null,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'recipient_name' => $recipientName,
                'user_phone' => $recipientPhone,
                'address' => $shippingAddress,
                'city' => $address?->city,
                'state' => $address?->province,
                'zip' => $address?->postal_code,
                'country' => $address?->country,
                'subtotal' => $total,
                'shipping_cost' => $shippingCost,
                'shipping_courier' => $verifiedRate['courier_name'],
                'shipping_courier_code' => $verifiedRate['courier_code'],
                'shipping_service' => $verifiedRate['service_name'],
                'shipping_service_code' => $verifiedRate['service_code'],
                'shipping_etd' => $verifiedRate['duration'],
                'total' => $total + $shippingCost,
                'status' => 'pending',
            ]);

            $order->orderItems()->createMany($orderLines);

            $inventory = app(InventoryService::class);
            foreach ($cartItems as $cartItem) {
                $inventory->change(
                    productId: (string) $cartItem->product_id,
                    delta: -(int) $cartItem->quantity,
                    type: 'out',
                    userId: (string) $user->id,
                    referenceType: Order::class,
                    referenceId: (string) $order->id,
                    notes: 'Stock deducted for order '.$order->order_number,
                );
            }

            Cart::query()
                ->where('user_id', $user->id)
                ->delete();

            return $order;
        });

        $request->session()->forget('checkout_shipping_quote');

        try {
            $payments->createPayment($order->load('orderItems'));

            return redirect()->route('orders.customer.show', $order)
                ->with('success', 'Pesanan berhasil dibuat. Silakan selesaikan pembayaran melalui Midtrans.');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('orders.customer.show', $order)
                ->with('error', 'Pesanan berhasil dibuat, tetapi pembayaran belum dapat disiapkan. Silakan coba lagi.');
        }
    }

    /** Display one order belonging to the authenticated customer. */
    public function show(Order $order): View
    {
        abort_unless(Auth::user()?->role !== 'admin', 404);
        abort_unless($order->user_id === Auth::id(), 403);

        $order->load([
            'orderItems.product.reviews' => fn ($query) => $query
                ->where('user_id', Auth::id())
                ->with('media'),
            'returnRequest',
            'cancellationRequest',
        ]);

        return view('orders.show', compact('order'));
    }

    /** Update fulfillment status manually by an administrator. */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,processing,shipped,delivered,cancelled'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ]);

        $previousStatus = $order->status;
        $previousTrackingNumber = $order->tracking_number;
        $nextStatus = $validated['status'];
        $allowedTransitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [],
            'completed' => [],
            'cancelled' => [],
            'success' => [],
        ];

        if ($nextStatus !== $order->status && ! in_array($nextStatus, $allowedTransitions[$order->status] ?? [], true)) {
            return back()->withErrors(['status' => 'Perubahan status pesanan tidak diizinkan.']);
        }

        if (in_array($nextStatus, ['processing', 'shipped', 'delivered'], true) && $order->payment_status !== 'paid') {
            return back()->withErrors(['status' => 'Pesanan hanya dapat diproses setelah pembayaran berhasil diverifikasi.']);
        }

        if ($nextStatus === 'cancelled' && $order->payment_status === 'paid') {
            return back()->withErrors(['status' => 'Pesanan yang sudah dibayar harus dibatalkan melalui pengajuan pembatalan customer agar refund tercatat.']);
        }

        if (in_array($nextStatus, ['processing', 'shipped', 'delivered'], true) && $order->cancellationRequest()->whereIn('status', ['requested', 'approved'])->exists()) {
            return back()->withErrors(['status' => 'Pesanan sedang dalam proses pembatalan dan fulfillment ditahan sampai pengajuan ditolak atau diselesaikan.']);
        }

        if ($nextStatus === 'shipped' && blank($validated['tracking_number'] ?? null) && blank($order->tracking_number)) {
            return back()->withErrors(['tracking_number' => 'Nomor resi wajib diisi sebelum pesanan ditandai dikirim.']);
        }

        $updates = ['status' => $nextStatus];
        if ($nextStatus === 'shipped') {
            $updates['tracking_number'] = $validated['tracking_number'] ?? $order->tracking_number;
            $updates['shipped_at'] = $order->shipped_at ?? now();
        }
        if ($nextStatus === 'delivered') {
            $updates['delivered_at'] = $order->delivered_at ?? now();
            $updates['auto_complete_at'] = now()->addDays(3);
        }
        if ($nextStatus === 'cancelled') {
            $updates['auto_complete_at'] = null;
        }

        $order->update($updates);

        if ($nextStatus === 'cancelled' && ! $order->stock_restored_at) {
            DB::transaction(function () use ($order): void {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
                if ($lockedOrder->stock_restored_at) {
                    return;
                }
                $inventory = app(InventoryService::class);
                foreach ($lockedOrder->orderItems()->get() as $item) {
                    $inventory->change(
                        productId: (string) $item->product_id,
                        delta: (int) $item->quantity,
                        type: 'in',
                        referenceType: Order::class,
                        referenceId: (string) $lockedOrder->id,
                        notes: 'Stock restored for cancelled order '.$lockedOrder->order_number,
                    );
                }
                $lockedOrder->update(['stock_restored_at' => now()]);
            });
        }

        $order->refresh();

        if ($previousStatus !== $order->status || $previousTrackingNumber !== $order->tracking_number) {
            app(OrderNotificationService::class)->statusUpdated(
                $order,
                $previousTrackingNumber !== $order->tracking_number,
            );
        }

        if (config('realtime.enabled') && config('realtime.order_status') && ($previousStatus !== $order->status || $previousTrackingNumber !== $order->tracking_number)) {
            OrderStatusUpdated::dispatch((string) $order->id, [
                'id' => (string) $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'tracking_number' => $order->tracking_number,
                'payment_status' => $order->payment_status,
                'shipped_at' => $order->shipped_at?->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
                'updated_at' => $order->updated_at?->toIso8601String(),
            ]);
        }

        return redirect()->route('orders.show', $order)->with('success', 'Status pesanan berhasil diperbarui.');
    }

    /** Confirm receipt and complete an order by its owner. */
    public function confirmReceived(Request $request, Order $order): RedirectResponse
    {
        abort_unless($request->user()->role !== 'admin', 403);
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->status !== 'delivered') {
            return back()->withErrors(['order' => 'Pesanan hanya dapat dikonfirmasi setelah berstatus Delivered.']);
        }

        if ($order->returnRequest()->exists()) {
            return back()->withErrors(['order' => 'Pesanan dengan pengajuan pengembalian aktif tidak dapat dikonfirmasi selesai.']);
        }

        $order->update([
            'status' => 'completed',
            'confirmed_at' => now(),
            'completed_at' => now(),
            'auto_complete_at' => null,
        ]);

        return back()->with('success', 'Terima kasih! Pesanan berhasil dikonfirmasi selesai.');
    }

    /** Display an order in the admin dashboard. */
    public function adminShow(Order $order): View
    {
        $order->load('user', 'orderItems.product', 'returnRequest', 'cancellationRequest');

        return view('admin.orders.show', compact('order'));
    }
}
