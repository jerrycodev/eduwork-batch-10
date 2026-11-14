<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderReturnRequest;
use App\Models\OrderReturnRevision;
use App\Services\InventoryService;
use App\Services\MidtransPaymentService;
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

        $validated = $request->validate([
            'reason' => ['required', 'in:damaged,wrong_item,incomplete,not_as_described,other'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'evidence' => ['nullable', 'array', 'max:5'],
            'evidence.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,webm', 'max:102400'],
        ]);

        $evidenceFiles = $request->file('evidence', []);
        $videoFiles = array_filter($evidenceFiles, fn ($file): bool => in_array(strtolower($file->getClientOriginalExtension()), ['mp4', 'mov', 'webm'], true));
        if (count($videoFiles) > 1) {
            throw ValidationException::withMessages(['evidence' => 'Maksimal satu video unboxing untuk setiap pengajuan.']);
        }

        foreach ($evidenceFiles as $index => $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) && $file->getSize() > 5 * 1024 * 1024) {
                throw ValidationException::withMessages(["evidence.{$index}" => 'Ukuran foto maksimal 5 MB per file.']);
            }
        }

        $paths = [];
        try {
            $returnRequest = DB::transaction(function () use ($request, $order, $validated, &$paths): OrderReturnRequest {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

                if (! in_array($lockedOrder->status, ['delivered', 'completed'], true)) {
                    throw ValidationException::withMessages(['return' => 'Pengembalian hanya dapat diajukan setelah pesanan diterima.']);
                }
                if (! $lockedOrder->delivered_at) {
                    throw ValidationException::withMessages(['return' => 'Tanggal penerimaan pesanan tidak tersedia sehingga pengembalian tidak dapat diajukan.']);
                }
                if (now()->greaterThan($lockedOrder->delivered_at->copy()->addDays(7))) {
                    throw ValidationException::withMessages(['return' => 'Batas pengajuan pengembalian 7 hari setelah pesanan diterima telah berakhir.']);
                }
                if ($lockedOrder->returnRequest()->exists()) {
                    throw ValidationException::withMessages(['return' => 'Pengajuan pengembalian untuk pesanan ini sudah pernah dibuat.']);
                }

                $returnRequest = $lockedOrder->returnRequest()->create([
                    'user_id' => $request->user()->id,
                    'reason' => $validated['reason'],
                    'description' => $validated['description'],
                    'status' => 'requested',
                ]);

                foreach ($request->file('evidence', []) as $file) {
                    $paths[] = $file->store('order-returns/'.$returnRequest->id, 'local');
                }

                $returnRequest->update(['evidence_paths' => $paths]);
                $lockedOrder->update(['auto_complete_at' => null]);

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

    public function requestRevision(Request $request, OrderReturnRequest $returnRequest): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'required_documents' => ['required', 'array', 'min:1', 'max:4'],
            'required_documents.*' => ['required', 'in:video_unboxing,product_photos,packaging_photos,other'],
        ]);

        DB::transaction(function () use ($request, $returnRequest, $validated): void {
            $lockedRequest = OrderReturnRequest::query()->lockForUpdate()->findOrFail($returnRequest->id);
            if (! in_array($lockedRequest->status, ['requested', 'revision_requested'], true)) {
                throw ValidationException::withMessages(['revision' => 'Dokumen tambahan hanya dapat diminta saat pengajuan sedang diperiksa.']);
            }

            $lockedRequest->revisions()->create([
                'requested_by' => $request->user()->id,
                'message' => $validated['message'],
                'required_documents' => array_values(array_unique($validated['required_documents'])),
                'status' => 'requested',
            ]);

            $lockedRequest->update([
                'status' => 'revision_requested',
                'admin_note' => $validated['message'],
                'reviewed_at' => now(),
            ]);
        });

        return back()->with('success', 'Permintaan dokumen tambahan berhasil dikirim kepada pelanggan.');
    }

    public function submitRevision(Request $request, OrderReturnRequest $returnRequest, OrderReturnRevision $revision): RedirectResponse
    {
        abort_unless((string) $returnRequest->user_id === (string) $request->user()->id, 403);
        abort_unless((string) $revision->order_return_request_id === (string) $returnRequest->id, 404);

        if ($returnRequest->status !== 'revision_requested' || $revision->status !== 'requested') {
            throw ValidationException::withMessages(['revision' => 'Revisi ini tidak lagi menunggu dokumen pelanggan.']);
        }

        $validated = $request->validate([
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'evidence' => ['required', 'array', 'min:1', 'max:5'],
            'evidence.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,mp4,mov,webm,pdf', 'max:102400'],
        ]);

        $files = $request->file('evidence', []);
        $required = $revision->required_documents ?? [];
        $videoCount = 0;
        $imageCount = 0;
        foreach ($files as $index => $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $isVideo = in_array($extension, ['mp4', 'mov', 'webm'], true);
            $videoCount += $isVideo ? 1 : 0;
            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true);
            $imageCount += $isImage ? 1 : 0;
            if (! $isVideo && $file->getSize() > 5 * 1024 * 1024) {
                throw ValidationException::withMessages(["evidence.{$index}" => 'Ukuran foto maksimal 5 MB per file.']);
            }
        }

        if (in_array('video_unboxing', $required, true) && $videoCount < 1) {
            throw ValidationException::withMessages(['evidence' => 'Admin meminta video unboxing. Lampirkan minimal satu video.']);
        }
        if (in_array('product_photos', $required, true) && $imageCount < 1) {
            throw ValidationException::withMessages(['evidence' => 'Admin meminta foto kondisi produk. Lampirkan minimal satu foto.']);
        }
        if (in_array('packaging_photos', $required, true) && $imageCount < 1) {
            throw ValidationException::withMessages(['evidence' => 'Admin meminta foto kemasan. Lampirkan minimal satu foto.']);
        }
        if ($videoCount > 1) {
            throw ValidationException::withMessages(['evidence' => 'Maksimal satu video untuk setiap pengiriman revisi.']);
        }

        $paths = [];
        $names = [];
        try {
            foreach ($files as $file) {
                $paths[] = $file->store('order-returns/'.$returnRequest->id.'/revisions/'.$revision->id, 'local');
                $names[] = $file->getClientOriginalName();
            }

            DB::transaction(function () use ($revision, $paths, $names, $validated, $returnRequest): void {
                $lockedRequest = OrderReturnRequest::query()->lockForUpdate()->findOrFail($returnRequest->id);
                $lockedRevision = OrderReturnRevision::query()->lockForUpdate()->findOrFail($revision->id);
                if ($lockedRequest->status !== 'revision_requested' || $lockedRevision->status !== 'requested') {
                    throw ValidationException::withMessages(['revision' => 'Revisi ini tidak lagi menunggu dokumen pelanggan.']);
                }

                $lockedRevision->update([
                    'evidence_paths' => $paths,
                    'evidence_original_names' => $names,
                    'customer_note' => $validated['customer_note'] ?? null,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);
                $lockedRequest->update(['status' => 'requested']);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }

        return back()->with('success', 'Dokumen tambahan berhasil dikirim. Pengajuan kembali menunggu pemeriksaan admin.');
    }

    public function revisionEvidence(OrderReturnRequest $returnRequest, OrderReturnRevision $revision, int $index)
    {
        abort_unless(auth()->user()?->role === 'admin' || (string) $returnRequest->user_id === (string) auth()->id(), 403);
        abort_unless((string) $revision->order_return_request_id === (string) $returnRequest->id, 404);

        $path = $revision->evidence_paths[$index] ?? null;
        $name = $revision->evidence_original_names[$index] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $name);
    }

    public function update(Request $request, OrderReturnRequest $returnRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:3000'],
            'return_recipient' => ['required_if:status,approved', 'nullable', 'string', 'max:150'],
            'return_phone' => ['required_if:status,approved', 'nullable', 'string', 'max:40'],
            'return_address' => ['required_if:status,approved', 'nullable', 'string', 'max:3000'],
            'return_instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['status'] === 'rejected' && blank($validated['admin_note'] ?? null)) {
            throw ValidationException::withMessages(['admin_note' => 'Alasan penolakan wajib diisi.']);
        }

        DB::transaction(function () use ($returnRequest, $validated): void {
            $lockedRequest = OrderReturnRequest::query()->lockForUpdate()->findOrFail($returnRequest->id);
            if ($lockedRequest->status !== 'requested') {
                throw ValidationException::withMessages(['status' => 'Pengajuan ini sudah diperiksa.']);
            }

            $lockedRequest->update([
                'status' => $validated['status'],
                'admin_note' => $validated['admin_note'] ?? null,
                'return_recipient' => $validated['return_recipient'] ?? null,
                'return_phone' => $validated['return_phone'] ?? null,
                'return_address' => $validated['return_address'] ?? null,
                'return_instructions' => $validated['return_instructions'] ?? null,
                'reviewed_at' => now(),
                'resolved_at' => $validated['status'] === 'rejected' ? now() : null,
            ]);

            if ($validated['status'] === 'rejected') {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($lockedRequest->order_id);
                $lockedOrder->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'auto_complete_at' => null,
                ]);
            }
        });

        return back()->with('success', $validated['status'] === 'approved'
            ? 'Pengajuan disetujui. Pelanggan dapat mengirim barang ke alamat pengembalian.'
            : 'Pengajuan pengembalian ditolak.');
    }

    public function submitReturnShipment(Request $request, OrderReturnRequest $returnRequest): RedirectResponse
    {
        abort_unless((string) $returnRequest->user_id === (string) $request->user()->id, 403);
        $validated = $request->validate([
            'return_courier' => ['required', 'string', 'max:100'],
            'return_tracking_number' => ['required', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($returnRequest, $validated): void {
            $lockedRequest = OrderReturnRequest::query()->lockForUpdate()->findOrFail($returnRequest->id);
            if ($lockedRequest->status !== 'approved' || $lockedRequest->return_tracking_number) {
                throw ValidationException::withMessages(['return' => 'Pengiriman barang kembali belum dapat dicatat.']);
            }
            $lockedRequest->update($validated + ['status' => 'return_shipped']);
        });

        return back()->with('success', 'Informasi pengiriman barang kembali berhasil dikirim.');
    }

    public function receive(Request $request, OrderReturnRequest $returnRequest): RedirectResponse
    {
        $validated = $request->validate(['inspection_note' => ['nullable', 'string', 'max:3000']]);
        DB::transaction(function () use ($returnRequest, $validated): void {
            $lockedRequest = OrderReturnRequest::query()->lockForUpdate()->findOrFail($returnRequest->id);
            if ($lockedRequest->status !== 'return_shipped') {
                throw ValidationException::withMessages(['status' => 'Barang harus berstatus sedang dikirim sebelum dapat diterima.']);
            }
            $lockedRequest->update([
                'status' => 'item_received',
                'item_received_at' => now(),
                'inspection_note' => $validated['inspection_note'] ?? null,
            ]);
        });

        return back()->with('success', 'Barang retur dicatat telah diterima. Silakan pilih penyelesaian refund atau replacement.');
    }

    public function resolve(Request $request, OrderReturnRequest $returnRequest, MidtransPaymentService $payments): RedirectResponse
    {
        $validated = $request->validate([
            'resolution_type' => ['required', 'in:refund,replacement'],
            'refund_method' => ['required_if:resolution_type,refund', 'nullable', 'in:midtrans,manual'],
            'refund_amount' => ['required_if:resolution_type,refund', 'nullable', 'integer', 'min:1'],
            'refund_reference' => ['nullable', 'string', 'max:150'],
            'refund_proof' => ['required_if:refund_method,manual', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'replacement_courier' => ['required_if:resolution_type,replacement', 'nullable', 'string', 'max:100'],
            'replacement_tracking_number' => ['required_if:resolution_type,replacement', 'nullable', 'string', 'max:120'],
        ]);

        $order = $returnRequest->order;
        $amount = (int) ($validated['refund_amount'] ?? 0);
        if ($validated['resolution_type'] === 'refund' && $amount > (int) $order->total) {
            throw ValidationException::withMessages(['refund_amount' => 'Nominal refund tidak boleh melebihi total pesanan.']);
        }
        if ($validated['resolution_type'] === 'refund' && $validated['refund_method'] === 'manual' && blank($validated['refund_reference'] ?? null)) {
            throw ValidationException::withMessages(['refund_reference' => 'Referensi transaksi wajib untuk refund manual.']);
        }

        $proofData = [];
        if ($request->hasFile('refund_proof')) {
            $file = $request->file('refund_proof');
            $proofData = [
                'refund_proof_path' => $file->store('order-returns/'.$returnRequest->id.'/refund', 'local'),
                'refund_proof_original_name' => $file->getClientOriginalName(),
            ];
        }

        if ($validated['resolution_type'] === 'refund' && $validated['refund_method'] === 'manual' && empty($proofData) && blank($returnRequest->refund_proof_path)) {
            throw ValidationException::withMessages(['refund_proof' => 'Bukti transfer wajib untuk refund manual.']);
        }

        // Reserve the resolution under a row lock before calling an external payment API.
        DB::transaction(function () use ($returnRequest, $validated, $request, $order, $amount, $proofData): void {
            $lockedRequest = OrderReturnRequest::query()->lockForUpdate()->findOrFail($returnRequest->id);
            if ($lockedRequest->status !== 'item_received' || $lockedRequest->resolution_status !== 'pending') {
                throw ValidationException::withMessages(['resolution' => 'Penyelesaian hanya dapat diproses setelah barang diterima admin.']);
            }

            if ($validated['resolution_type'] === 'replacement') {
                $items = $order->orderItems()->get();
                if ($items->isEmpty()) {
                    throw ValidationException::withMessages(['replacement' => 'Item pesanan tidak ditemukan.']);
                }
                foreach ($items as $item) {
                    app(InventoryService::class)->change(
                        productId: (string) $item->product_id,
                        delta: -((int) $item->quantity),
                        type: 'out',
                        userId: (string) $request->user()->id,
                        referenceType: OrderReturnRequest::class,
                        referenceId: (string) $lockedRequest->id,
                        notes: 'Replacement shipment for order '.$order->order_number,
                    );
                }
                $lockedRequest->update([
                    'resolution_type' => 'replacement',
                    'resolution_status' => 'processing',
                    'replacement_courier' => $validated['replacement_courier'],
                    'replacement_tracking_number' => $validated['replacement_tracking_number'],
                    'replacement_shipped_at' => now(),
                ]);

                return;
            }

            $lockedRequest->update(array_merge([
                'resolution_type' => 'refund',
                'resolution_status' => 'initiating',
                'refund_amount' => $amount,
                'refund_method' => $validated['refund_method'],
            ], $proofData));
        });

        if ($validated['resolution_type'] === 'replacement') {
            return back()->with('success', 'Pengiriman replacement dicatat. Pelanggan dapat melacak barang pengganti menggunakan nomor resi.');
        }

        $refundReference = $validated['refund_reference'] ?? null;
        if ($validated['refund_method'] === 'midtrans') {
            try {
                $response = $payments->refund(
                    $order,
                    $amount,
                    'Customer return '.$order->order_number,
                    'RET-'.$returnRequest->id,
                );
                $refundReference = (string) ($response->refund_key ?? $response->refund_id ?? $refundReference ?? 'RET-'.$returnRequest->id);
            } catch (\Throwable $exception) {
                report($exception);
                DB::transaction(function () use ($returnRequest): void {
                    $lockedRequest = OrderReturnRequest::query()->lockForUpdate()->findOrFail($returnRequest->id);
                    if ($lockedRequest->resolution_status === 'initiating') {
                        $lockedRequest->update(['resolution_type' => null, 'resolution_status' => 'pending']);
                    }
                });
                throw ValidationException::withMessages(['refund_method' => 'Refund otomatis Midtrans gagal atau tidak didukung transaksi ini. Pilih refund manual dan unggah bukti transfer.']);
            }
        }

        DB::transaction(function () use ($returnRequest, $amount, $validated, $refundReference): void {
            $lockedRequest = OrderReturnRequest::query()->lockForUpdate()->findOrFail($returnRequest->id);
            if ($lockedRequest->resolution_status !== 'initiating') {
                throw ValidationException::withMessages(['resolution' => 'Proses refund sudah berubah. Periksa status penyelesaian sebelum mencoba kembali.']);
            }
            $lockedRequest->update([
                'resolution_status' => 'processing',
                'refund_amount' => $amount,
                'refund_method' => $validated['refund_method'],
                'refund_reference' => $refundReference,
            ]);
        });

        return back()->with('success', $validated['refund_method'] === 'midtrans'
            ? 'Permintaan refund telah dikirim ke Midtrans. Verifikasi status refund sebelum menandai selesai.'
            : 'Refund manual dicatat. Verifikasi transfer sebelum menandai selesai.');
    }

    public function completeResolution(Request $request, OrderReturnRequest $returnRequest): RedirectResponse
    {
        $validated = $request->validate(['resolution_reference' => ['nullable', 'string', 'max:150']]);
        DB::transaction(function () use ($returnRequest, $validated): void {
            $lockedRequest = OrderReturnRequest::query()->lockForUpdate()->findOrFail($returnRequest->id);
            if ($lockedRequest->status !== 'item_received' || $lockedRequest->resolution_status !== 'processing') {
                throw ValidationException::withMessages(['resolution' => 'Penyelesaian belum siap ditandai selesai.']);
            }

            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($lockedRequest->order_id);
            if ($lockedRequest->resolution_type === 'refund') {
                $reference = $validated['resolution_reference'] ?? $lockedRequest->refund_reference;
                if (blank($reference)) {
                    throw ValidationException::withMessages(['resolution_reference' => 'Referensi refund wajib diverifikasi.']);
                }
                $lockedRequest->update(['refund_reference' => $reference]);
                $lockedOrder->update([
                    'payment_status' => (int) $lockedRequest->refund_amount >= (int) $lockedOrder->total ? 'refunded' : 'partially_refunded',
                ]);
            } elseif ($lockedRequest->resolution_type === 'replacement') {
                $lockedRequest->update(['replacement_delivered_at' => now()]);
            } else {
                throw ValidationException::withMessages(['resolution' => 'Jenis penyelesaian tidak valid.']);
            }

            $lockedRequest->update([
                'status' => 'completed',
                'resolution_status' => 'completed',
                'resolved_at' => now(),
            ]);
            $lockedOrder->update([
                'status' => 'completed',
                'completed_at' => now(),
                'auto_complete_at' => null,
            ]);
        });

        return back()->with('success', 'Penyelesaian pengembalian berhasil ditandai selesai.');
    }

    public function refundProof(Request $request, OrderReturnRequest $returnRequest)
    {
        abort_unless($request->user()->role === 'admin' || (string) $returnRequest->user_id === (string) $request->user()->id, 403);
        abort_unless($returnRequest->refund_proof_path && Storage::disk('local')->exists($returnRequest->refund_proof_path), 404);

        return Storage::disk('local')->response($returnRequest->refund_proof_path, $returnRequest->refund_proof_original_name);
    }
}
