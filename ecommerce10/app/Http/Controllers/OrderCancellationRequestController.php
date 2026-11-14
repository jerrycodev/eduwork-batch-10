<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrderCancellationRequestController extends Controller
{
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

    public function update(Request $request, OrderCancellationRequest $cancellationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected,completed'],
            'admin_note' => ['nullable', 'string', 'max:3000'],
            'refund_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

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
                if (blank($validated['admin_note'] ?? null)) {
                    throw ValidationException::withMessages(['admin_note' => 'Catat referensi refund sebelum menyelesaikan pembatalan.']);
                }

                if (! $request->hasFile('refund_proof') && blank($requestRecord->refund_proof_path)) {
                    throw ValidationException::withMessages(['refund_proof' => 'Upload bukti transaksi refund sebelum menyelesaikan pembatalan.']);
                }

                $order->update(['payment_status' => 'refunded']);
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
            default => 'Pembatalan diselesaikan dan stok dikembalikan. Pastikan refund telah diproses.',
        });
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
