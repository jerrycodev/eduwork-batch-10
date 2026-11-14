<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderReturnRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrderReturnRequestController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        abort_unless($request->user()->role !== 'admin', 403);
        abort_unless($order->user_id === $request->user()->id, 403);

        if (! in_array($order->status, ['delivered', 'completed'], true)) {
            throw ValidationException::withMessages(['return' => 'Pengembalian hanya dapat diajukan setelah pesanan diterima.']);
        }

        if (! $order->delivered_at) {
            throw ValidationException::withMessages(['return' => 'Tanggal penerimaan pesanan tidak tersedia sehingga pengembalian tidak dapat diajukan.']);
        }

        $returnDeadline = $order->delivered_at->copy()->addDays(7);
        if (now()->greaterThan($returnDeadline)) {
            throw ValidationException::withMessages(['return' => 'Batas pengajuan pengembalian 7 hari setelah pesanan diterima telah berakhir.']);
        }

        if ($order->returnRequest()->exists()) {
            throw ValidationException::withMessages(['return' => 'Pengajuan pengembalian untuk pesanan ini sudah pernah dibuat.']);
        }

        $validated = $request->validate([
            'reason' => ['required', 'in:damaged,wrong_item,incomplete,not_as_described,other'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'evidence' => ['nullable', 'array', 'max:5'],
            'evidence.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $paths = [];
        try {
            $returnRequest = DB::transaction(function () use ($request, $order, $validated, &$paths): OrderReturnRequest {
                $returnRequest = $order->returnRequest()->create([
                    'user_id' => $request->user()->id,
                    'reason' => $validated['reason'],
                    'description' => $validated['description'],
                    'status' => 'requested',
                ]);

                foreach ($request->file('evidence', []) as $file) {
                    $paths[] = $file->store('order-returns/'.$returnRequest->id, 'local');
                }

                $returnRequest->update(['evidence_paths' => $paths]);
                $order->update(['auto_complete_at' => null]);

                return $returnRequest;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }

        return back()->with('success', 'Pengajuan pengembalian berhasil dikirim dan menunggu pemeriksaan admin.');
    }

    public function evidence(OrderReturnRequest $returnRequest, int $index)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $path = $returnRequest->evidence_paths[$index] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function update(Request $request, OrderReturnRequest $returnRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected,completed'],
            'admin_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $allowedTransitions = match ($returnRequest->status) {
            'requested' => ['approved', 'rejected'],
            'approved' => ['completed'],
            default => [],
        };

        if (! in_array($validated['status'], $allowedTransitions, true)) {
            throw ValidationException::withMessages(['status' => 'Perubahan status pengembalian tidak diizinkan.']);
        }

        if ($validated['status'] === 'rejected' && blank($validated['admin_note'] ?? null)) {
            throw ValidationException::withMessages(['admin_note' => 'Alasan penolakan wajib diisi.']);
        }

        if ($validated['status'] === 'completed' && $returnRequest->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Pengajuan harus disetujui sebelum diselesaikan.']);
        }

        $returnRequest->update([
            'status' => $validated['status'],
            'admin_note' => $validated['admin_note'] ?? null,
            'reviewed_at' => $returnRequest->reviewed_at ?? now(),
            'resolved_at' => in_array($validated['status'], ['rejected', 'completed'], true) ? now() : null,
        ]);

        if ($validated['status'] === 'rejected') {
            $returnRequest->order()->update([
                'status' => 'completed',
                'completed_at' => now(),
                'auto_complete_at' => null,
            ]);
        } elseif ($validated['status'] === 'completed') {
            $returnRequest->order()->update([
                'status' => 'completed',
                'completed_at' => now(),
                'auto_complete_at' => null,
            ]);
        }

        return back()->with('success', match ($validated['status']) {
            'approved' => 'Pengajuan pengembalian disetujui. Lanjutkan proses penerimaan barang dan refund secara manual.',
            'rejected' => 'Pengajuan pengembalian ditolak.',
            default => 'Pengajuan ditandai selesai. Pastikan refund telah diproses melalui kanal pembayaran.',
        });
    }
}
