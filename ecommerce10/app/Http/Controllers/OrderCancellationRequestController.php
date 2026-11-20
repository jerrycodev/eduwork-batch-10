<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Services\BiteshipShippingService;
use App\Services\InventoryService;
use App\Services\MidtransPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrderCancellationRequestController extends Controller
{
    /**
     * Memvalidasi dan memproses permintaan pembuatan data baru sesuai aturan bisnis.
     */
    public function store(Request $request, Order $order): RedirectResponse
    {
        abort_unless($request->user()->role !== 'admin', 403);
        abort_unless((string) $order->user_id === (string) $request->user()->id, 403);

        $validated = $request->validate([
            'reason' => ['required', 'in:changed_mind,wrong_order,wrong_address,duplicate,other'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($order, $request, $validated): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! in_array($lockedOrder->status, ['pending', 'processing'], true)) {
                throw ValidationException::withMessages(['cancellation' => 'Pesanan hanya dapat dibatalkan sebelum dikirim.']);
            }

            if ($lockedOrder->cancellationRequest()->exists()) {
                throw ValidationException::withMessages(['cancellation' => 'Pengajuan pembatalan untuk pesanan ini sudah pernah dibuat.']);
            }

            if ($lockedOrder->returnRequest()->exists()) {
                throw ValidationException::withMessages(['cancellation' => 'Pesanan memiliki pengajuan pengembalian dan tidak dapat dibatalkan.']);
            }

            if ($lockedOrder->payment_status === 'paid') {
                $lockedOrder->cancellationRequest()->create([
                    'user_id' => $request->user()->id,
                    'reason' => $validated['reason'],
                    'description' => $validated['description'] ?? null,
                    'status' => 'requested',
                ]);

                return;
            }

            if (in_array($lockedOrder->payment_status, ['refunded'], true)) {
                throw ValidationException::withMessages(['cancellation' => 'Status pembayaran pesanan tidak memungkinkan pembatalan ini.']);
            }

            $lockedOrder->update([
                'status' => 'cancelled',
                'cancellation_reason' => $validated['reason'].(filled($validated['description'] ?? null) ? ': '.$validated['description'] : ''),
                'auto_complete_at' => null,
            ]);
            $this->restoreStock($lockedOrder);
        });

        return back()->with('success', $order->fresh()->payment_status === 'paid'
            ? 'Pengajuan pembatalan berhasil dikirim dan menunggu persetujuan admin.'
            : 'Pesanan berhasil dibatalkan.');
    }

    /**
     * Memvalidasi dan menyimpan perubahan data dengan tetap menjaga aturan bisnis.
     */
    public function update(Request $request, OrderCancellationRequest $cancellationRequest, MidtransPaymentService $payments, BiteshipShippingService $biteship): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected,completed'],
            'admin_note' => ['nullable', 'string', 'max:3000'],
            'refund_method' => ['nullable', 'in:midtrans,manual'],
            'refund_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        // Initiate the remote refund outside the local DB transaction. The stable
        // refund key makes a retry safe if the network fails after Midtrans accepted it.
        if (($validated['status'] ?? null) === 'completed' && $cancellationRequest->order()->value('payment_status') === 'paid' && ($validated['refund_method'] ?? 'manual') === 'midtrans') {
            return $this->requestMidtransRefund($cancellationRequest, $validated, $payments);
        }

        // Cancel the remote shipment before marking the local order cancelled.
        // If Biteship rejects it (for example, the courier already picked the parcel),
        // keep the local order active so the two systems cannot silently diverge.
        if (($validated['status'] ?? null) === 'completed') {
            if ($cancellationRequest->status !== 'approved') {
                throw ValidationException::withMessages(['status' => 'Pengajuan pembatalan harus disetujui sebelum diselesaikan.']);
            }

            $orderForCancellation = $cancellationRequest->order()->firstOrFail();
            if ($orderForCancellation->payment_status === 'paid'
                && (($validated['refund_method'] ?? 'manual') !== 'manual'
                    || blank($validated['admin_note'] ?? null)
                    || (! $request->hasFile('refund_proof') && blank($cancellationRequest->refund_proof_path)))) {
                if (($validated['refund_method'] ?? 'manual') !== 'manual') {
                    throw ValidationException::withMessages(['refund_method' => 'Metode refund tidak valid.']);
                }
                if (blank($validated['admin_note'] ?? null)) {
                    throw ValidationException::withMessages(['admin_note' => 'Catat referensi refund manual sebelum menyelesaikan pembatalan.']);
                }
                if (! $request->hasFile('refund_proof') && blank($cancellationRequest->refund_proof_path)) {
                    throw ValidationException::withMessages(['refund_proof' => 'Upload bukti transfer refund manual sebelum menyelesaikan pembatalan.']);
                }
            }

            if ($orderForCancellation->biteship_order_id && $orderForCancellation->biteship_status !== 'cancelled') {
                try {
                    $biteship->cancelShipment(
                        $orderForCancellation,
                        trim(($cancellationRequest->description ? $cancellationRequest->description.' — ' : '').$cancellationRequest->reason),
                    );
                } catch (\Throwable $exception) {
                    report($exception);
                    throw ValidationException::withMessages([
                        'status' => 'Pembatalan shipment Biteship belum berhasil. Order lokal belum dibatalkan: '.$exception->getMessage(),
                    ]);
                }
            }
        }

        DB::transaction(function () use ($cancellationRequest, $validated, $request): void {
            $requestRecord = OrderCancellationRequest::query()->lockForUpdate()->findOrFail($cancellationRequest->id);
            $order = Order::query()->lockForUpdate()->findOrFail($requestRecord->order_id);

            $allowedTransitions = match ($requestRecord->status) {
                'requested' => ['approved', 'rejected'],
                'approved' => ['completed'],
                default => [],
            };

            if (! in_array($validated['status'], $allowedTransitions, true)) {
                throw ValidationException::withMessages(['status' => 'Perubahan status pembatalan tidak diizinkan.']);
            }

            if ($validated['status'] === 'rejected' && blank($validated['admin_note'] ?? null)) {
                throw ValidationException::withMessages(['admin_note' => 'Alasan penolakan wajib diisi.']);
            }

            if ($validated['status'] === 'completed' && $order->payment_status === 'paid') {
                if (($validated['refund_method'] ?? 'manual') !== 'manual') {
                    throw ValidationException::withMessages(['refund_method' => 'Metode refund tidak valid.']);
                }
                if (blank($validated['admin_note'] ?? null)) {
                    throw ValidationException::withMessages(['admin_note' => 'Catat referensi refund manual sebelum menyelesaikan pembatalan.']);
                }
                if (! $request->hasFile('refund_proof') && blank($requestRecord->refund_proof_path)) {
                    throw ValidationException::withMessages(['refund_proof' => 'Upload bukti transfer refund manual sebelum menyelesaikan pembatalan.']);
                }
                $order->update(['payment_status' => 'refunded']);
                $requestRecord->update(['refund_method' => 'manual', 'refund_reference' => $validated['admin_note']]);
            }

            if ($validated['status'] === 'completed' && $order->payment_status === 'refunded' && $requestRecord->refund_method === 'midtrans' && $requestRecord->refund_request_status !== 'submitted') {
                throw ValidationException::withMessages(['status' => 'Refund Midtrans belum tercatat berhasil. Periksa webhook dan status transaksi terlebih dahulu.']);
            }

            $proofData = [];
            if ($request->hasFile('refund_proof')) {
                $file = $request->file('refund_proof');
                $proofData = [
                    'refund_proof_path' => $file->store('order-cancellations/'.$requestRecord->id.'/refund', 'local'),
                    'refund_proof_original_name' => $file->getClientOriginalName(),
                ];
            }

            $requestRecord->update(array_merge([
                'status' => $validated['status'],
                'refund_method' => $requestRecord->refund_method ?? (($validated['status'] === 'completed' && $order->payment_status === 'refunded') ? 'manual' : null),
                'admin_note' => $validated['admin_note'] ?? null,
                'reviewed_at' => $requestRecord->reviewed_at ?? now(),
                'resolved_at' => in_array($validated['status'], ['rejected', 'completed'], true) ? now() : null,
            ], $proofData));

            if ($validated['status'] === 'completed') {
                $order->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $requestRecord->reason.($requestRecord->description ? ': '.$requestRecord->description : ''),
                    'auto_complete_at' => null,
                ]);
                $this->restoreStock($order);
            }
        });

        return back()->with('success', match ($validated['status']) {
            'approved' => 'Pembatalan disetujui. Pesanan ditahan; proses refund terlebih dahulu sebelum menandai selesai.',
            'rejected' => 'Pengajuan pembatalan ditolak. Pesanan dapat dilanjutkan kembali.',
            default => 'Pembatalan diselesaikan dan stok dikembalikan.',
        });
    }

    private function requestMidtransRefund(OrderCancellationRequest $cancellationRequest, array $validated, MidtransPaymentService $payments): RedirectResponse
    {
        $reservation = DB::transaction(function () use ($cancellationRequest): array {
            $record = OrderCancellationRequest::query()->lockForUpdate()->findOrFail($cancellationRequest->id);
            $order = Order::query()->lockForUpdate()->findOrFail($record->order_id);

            if ($record->status !== 'approved' || $order->payment_status !== 'paid') {
                throw ValidationException::withMessages(['status' => 'Pembatalan harus disetujui dan pembayaran masih berstatus paid untuk memulai refund.']);
            }
            if ($record->refund_request_status === 'submitted') {
                throw ValidationException::withMessages(['refund_method' => 'Permintaan refund sudah dikirim. Tunggu webhook Midtrans sebelum mencoba lagi.']);
            }
            if (! $order->transaction_id || data_get($order->payment_payload, 'transaction_status') !== 'settlement') {
                throw ValidationException::withMessages(['refund_method' => 'Refund otomatis memerlukan ID transaksi Midtrans dengan status settlement. Gunakan refund manual jika transaksi tidak memenuhi syarat.']);
            }

            $alreadyRefunded = (int) round((float) data_get($order->payment_payload, 'refund_amount', 0));
            $amount = max(0, (int) $order->total - $alreadyRefunded);
            if ($amount < 1) {
                throw ValidationException::withMessages(['refund_method' => 'Tidak ada sisa nominal yang dapat direfund.']);
            }

            $record->update([
                'refund_method' => 'midtrans',
                'refund_amount' => $amount,
                'refund_request_status' => 'initiating',
                'refund_requested_at' => $record->refund_requested_at ?? now(),
            ]);

            return ['order' => $order, 'amount' => $amount, 'key' => 'CANCEL-'.$record->id];
        });

        try {
            $response = $payments->refund(
                $reservation['order'],
                $reservation['amount'],
                'Order cancellation '.$reservation['order']->order_number,
                $reservation['key'],
            );
            $reference = (string) ($response->refund_key ?? $response->refund_id ?? $reservation['key']);
            OrderCancellationRequest::query()->whereKey($cancellationRequest->id)->update([
                'refund_request_status' => 'submitted',
                'refund_reference' => $reference,
                'refund_requested_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
            OrderCancellationRequest::query()->whereKey($cancellationRequest->id)
                ->where('refund_request_status', 'initiating')
                ->update(['refund_request_status' => 'failed']);

            throw ValidationException::withMessages(['refund_method' => 'Permintaan refund Midtrans gagal dikirim. Periksa konfigurasi dan status transaksi, lalu coba lagi. Kunci refund tetap sama untuk mencegah refund ganda.']);
        }

        return back()->with('success', 'Permintaan refund berhasil dikirim ke Midtrans. Status pembayaran menunggu konfirmasi webhook sebelum pembatalan dapat diselesaikan.');
    }

    public function proof(OrderCancellationRequest $cancellationRequest)
    {
        $user = request()->user();
        abort_unless($user && ($user->role === 'admin' || (string) $cancellationRequest->user_id === (string) $user->id), 403);

        $path = $cancellationRequest->refund_proof_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $cancellationRequest->refund_proof_original_name);
    }

    private function restoreStock(Order $order): void
    {
        if ($order->stock_restored_at) {
            return;
        }

        $inventory = app(InventoryService::class);
        foreach ($order->orderItems()->get() as $item) {
            $inventory->change(
                productId: (string) $item->product_id,
                variantId: $item->product_variant_id ? (string) $item->product_variant_id : null,
                delta: (int) $item->quantity,
                type: 'in',
                referenceType: Order::class,
                referenceId: (string) $order->id,
                notes: 'Stock restored for cancelled order '.$order->order_number,
            );
        }

        $order->update(['stock_restored_at' => now()]);
    }
}
