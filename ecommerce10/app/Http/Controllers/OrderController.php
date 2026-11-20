<?php

namespace App\Http\Controllers;

use App\Events\OrderStatusUpdated;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\BiteshipShippingService;
use App\Services\InventoryService;
use App\Services\MidtransPaymentService;
use App\Services\OrderEmailService;
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

    /** Display all orders in the admin area with operational metrics and filters. */
    public function adminIndex(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,processing,shipped,delivered,completed,cancelled,success'],
            'payment_status' => ['nullable', 'in:unpaid,pending,paid,failed,expired,refunded,partially_refunded'],
            'follow_up' => ['nullable', 'in:payment,fulfillment,delivery,delivery_today,delivery_overdue,cancellation,return'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $baseQuery = Order::query();
        $metrics = [
            'total' => (clone $baseQuery)->count(),
            'awaiting_payment' => (clone $baseQuery)->whereIn('payment_status', ['unpaid', 'pending'])->where('status', 'pending')->count(),
            'ready_to_process' => (clone $baseQuery)->where('status', 'pending')->where('payment_status', 'paid')->count(),
            'in_delivery' => (clone $baseQuery)->where('status', 'shipped')->count(),
            'delivery_due_today' => (clone $baseQuery)->where('status', 'shipped')->whereDate('estimated_delivery_at', today())->count(),
            'delivery_overdue' => (clone $baseQuery)->where('status', 'shipped')->whereNotNull('estimated_delivery_at')->whereDate('estimated_delivery_at', '<', today())->count(),
            'cancellation' => (clone $baseQuery)->whereHas('cancellationRequest', fn ($query) => $query->whereIn('status', ['requested', 'approved']))->count(),
            'returns' => (clone $baseQuery)->whereHas('returnRequest', fn ($query) => $query
                ->where(function ($status) {
                    $status->where('status', 'requested')
                        ->orWhere(fn ($resolution) => $resolution->where('status', 'item_received')->whereIn('resolution_status', ['pending', 'initiating', 'processing']));
                }))->count(),
        ];

        $query = Order::query()
            ->withCount('orderItems')
            ->with(['cancellationRequest', 'returnRequest']);

        if (filled($validated['search'] ?? null)) {
            $search = trim($validated['search']);
            $query->where(fn ($orders) => $orders
                ->where('order_number', 'like', "%{$search}%")
                ->orWhere('user_name', 'like', "%{$search}%")
                ->orWhere('user_email', 'like', "%{$search}%")
                ->orWhere('tracking_number', 'like', "%{$search}%")
            );
        }

        if (filled($validated['status'] ?? null)) {
            $query->where('status', $validated['status']);
        }

        if (filled($validated['payment_status'] ?? null)) {
            $query->where('payment_status', $validated['payment_status']);
        }

        if (filled($validated['date_from'] ?? null)) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (filled($validated['date_to'] ?? null)) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        match ($validated['follow_up'] ?? null) {
            'payment' => $query->whereIn('payment_status', ['unpaid', 'pending'])->where('status', 'pending'),
            'fulfillment' => $query->where('status', 'pending')->where('payment_status', 'paid'),
            'delivery' => $query->where('status', 'shipped')->whereNotNull('estimated_delivery_at')->whereDate('estimated_delivery_at', '<=', today()),
            'delivery_today' => $query->where('status', 'shipped')->whereDate('estimated_delivery_at', today()),
            'delivery_overdue' => $query->where('status', 'shipped')->whereNotNull('estimated_delivery_at')->whereDate('estimated_delivery_at', '<', today()),
            'cancellation' => $query->whereHas('cancellationRequest', fn ($cancellation) => $cancellation->whereIn('status', ['requested', 'approved'])),
            'return' => $query->whereHas('returnRequest', fn ($return) => $return
                ->where(function ($status) {
                    $status->where('status', 'requested')
                        ->orWhere(fn ($resolution) => $resolution->where('status', 'item_received')->whereIn('resolution_status', ['pending', 'initiating', 'processing']));
                })),
            default => null,
        };

        $orders = $query->latest()->paginate(15)->withQueryString();

        return view('admin.orders.index', compact('orders', 'metrics'));
    }

    /** Return live Biteship rates for the authenticated customer cart and saved address. */
    public function shippingRates(Request $request, BiteshipShippingService $shipping): JsonResponse
    {
        $validated = $request->validate(['address_id' => ['required', 'uuid']]);
        $address = $request->user()->addresses()->find($validated['address_id']);
        if (! $address) {
            return response()->json(['message' => 'Alamat pengiriman tidak ditemukan.'], 422);
        }

        $cartItems = Cart::query()->with(['product', 'variant'])->where('user_id', $request->user()->id)->get();
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
                    ! $product->is_active ||
                    $cartItem->quantity < 1 ||
                    $cartItem->quantity > $product->stock
                ) {
                    throw ValidationException::withMessages([
                        'cart' => 'Stok produk '.($product?->name ?? 'yang dipilih').
                            ' tidak mencukupi. Perbarui keranjang lalu coba lagi.',
                    ]);
                }

                $variant = $cartItem->product_variant_id
                    ? ProductVariant::query()->where('product_id', $product->id)->lockForUpdate()->find($cartItem->product_variant_id)
                    : ProductVariant::query()->where('product_id', $product->id)->where('is_default', true)->lockForUpdate()->first();

                if (! $variant || ! $variant->is_active || $cartItem->quantity > $variant->stock) {
                    throw ValidationException::withMessages(['cart' => 'Variant produk '.($product->name).' tidak tersedia atau stok tidak mencukupi.']);
                }

                $lineTotal = (int) $variant->price * (int) $cartItem->quantity;
                $total += $lineTotal;
                $variantName = $variant->optionValues()->with('option')->get()
                    ->map(fn ($value) => $value->option->name.': '.$value->label)->implode(', ');

                $orderLines[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'product_sku' => $variant->sku,
                    'product_variant_name' => $variantName ?: ($variant->name ?? 'Default'),
                    'quantity' => $cartItem->quantity,
                    'price' => $variant->price,
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
                'payment_status' => 'pending',
                'payment_expires_at' => now()->addMinutes((int) config('midtrans.payment_expiry_minutes', 30)),
            ]);

            $order->orderItems()->createMany($orderLines);

            $inventory = app(InventoryService::class);
            foreach ($cartItems as $cartItem) {
                $inventory->change(
                    productId: (string) $cartItem->product_id,
                    variantId: (string) $cartItem->product_variant_id,
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
        $request->attributes->set('customer_activity_subject', $order);
        $request->attributes->set('customer_activity_metadata', [
            'order_number' => $order->order_number,
            'item_count' => $order->orderItems()->count(),
            'items' => $order->orderItems()->get(['product_name', 'product_sku', 'product_variant_name', 'quantity', 'price', 'total'])->toArray(),
            'subtotal' => (int) $order->subtotal,
            'shipping_cost' => (int) $order->shipping_cost,
            'total' => (int) $order->total,
            'currency' => 'IDR',
            'payment_status' => $order->payment_status,
        ]);

        $paymentReady = true;

        try {
            $payments->createPayment($order->load('orderItems'));
        } catch (\Throwable $exception) {
            report($exception);
            $paymentReady = false;
        }
        $request->attributes->set('customer_activity_secondary_action', $paymentReady ? 'payment.initiated' : 'payment.initialization_failed');

        // The order still exists; send its invoice even if Midtrans setup needs to be retried.
        app(OrderEmailService::class)->send($order->fresh()->load('orderItems'), 'order_created');

        return redirect()->route('orders.customer.show', $order)->with(
            $paymentReady ? 'success' : 'error',
            $paymentReady
                ? 'Pesanan berhasil dibuat. Invoice telah dikirim ke email Anda. Silakan selesaikan pembayaran melalui Midtrans.'
                : 'Pesanan berhasil dibuat, tetapi pembayaran belum dapat disiapkan. Invoice telah dikirim ke email Anda. Silakan coba lagi.',
        );
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

        $trackingData = null;
        $trackingError = null;

        if (
            filled($order->biteship_tracking_id)
            && ! $order->cancellationRequest
            && ! $order->returnRequest
            && $order->status !== 'cancelled'
        ) {
            try {
                $trackingData = app(BiteshipShippingService::class)->getTrackingPage($order->biteship_tracking_id);
            } catch (\Throwable $exception) {
                report($exception);
                $trackingError = 'Informasi tracking belum dapat dimuat. Silakan coba muat ulang halaman.';
            }
        }

        return view('orders.show', compact('order', 'trackingData', 'trackingError'));
    }

    /** Update fulfillment status manually by an administrator. */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,processing,shipped,delivered,cancelled'],
        ]);

        $result = DB::transaction(function () use ($order, $validated): array {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $previousStatus = $lockedOrder->status;
            $previousTrackingNumber = $lockedOrder->tracking_number;
            $nextStatus = $validated['status'];
            $allowedTransitions = [
                'pending' => ['processing', 'cancelled'],
                'processing' => [],
                'shipped' => [],
                'delivered' => [],
                'completed' => [],
                'cancelled' => [],
                'success' => [],
            ];

            if ($nextStatus !== $lockedOrder->status && ! in_array($nextStatus, $allowedTransitions[$lockedOrder->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Perubahan status pesanan tidak diizinkan.']);
            }

            if (in_array($nextStatus, ['processing', 'shipped', 'delivered'], true) && $lockedOrder->payment_status !== 'paid') {
                throw ValidationException::withMessages(['status' => 'Pesanan hanya dapat diproses setelah pembayaran berhasil diverifikasi.']);
            }

            if ($nextStatus === 'cancelled' && $lockedOrder->payment_status === 'paid') {
                throw ValidationException::withMessages(['status' => 'Pesanan yang sudah dibayar harus dibatalkan melalui pengajuan pembatalan customer agar refund tercatat.']);
            }

            if (in_array($nextStatus, ['processing', 'shipped', 'delivered'], true) && $lockedOrder->cancellationRequest()->whereIn('status', ['requested', 'approved'])->exists()) {
                throw ValidationException::withMessages(['status' => 'Pesanan sedang dalam proses pembatalan dan fulfillment ditahan sampai pengajuan ditolak atau diselesaikan.']);
            }

            if (in_array($nextStatus, ['shipped', 'delivered'], true) && $nextStatus !== $lockedOrder->status) {
                throw ValidationException::withMessages(['status' => 'Status pengiriman Shipped dan Delivered hanya diperbarui otomatis dari webhook Biteship.']);
            }

            $updates = ['status' => $nextStatus];
            if ($nextStatus === 'shipped') {
                $updates['shipped_at'] = $lockedOrder->shipped_at ?? now();

                if (! $lockedOrder->estimated_delivery_at) {
                    preg_match_all('/\\d+/', (string) $lockedOrder->shipping_etd, $etdMatches);
                    $estimatedDays = collect($etdMatches[0] ?? [])->map(fn ($value) => (int) $value)->max();
                    $updates['estimated_delivery_at'] = $estimatedDays
                        ? ($lockedOrder->shipped_at ?? now())->copy()->addDays($estimatedDays)
                        : null;
                }
            }
            if ($nextStatus === 'delivered') {
                $updates['delivered_at'] = $lockedOrder->delivered_at ?? now();
                $updates['auto_complete_at'] = now()->addDays(3);
            }
            if ($nextStatus === 'cancelled') {
                $updates['auto_complete_at'] = null;
            }

            $lockedOrder->update($updates);

            // Status and stock restoration must commit or roll back together.
            if ($nextStatus === 'cancelled' && ! $lockedOrder->stock_restored_at) {
                $inventory = app(InventoryService::class);
                foreach ($lockedOrder->orderItems()->get() as $item) {
                    $inventory->change(
                        productId: (string) $item->product_id,
                        variantId: $item->product_variant_id ? (string) $item->product_variant_id : null,
                        delta: (int) $item->quantity,
                        type: 'in',
                        referenceType: Order::class,
                        referenceId: (string) $lockedOrder->id,
                        notes: 'Stock restored for cancelled order '.$lockedOrder->order_number,
                    );
                }
                $lockedOrder->update(['stock_restored_at' => now()]);
            }

            return [
                'order' => $lockedOrder->fresh(),
                'previous_status' => $previousStatus,
                'previous_tracking_number' => $previousTrackingNumber,
            ];
        });

        $order = $result['order'];
        $previousStatus = $result['previous_status'];
        $previousTrackingNumber = $result['previous_tracking_number'];

        $order->refresh();

        $shipmentError = null;
        if ($order->status === 'processing' && $order->payment_status === 'paid' && ! $order->biteship_order_id) {
            try {
                $order = app(BiteshipShippingService::class)->createShipment($order);
            } catch (\Throwable $exception) {
                report($exception);
                $shipmentError = $exception->getMessage();
            }
        }

        if ($previousStatus !== $order->status || $previousTrackingNumber !== $order->tracking_number) {
            $trackingChanged = $previousTrackingNumber !== $order->tracking_number;

            app(OrderNotificationService::class)->statusUpdated($order, $trackingChanged);
            app(OrderEmailService::class)->statusUpdated($order, $trackingChanged);
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

        if ($shipmentError !== null) {
            return redirect()->route('orders.show', $order)->with('error', 'Status tersimpan sebagai Processing, tetapi pengiriman Biteship belum berhasil dibuat: '.$shipmentError.' Admin dapat mencoba ulang setelah memperbaiki penyebabnya.');
        }

        return redirect()->route('orders.show', $order)->with('success', $order->biteship_order_id ? 'Status pesanan tersimpan dan pengiriman Biteship telah terhubung.' : 'Status pesanan berhasil diperbarui.');
    }

    /** Retry shipment creation after a previous Biteship API failure. */
    public function retryBiteshipShipment(Order $order): RedirectResponse
    {
        abort_unless($order->payment_status === 'paid' && $order->status === 'processing', 422, 'Pesanan harus lunas dan berstatus Processing.');
        try {
            $order = app(BiteshipShippingService::class)->createShipment($order);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Pengiriman Biteship belum berhasil dibuat: '.$exception->getMessage());
        }

        return back()->with('success', 'Pengiriman Biteship berhasil dibuat. ID: '.$order->biteship_order_id);
    }

    /** Confirm receipt and complete an order by its owner. */
    public function confirmReceived(Request $request, Order $order): RedirectResponse
    {
        abort_unless($request->user()->role !== 'admin', 403);
        abort_unless($order->user_id === $request->user()->id, 403);

        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status !== 'delivered') {
                throw ValidationException::withMessages(['order' => 'Pesanan hanya dapat dikonfirmasi setelah berstatus Delivered.']);
            }

            if ($lockedOrder->returnRequest()->exists()) {
                throw ValidationException::withMessages(['order' => 'Pesanan dengan pengajuan pengembalian aktif tidak dapat dikonfirmasi selesai.']);
            }

            $lockedOrder->update([
                'status' => 'completed',
                'confirmed_at' => now(),
                'completed_at' => now(),
                'auto_complete_at' => null,
            ]);
        });

        return back()->with('success', 'Terima kasih! Pesanan berhasil dikonfirmasi selesai.');
    }

    /** Display an order in the admin dashboard. */
    public function adminShow(Order $order, BiteshipShippingService $biteship): View
    {
        $order->load('user', 'orderItems.product', 'returnRequest', 'cancellationRequest');
        $trackingData = null;
        $trackingError = null;

        if (filled($order->biteship_tracking_id)) {
            try {
                $trackingData = $biteship->getTrackingPage($order->biteship_tracking_id);
            } catch (\Throwable $exception) {
                report($exception);
                $trackingError = $exception->getMessage();
            }
        }

        return view('admin.orders.show', compact('order', 'trackingData', 'trackingError'));
    }
}
