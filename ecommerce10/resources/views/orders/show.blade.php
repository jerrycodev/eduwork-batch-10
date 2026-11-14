<x-layout-component>
    @push('scripts')
        @if (config('realtime.enabled') && config('realtime.order_status'))
            @vite('resources/js/order-realtime.js')
        @endif
    @endpush

    <div id="order-detail-content" @if (config('realtime.enabled') && config('realtime.order_status')) data-order-realtime="{{ $order->id }}" @endif>
        <div class="mb-4"><a href="{{ route('orders.invoice', $order) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700"><i data-lucide="file-down" class="h-4 w-4"></i>{{ $order->payment_status === 'paid' ? 'Unduh bukti pembayaran' : 'Unduh invoice' }}</a></div>
    <div class="mb-7">
        <a href="{{ route('orders.customer.index') }}"
            class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition hover:text-indigo-600"><i
                data-lucide="arrow-left" class="h-4 w-4"></i>Kembali ke pesanan</a>
        <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="mb-1 text-sm font-semibold text-indigo-600">Order details</p>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Pesanan
                    #{{ $order->order_number }}</h1>
                <p class="mt-1.5 text-sm text-gray-500">Dibuat {{ $order->created_at->format('d M Y, H:i') }}</p>
            </div>
            @php
                $status = strtolower($order->status);
                $statusClass = match ($status) {
                    'success', 'completed' => 'bg-green-50 text-green-700 ring-green-600/20',
                    'processing', 'shipped' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    'delivered' => 'bg-cyan-50 text-cyan-700 ring-cyan-600/20',
                    'failed', 'cancelled' => 'bg-red-50 text-red-700 ring-red-600/20',
                    'refund', 'refunded' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                    default => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                };
            @endphp
            <span
                class="{{ $statusClass }} inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ring-inset"><span
                    class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ ucfirst($order->status) }}</span>
        </div>
    </div>

    @php
        $cancellation = $order->cancellationRequest;
        $returnRequest = $order->returnRequest;
        $cancellationActive = $cancellation && in_array($cancellation->status, ['requested', 'approved'], true);
        $cancellationRejected = $cancellation && $cancellation->status === 'rejected';
        $cancellationCompleted = $cancellation && $cancellation->status === 'completed';

        $orderSteps = [
            ['key' => 'placed', 'label' => 'Pesanan Dibuat', 'icon' => 'clipboard-list', 'date' => $order->created_at],
            ['key' => 'processing', 'label' => 'Dikemas', 'icon' => 'package-check', 'date' => null],
            ['key' => 'shipped', 'label' => 'Dalam Perjalanan', 'icon' => 'truck', 'date' => $order->shipped_at],
            ['key' => 'delivered', 'label' => 'Diterima', 'icon' => 'house', 'date' => $order->delivered_at],
            ['key' => 'completed', 'label' => 'Selesai', 'icon' => 'check-check', 'date' => $order->confirmed_at ?? $order->completed_at],
        ];
        $orderProgress = match ($order->status) {
            'pending' => 0,
            'processing' => 1,
            'shipped' => 2,
            'delivered' => 3,
            'completed', 'success' => 4,
            default => -1,
        };

        if ($cancellationActive || $cancellationCompleted) {
            $trackingTitle = 'Proses Pembatalan';
            $trackingDescription = 'Pantau pemeriksaan pengajuan, persetujuan, hingga penyelesaian pembatalan.';
            $trackingSteps = [
                ['label' => 'Pengajuan Dikirim', 'icon' => 'file-text', 'date' => $cancellation->created_at],
                ['label' => 'Ditinjau Admin', 'icon' => 'clipboard-check', 'date' => $cancellation->reviewed_at],
                ['label' => 'Refund Diproses', 'icon' => 'wallet', 'date' => $order->payment_status === 'refunded' ? $cancellation->resolved_at : null],
                ['label' => 'Pembatalan Selesai', 'icon' => 'check-check', 'date' => $cancellation->status === 'completed' ? $cancellation->resolved_at : null],
            ];
            $trackingProgress = match ($cancellation->status) {
                'requested' => 0,
                'approved' => 1,
                'return_shipped' => 2,
                'item_received' => 3,
                'completed' => 4,
                default => -1,
            };
            if ($cancellation->status === 'approved' && $order->payment_status === 'refunded') {
                $trackingProgress = 2;
            }
        } elseif ($returnRequest) {
            $trackingTitle = 'Proses Pengembalian';
            $trackingDescription = 'Pantau pemeriksaan pengajuan pengembalian produk.';
            $trackingSteps = [
                ['label' => 'Pengajuan Dikirim', 'icon' => 'file-text', 'date' => $returnRequest->created_at],
                ['label' => 'Disetujui Admin', 'icon' => 'clipboard-check', 'date' => $returnRequest->reviewed_at],
                ['label' => 'Barang Dikirim Kembali', 'icon' => 'truck', 'date' => $returnRequest->status === 'return_shipped' ? $returnRequest->updated_at : null],
                ['label' => 'Barang Diterima Admin', 'icon' => 'package-check', 'date' => $returnRequest->item_received_at],
                ['label' => 'Penyelesaian', 'icon' => 'check-check', 'date' => $returnRequest->status === 'completed' ? $returnRequest->resolved_at : null],
            ];
            $trackingProgress = match ($returnRequest->status) {
                'requested' => 0,
                'approved' => 1,
                'return_shipped' => 2,
                'item_received' => 3,
                'completed' => 4,
                default => -1,
            };
        } else {
            $trackingTitle = 'Lacak Pesanan';
            $trackingDescription = 'Pantau perjalanan pesananmu dari dibuat hingga selesai.';
            $trackingSteps = $orderSteps;
            $trackingProgress = $orderProgress;
        }
    @endphp

    <section class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-gray-900 sm:text-lg">{{ $trackingTitle }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $trackingDescription }}</p>
            </div>
            @if ($order->tracking_number && ! $cancellationActive && ! $cancellationCompleted && ! $returnRequest)
                <div class="rounded-lg bg-gray-50 px-3 py-2 text-right">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Nomor Resi</p>
                    <p class="mt-0.5 font-mono text-sm font-bold text-gray-800">{{ $order->tracking_number }}</p>
                </div>
            @endif
        </div>

        @if ($cancellationRejected && ! $returnRequest)
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <p class="font-semibold">Pengajuan pembatalan ditolak</p>
                <p class="mt-1">{{ $cancellation->admin_note ?: 'Pesanan dapat dilanjutkan mengikuti proses normal.' }}</p>
            </div>
        @endif

        @if ($cancellationActive || $cancellationCompleted || $returnRequest)
            @php
                $trackingRejected = ($cancellation && $cancellation->status === 'rejected') || ($returnRequest && $returnRequest->status === 'rejected');
                $trackingCount = count($trackingSteps);
            @endphp
            @if ($trackingRejected)
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <p class="font-semibold">Pengajuan {{ $returnRequest ? 'pengembalian' : 'pembatalan' }} ditolak</p>
                    <p class="mt-1">{{ $returnRequest?->admin_note ?: $cancellation?->admin_note ?: 'Pengajuan tidak disetujui. Silakan lihat kembali status pesanan.' }}</p>
                </div>
            @else
                <div class="mt-7 overflow-x-auto pb-2">
                    <div class="relative grid min-w-[480px] {{ $trackingCount === 5 ? 'grid-cols-5' : ($trackingCount === 4 ? 'grid-cols-4' : 'grid-cols-3') }}">
                        <div class="absolute left-[12%] right-[12%] top-5 h-1 rounded-full bg-gray-100"></div>
                        <div class="absolute left-[12%] top-5 h-1 rounded-full bg-amber-500 transition-all duration-500" style="width: {{ $trackingCount > 1 && $trackingProgress >= 0 ? ($trackingProgress / ($trackingCount - 1)) * 76 : 0 }}%"></div>
                        @foreach ($trackingSteps as $index => $step)
                            @php
                                $isComplete = $trackingProgress >= $index;
                                $isCurrent = $trackingProgress === $index;
                            @endphp
                            <div class="relative z-10 flex flex-col items-center text-center">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full border-2 transition-colors {{ $isComplete ? 'border-amber-500 bg-amber-500 text-white shadow-sm shadow-amber-200' : 'border-gray-200 bg-white text-gray-400' }} {{ $isCurrent ? 'ring-4 ring-amber-100' : '' }}">
                                    <i data-lucide="{{ $step['icon'] }}" class="h-[18px] w-[18px]"></i>
                                </span>
                                <span class="mt-3 text-xs font-semibold {{ $isComplete ? 'text-gray-900' : 'text-gray-400' }}">{{ $step['label'] }}</span>
                                @if ($step['date'] && $isComplete)
                                    <span class="mt-1 text-[10px] text-gray-400">{{ $step['date']->format('d M, H:i') }}</span>
                                @elseif ($isCurrent)
                                    <span class="mt-1 text-[10px] font-semibold text-amber-600">Sedang berlangsung</span>
                                @else
                                    <span class="mt-1 text-[10px] text-gray-300">Menunggu</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-3 text-xs text-gray-500">
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full {{ $trackingRejected ? 'bg-amber-500' : 'bg-amber-500' }}"></span>{{ $trackingRejected ? 'Pengajuan ditolak' : 'Tahap pengajuan' }}</span>
                @if ($cancellationActive && $cancellation->status === 'requested')
                    <span class="font-medium text-amber-700">Menunggu keputusan admin · Pesanan ditahan</span>
                @elseif ($cancellationActive && $cancellation->status === 'approved' && $order->payment_status !== 'refunded')
                    <span class="font-medium text-amber-700">Disetujui · Menunggu refund diproses</span>
                @elseif ($cancellationCompleted)
                    <span class="font-medium text-emerald-700">Pembatalan selesai{{ in_array($order->payment_status, ['refunded', 'partially_refunded'], true) ? ' · Refund tercatat' : '' }}</span>
                @elseif ($returnRequest && $returnRequest->status === 'requested')
                    <span class="font-medium text-amber-700">Menunggu pemeriksaan admin</span>
                @elseif ($returnRequest && $returnRequest->status === 'approved')
                    <span class="font-medium text-amber-700">Pengembalian disetujui · Kirim barang ke alamat retur</span>
                @elseif ($returnRequest && $returnRequest->status === 'return_shipped')
                    <span class="font-medium text-amber-700">Barang retur sedang dikirim</span>
                @elseif ($returnRequest && $returnRequest->status === 'item_received')
                    <span class="font-medium text-amber-700">Barang diterima admin · Menunggu penyelesaian</span>
                @elseif ($returnRequest && $returnRequest->status === 'completed')
                    <span class="font-medium text-emerald-700">Pengajuan pengembalian selesai{{ in_array($order->payment_status, ['refunded', 'partially_refunded'], true) ? ' · Refund tercatat' : '' }}</span>
                @elseif ($trackingRejected)
                    <span class="font-medium text-amber-700">Pesanan dapat mengikuti proses normal</span>
                @endif
            </div>
        @elseif ($order->status === 'cancelled')
            <div class="mt-5 rounded-xl border border-red-100 bg-red-50 p-4 text-red-700">
                <p class="text-sm font-semibold">Pesanan dibatalkan</p>
                <p class="mt-1 text-xs">Pesanan ini tidak dilanjutkan ke tahap pengiriman.</p>
                @if ($order->payment_status === 'refunded')
                    <p class="mt-2 text-xs font-semibold">Refund tercatat telah diproses.</p>
                @endif
            </div>
        @else
            <div class="mt-7 overflow-x-auto pb-2">
                <div class="relative grid min-w-[560px] grid-cols-5">
                    <div class="absolute left-[10%] right-[10%] top-5 h-1 rounded-full bg-gray-100"></div>
                    <div class="absolute left-[10%] top-5 h-1 rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $trackingProgress < 0 ? 0 : ($trackingProgress * 20) }}%"></div>
                    @foreach ($trackingSteps as $index => $step)
                        @php
                            $isComplete = $trackingProgress >= $index;
                            $isCurrent = $trackingProgress === $index;
                        @endphp
                        <div class="relative z-10 flex flex-col items-center text-center">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full border-2 transition-colors {{ $isComplete ? 'border-emerald-500 bg-emerald-500 text-white shadow-sm shadow-emerald-200' : 'border-gray-200 bg-white text-gray-400' }} {{ $isCurrent ? 'ring-4 ring-emerald-100' : '' }}">
                                <i data-lucide="{{ $step['icon'] }}" class="h-[18px] w-[18px]"></i>
                            </span>
                            <span class="mt-3 text-xs font-semibold {{ $isComplete ? 'text-gray-900' : 'text-gray-400' }}">{{ $step['label'] }}</span>
                            @if ($step['date'] && $isComplete)
                                <span class="mt-1 text-[10px] text-gray-400">{{ $step['date']->format('d M, H:i') }}</span>
                            @elseif ($isCurrent)
                                <span class="mt-1 text-[10px] font-semibold text-emerald-600">Sedang berlangsung</span>
                            @else
                                <span class="mt-1 text-[10px] text-gray-300">Menunggu</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 text-xs text-gray-500">
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Tahap selesai</span>
                @if ($order->status === 'delivered')
                    <span class="font-medium text-emerald-700">Menunggu konfirmasi penerimaan</span>
                @elseif (in_array($order->status, ['completed', 'success'], true))
                    <span class="font-medium text-emerald-700">Pesanan telah selesai</span>
                @elseif ($order->status === 'pending' && $order->payment_status === 'paid')
                    <span class="font-medium text-blue-700">Pembayaran terverifikasi · Menunggu admin memproses pesanan</span>
                @elseif ($order->status === 'pending')
                    <span class="font-medium text-amber-600">Menunggu pembayaran</span>
                @endif
            </div>
        @endif
    </section>

    @if (in_array($order->status, ['pending', 'processing'], true) && ! $order->cancellationRequest && ! $order->returnRequest)
        <section class="mb-5 rounded-xl border border-red-100 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-gray-900">Batalkan Pesanan</h2>
                    @if ($order->payment_status === 'paid')
                        <p class="mt-1 text-sm text-gray-500">Karena pembayaran sudah berhasil, pembatalan perlu diperiksa dan disetujui admin. Refund diproses setelah persetujuan.</p>
                    @else
                        <p class="mt-1 text-sm text-gray-500">Pesanan belum dibayar. Pembatalan akan langsung diproses dan stok dikembalikan.</p>
                    @endif
                </div>
                <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">{{ $order->payment_status === 'paid' ? 'Perlu persetujuan admin' : 'Bisa dibatalkan langsung' }}</span>
            </div>
            <form action="{{ route('orders.cancel', $order) }}" method="POST" class="mt-4 space-y-3">@csrf
                <div><label for="cancellation-reason" class="mb-1 block text-sm font-medium text-gray-700">Alasan pembatalan</label><select id="cancellation-reason" name="reason" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"><option value="">Pilih alasan</option><option value="changed_mind">Berubah pikiran</option><option value="wrong_order">Salah memilih produk</option><option value="wrong_address">Alamat pengiriman salah</option><option value="duplicate">Pesanan duplikat</option><option value="other">Alasan lainnya</option></select>@error('reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="cancellation-description" class="mb-1 block text-sm font-medium text-gray-700">Keterangan (opsional)</label><textarea id="cancellation-description" name="description" rows="2" maxlength="2000" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" placeholder="Tambahkan keterangan jika diperlukan"></textarea></div>
                <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50" onclick="return confirm('{{ $order->payment_status === 'paid' ? 'Kirim pengajuan pembatalan kepada admin?' : 'Yakin ingin membatalkan pesanan ini?' }}')">{{ $order->payment_status === 'paid' ? 'Ajukan Pembatalan' : 'Batalkan Pesanan' }}</button>
            </form>
        </section>
    @endif

    @if ($order->cancellationRequest)
        <section class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-5">
            <h2 class="font-semibold text-amber-900">Pengajuan Pembatalan</h2>
            <p class="mt-1 text-sm text-amber-800">Status: <strong>{{ str($order->cancellationRequest->status)->replace('_', ' ')->title() }}</strong></p>
            <p class="mt-2 text-sm text-amber-900">Alasan: {{ str($order->cancellationRequest->reason)->replace('_', ' ')->title() }}</p>
            @if ($order->cancellationRequest->description)<p class="mt-1 text-sm text-amber-900">{{ $order->cancellationRequest->description }}</p>@endif
            @if ($order->cancellationRequest->admin_note)<p class="mt-2 text-sm text-amber-900">Catatan admin: {{ $order->cancellationRequest->admin_note }}</p>@endif
            @if ($order->cancellationRequest->refund_proof_path)
                <div class="mt-3 rounded-lg border border-purple-200 bg-white p-3">
                    <p class="text-sm font-semibold text-purple-900">Bukti transaksi refund</p>
                    <a href="{{ route('order-cancellations.proof', $order->cancellationRequest) }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex items-center gap-2 text-sm font-medium text-purple-700 underline"><i data-lucide="file-check" class="h-4 w-4"></i>Lihat bukti refund · {{ $order->cancellationRequest->refund_proof_original_name }}</a>
                </div>
            @endif
            <p class="mt-2 text-xs text-amber-800">@if ($order->cancellationRequest->status === 'requested')Pengajuan sedang ditinjau admin. Pesanan ditahan dan tidak akan dikirim.@elseif ($order->cancellationRequest->status === 'approved')Pembatalan disetujui. Refund sedang menunggu atau diproses admin.@elseif ($order->cancellationRequest->status === 'rejected')Pengajuan ditolak. Pesanan dapat dilanjutkan.@elsePembatalan selesai. Jika pembayaran dilakukan, periksa mutasi rekening atau kanal pembayaran untuk memastikan refund diterima.@endif</p>
        </section>
    @endif

    @if ($errors->has('order') || $errors->has('return') || $errors->has('review'))
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ $errors->first('order') ?: ($errors->first('return') ?: $errors->first('review')) }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            {{ session('error') }}</div>
    @endif
    @if (session('success') || session('review_success'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
            role="status"><i data-lucide="check-circle-2"
                class="mt-0.5 h-4 w-4 shrink-0"></i><span>{{ session('success') ?: session('review_success') }}</span></div>
    @endif

    <section class="mb-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-gray-900">Pembayaran</h2>
                <p class="mt-1 text-sm text-gray-500">Status pembayaran: <span
                        class="font-semibold text-gray-900">{{ ucfirst($order->payment_status ?? 'unpaid') }}</span></p>
                @if ($order->paid_at)
                    <p class="mt-1 text-xs text-gray-500">Dibayar {{ $order->paid_at->format('d M Y, H:i') }}</p>
                @endif
            </div>
            @if ($order->payment_status === 'refunded')
                <span class="inline-flex rounded-full bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-700">Refund tercatat</span>
            @elseif ($order->payment_status !== 'paid' && $order->status !== 'cancelled')
                @if ($order->snap_token && in_array($order->payment_status, ['pending', 'challenge'], true))
                    <button type="button" id="pay-order-button"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Bayar
                        Sekarang<i data-lucide="credit-card" class="h-4 w-4"></i></button>
                @else
                    <form action="{{ route('orders.payment.create', $order) }}" method="POST">@csrf<button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Siapkan
                            Pembayaran<i data-lucide="arrow-right" class="h-4 w-4"></i></button></form>
                @endif
            @elseif ($order->payment_status === 'paid')
                <span
                    class="inline-flex rounded-full bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700">Pembayaran
                    terverifikasi</span>
            @endif
        </div>
        @if ($order->snap_redirect_url && in_array($order->payment_status, ['pending', 'challenge'], true))
            <a href="{{ $order->snap_redirect_url }}" target="_blank" rel="noopener noreferrer"
                class="mt-3 inline-block text-xs font-medium text-indigo-600 hover:text-indigo-700">Buka halaman
                pembayaran di tab baru</a>
        @endif
    </section>

    @if ($order->tracking_number || $order->shipped_at || $order->delivered_at)
        <section class="mb-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-gray-900">Informasi Pengiriman</h2>
            @if ($order->tracking_number)<p class="mt-2 text-sm text-gray-600">Nomor resi: <strong class="text-gray-900">{{ $order->tracking_number }}</strong></p>@endif
            @if ($order->shipped_at)<p class="mt-1 text-xs text-gray-500">Dikirim {{ $order->shipped_at->format('d M Y, H:i') }}</p>@endif
            @if ($order->delivered_at)<p class="mt-1 text-xs text-gray-500">Ditandai diterima {{ $order->delivered_at->format('d M Y, H:i') }}</p>@endif
        </section>
    @endif

    @if ($order->status === 'delivered' && ! $order->returnRequest)
        <section class="mb-5 rounded-xl border border-green-200 bg-green-50 p-5">
            <h2 class="font-semibold text-green-900">Pesanan telah ditandai diterima</h2>
            <p class="mt-1 text-sm text-green-800">Jika barang sudah kamu terima dengan baik, konfirmasikan pesanan. Jika ada masalah, ajukan pengembalian dalam 7 hari setelah penerimaan.</p>
            <form action="{{ route('orders.confirm-received', $order) }}" method="POST" class="mt-4">@csrf<button type="submit" class="rounded-lg bg-green-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-800" onclick="return confirm('Konfirmasi bahwa pesanan sudah diterima dengan baik?')">Konfirmasi Pesanan Diterima</button></form>
        </section>
    @endif

    @if (in_array($order->status, ['completed', 'success'], true) && $order->payment_status === 'paid')
        <section class="mb-6 rounded-2xl border border-amber-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Ulasan Produk</h2>
                    <p class="mt-1 text-sm text-gray-500">Bagikan pengalamanmu setelah menerima produk. Ulasan akan ditampilkan setelah disetujui admin.</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700"><i data-lucide="star" class="h-3.5 w-3.5"></i>Review pembelian</span>
            </div>

            <div class="mt-5 space-y-5">
                @foreach ($order->orderItems as $item)
                    @php
                        $existingReview = $item->product?->reviews->first();
                    @endphp
                    <article class="rounded-xl border border-gray-200 p-4 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ $item->product_name }}</h3>
                                <p class="mt-1 text-xs text-gray-500">{{ $item->quantity }} item dibeli</p>
                            </div>
                            @if ($existingReview)
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $existingReview->is_approved ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">{{ $existingReview->is_approved ? 'Ulasan ditampilkan' : 'Menunggu moderasi' }}</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">Belum diulas</span>
                            @endif
                        </div>

                        @if ($existingReview)
                            <div class="mt-4 rounded-lg bg-gray-50 p-4">
                                <div class="flex items-center gap-1 text-amber-500" aria-label="Rating {{ $existingReview->rating }} dari 5">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <i data-lucide="star" class="h-4 w-4 {{ $star <= $existingReview->rating ? 'fill-current' : 'text-gray-300' }}"></i>
                                    @endfor
                                    <span class="ml-1 text-xs font-semibold text-gray-700">{{ $existingReview->rating }}/5</span>
                                </div>
                                @if ($existingReview->title)<p class="mt-2 text-sm font-semibold text-gray-900">{{ $existingReview->title }}</p>@endif
                                <p class="mt-1 whitespace-pre-line text-sm text-gray-600">{{ $existingReview->comment }}</p>
                                @if ($existingReview->media->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($existingReview->media as $media)
                                            @if ($media->media_type === 'image')
                                                <a href="{{ Storage::disk('public')->url($media->path) }}" target="_blank" rel="noopener noreferrer"><img src="{{ Storage::disk('public')->url($media->path) }}" alt="Lampiran ulasan {{ $item->product_name }}" class="h-20 w-20 rounded-lg object-cover"></a>
                                            @else
                                                <a href="{{ Storage::disk('public')->url($media->path) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-medium text-indigo-600"><i data-lucide="video" class="h-4 w-4"></i>Lihat video</a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                                @if (! $existingReview->is_approved)
                                    <p class="mt-3 text-xs text-amber-700">Terima kasih! Ulasanmu sudah diterima dan sedang diperiksa oleh admin.</p>
                                @endif
                            </div>
                        @elseif ($item->product)
                            <form action="{{ route('product-reviews.store', $item->product) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                                @csrf
                                <div>
                                    <span class="mb-2 block text-sm font-medium text-gray-700">Rating produk</span>
                                    <div class="flex flex-wrap gap-3">
                                        @for ($rating = 5; $rating >= 1; $rating--)
                                            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm transition has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50 has-[:checked]:text-amber-700">
                                                <input type="radio" name="rating" value="{{ $rating }}" required class="sr-only" {{ old('rating') == $rating ? 'checked' : '' }}>
                                                <i data-lucide="star" class="h-4 w-4 fill-current text-amber-400"></i>{{ $rating }}
                                            </label>
                                        @endfor
                                    </div>
                                    @error('rating')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="review-title-{{ $item->id }}" class="mb-1 block text-sm font-medium text-gray-700">Judul ulasan (opsional)</label>
                                    <input id="review-title-{{ $item->id }}" type="text" name="title" value="{{ old('title') }}" maxlength="120" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: Kualitas produk sangat bagus">
                                    @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="review-comment-{{ $item->id }}" class="mb-1 block text-sm font-medium text-gray-700">Ceritakan pengalamanmu</label>
                                    <textarea id="review-comment-{{ $item->id }}" name="comment" rows="3" required minlength="5" maxlength="3000" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Bagaimana kualitas, fungsi, dan pengalaman menggunakan produk ini?">{{ old('comment') }}</textarea>
                                    @error('comment')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="review-media-{{ $item->id }}" class="mb-1 block text-sm font-medium text-gray-700">Foto atau video (opsional)</label>
                                    <input id="review-media-{{ $item->id }}" type="file" name="media[]" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-semibold">
                                    <p class="mt-1 text-xs text-gray-500">Maksimal 5 file, masing-masing 30 MB. Format JPG, PNG, WEBP, MP4, MOV, atau WEBM.</p>
                                    @error('media')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    @error('media.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"><i data-lucide="send" class="h-4 w-4"></i>Kirim Ulasan</button>
                            </form>
                        @else
                            <p class="mt-3 text-sm text-gray-500">Produk ini sudah tidak tersedia sehingga ulasan tidak dapat dikirim.</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if ($order->returnRequest)
        <section class="mb-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-gray-900">Pengajuan pengembalian</h2>
            <p class="mt-1 text-sm text-gray-600">Status: <strong>{{ str($order->returnRequest->status)->replace('_', ' ')->title() }}</strong></p>
            <p class="mt-2 text-sm text-gray-700">{{ $order->returnRequest->description }}</p>
            @if ($order->returnRequest->admin_note)<p class="mt-2 text-sm text-gray-600">Catatan admin: {{ $order->returnRequest->admin_note }}</p>@endif
            @if ($order->returnRequest->revisions->isNotEmpty())
                <div class="mt-4 space-y-3">
                    <h3 class="text-sm font-semibold text-gray-900">Riwayat dokumen tambahan</h3>
                    @foreach ($order->returnRequest->revisions as $revision)
                        <div class="rounded-lg border border-gray-200 p-3 text-sm">
                            <div class="flex items-center justify-between gap-2"><strong>Permintaan revisi {{ $loop->iteration }}</strong><span class="text-xs text-gray-500">{{ ucfirst($revision->status) }}</span></div>
                            <p class="mt-2 whitespace-pre-line">{{ $revision->message }}</p>
                            <p class="mt-1 text-xs text-gray-500">Dokumen: {{ collect($revision->required_documents)->map(fn ($doc) => ['video_unboxing' => 'Video unboxing', 'product_photos' => 'Foto produk', 'packaging_photos' => 'Foto kemasan', 'other' => 'Dokumen lainnya'][$doc] ?? $doc)->join(', ') }}</p>
                            @foreach ($revision->evidence_paths ?? [] as $path)
                                <a class="mt-2 mr-2 inline-block text-indigo-700 underline" href="{{ route('orders.returns.revisions.evidence', [$order->returnRequest, $revision, $loop->index]) }}">{{ $revision->evidence_original_names[$loop->index] ?? 'Lampiran '.$loop->iteration }}</a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif
            @if ($order->returnRequest->status === 'revision_requested')
                @php($pendingRevision = $order->returnRequest->revisions->firstWhere('status', 'requested'))
                @if ($pendingRevision)
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <h3 class="font-semibold text-amber-900">Admin meminta dokumen tambahan</h3>
                        <p class="mt-2 whitespace-pre-line text-sm text-amber-900">{{ $pendingRevision->message }}</p>
                        <p class="mt-2 text-xs text-amber-800">Dokumen wajib: {{ collect($pendingRevision->required_documents)->map(fn ($doc) => ['video_unboxing' => 'Video unboxing', 'product_photos' => 'Foto kondisi produk', 'packaging_photos' => 'Foto kemasan', 'other' => 'Dokumen lainnya'][$doc] ?? $doc)->join(', ') }}</p>
                        <form action="{{ route('orders.returns.revisions.submit', [$order->returnRequest, $pendingRevision]) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3">@csrf
                            <x-file-upload id="return-revision-evidence" label="Lampirkan dokumen tambahan" name="evidence[]" accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm,application/pdf" help-text="Foto maksimal 5 MB; video maksimal 100 MB. Maksimal 5 file dan 1 video." :max-size="100" :image-max-size="5" :video-max-size="100" :multiple="true" mode="deferred" />
                            <textarea name="customer_note" rows="2" maxlength="2000" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Catatan tambahan untuk admin (opsional)"></textarea>
                            <button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Kirim dokumen tambahan</button>
                        </form>
                    </div>
                @endif
            @elseif ($order->returnRequest->status === 'approved')
                <div class="mt-4 rounded-lg border border-indigo-100 bg-indigo-50 p-4 text-sm"><p class="font-semibold text-indigo-900">Alamat pengiriman barang kembali</p><p class="mt-2 font-medium">{{ $order->returnRequest->return_recipient }} · {{ $order->returnRequest->return_phone }}</p><p class="mt-1 whitespace-pre-line">{{ $order->returnRequest->return_address }}</p>@if($order->returnRequest->return_instructions)<p class="mt-2">Instruksi: {{ $order->returnRequest->return_instructions }}</p>@endif</div>
                @if (!$order->returnRequest->return_tracking_number)
                    <form action="{{ route('orders.returns.shipment', $order->returnRequest) }}" method="POST" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf<div><label class="mb-1 block text-sm font-medium">Kurir pengiriman retur</label><input name="return_courier" required class="w-full rounded-lg border-gray-300 text-sm" placeholder="JNE, J&T, SiCepat"></div><div><label class="mb-1 block text-sm font-medium">Nomor resi</label><input name="return_tracking_number" required class="w-full rounded-lg border-gray-300 text-sm"></div><div class="sm:col-span-2"><button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Kirim informasi pengiriman</button></div></form>
                @endif
            @elseif ($order->returnRequest->status === 'return_shipped')
                <div class="mt-3 rounded-lg bg-amber-50 p-3 text-sm"><p class="font-semibold">Barang retur sedang dikirim</p><p>{{ $order->returnRequest->return_courier }} · {{ $order->returnRequest->return_tracking_number }}</p></div>
            @elseif ($order->returnRequest->status === 'item_received')
                <p class="mt-3 rounded-lg bg-green-50 p-3 text-sm text-green-800">Barang retur sudah diterima admin dan sedang diperiksa/diproses.</p>
            @endif
            @if ($order->returnRequest->resolution_type === 'replacement')
                <div class="mt-3 rounded-lg border border-indigo-100 p-3 text-sm"><p class="font-semibold">Pengiriman barang pengganti</p><p>{{ $order->returnRequest->replacement_courier }} · {{ $order->returnRequest->replacement_tracking_number }}</p><p class="mt-1 text-xs text-gray-500">Dikirim {{ $order->returnRequest->replacement_shipped_at?->format('d M Y, H:i') }}</p></div>
            @elseif ($order->returnRequest->resolution_type === 'refund')
                <div class="mt-3 rounded-lg border border-green-100 bg-green-50 p-3 text-sm"><p class="font-semibold">Refund {{ $order->returnRequest->resolution_status === 'completed' ? 'selesai' : 'sedang diproses' }}</p><p>Nominal: Rp {{ number_format($order->returnRequest->refund_amount, 0, ',', '.') }}</p>@if($order->returnRequest->refund_proof_path)<a class="mt-1 inline-block text-indigo-700 underline" href="{{ route('order-returns.refund-proof', $order->returnRequest) }}">Lihat bukti refund</a>@endif</div>
            @endif
            <p class="mt-2 text-xs text-gray-500">@if($order->returnRequest->status === 'completed')Penyelesaian pengembalian telah selesai.@elseif($order->returnRequest->status === 'approved')Silakan kirim barang ke alamat yang tertera dan masukkan nomor resi.@elseif($order->returnRequest->status === 'revision_requested')Lengkapi dokumen yang diminta admin agar pengajuan dapat diperiksa kembali.@elseif($order->returnRequest->status === 'rejected')Pengajuan ditolak.@elsePengajuan sedang menunggu pemeriksaan admin.@endif</p>
        </section>
    @elseif (in_array($order->status, ['delivered', 'completed'], true) && $order->delivered_at && now()->lessThanOrEqualTo($order->delivered_at->copy()->addDays(7)))
        <section class="mb-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-gray-900">Ajukan Pengembalian</h2>
            <p class="mt-1 text-sm text-gray-500">Ajukan jika barang rusak, tidak sesuai, salah kirim, atau ada item yang kurang. Batas pengajuan 7 hari setelah pesanan diterima.</p>
            <form action="{{ route('orders.returns.store', $order) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3">@csrf
                <div><label for="return-reason" class="mb-1 block text-sm font-medium text-gray-700">Alasan pengembalian</label><select id="return-reason" name="reason" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"><option value="">Pilih alasan</option><option value="damaged">Barang rusak</option><option value="wrong_item">Barang tidak sesuai pesanan</option><option value="incomplete">Barang atau aksesori kurang</option><option value="not_as_described">Tidak sesuai deskripsi</option><option value="other">Alasan lainnya</option></select>@error('reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="return-description" class="mb-1 block text-sm font-medium text-gray-700">Detail masalah</label><textarea id="return-description" name="description" rows="4" required minlength="10" maxlength="3000" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" placeholder="Jelaskan kondisi barang dan alasan pengembalian..."></textarea>@error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div>
                    <x-file-upload
                        id="return-evidence"
                        label="Bukti foto atau video unboxing (opsional)"
                        name="evidence[]"
                        accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm"
                        help-text="Foto JPG, PNG, WEBP maksimal 5 MB; video MP4, MOV, WEBM maksimal 100 MB. Maksimal 5 file dan 1 video."
                        :max-size="100"
                        :image-max-size="5"
                        :video-max-size="100"
                        :multiple="true"
                        mode="deferred"
                    />
                    @error('evidence')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    @error('evidence.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Kirim Pengajuan Pengembalian</button>
            </form>
        </section>
    @endif

    <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-12 lg:gap-6">
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-8">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Item Pesanan</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $order->orderItems->count() }} item dalam pesanan ini</p>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach ($order->orderItems as $item)
                    <div class="flex items-start justify-between gap-4 px-5 py-5">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900">{{ $item->product_name }}</p>
                            <p class="mt-1.5 text-sm text-gray-500">{{ $item->quantity }} × Rp
                                {{ number_format($item->price, 0, ',', '.') }}</p>
                        </div>
                        <p class="shrink-0 text-sm font-semibold text-gray-900">Rp
                            {{ number_format($item->total, 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
            <div class="space-y-2 border-t border-gray-100 bg-gray-50/70 px-5 py-4 text-sm">
                <div class="flex justify-between gap-4"><span class="text-gray-500">Subtotal produk</span><span class="font-medium text-gray-900">Rp {{ number_format($order->subtotal ?? $order->total, 0, ',', '.') }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-gray-500">Ongkos kirim @if($order->shipping_courier) ({{ $order->shipping_courier }} — {{ $order->shipping_service }}) @endif</span><span class="font-medium text-gray-900">Rp {{ number_format($order->shipping_cost ?? 0, 0, ',', '.') }}</span></div>
                @if($order->shipping_etd)<p class="text-xs text-gray-500">Estimasi pengiriman: {{ $order->shipping_etd }}</p>@endif
                <div class="flex items-center justify-between gap-4 border-t border-gray-200 pt-3"><span class="font-semibold text-gray-700">Total Pesanan</span><span class="text-lg font-bold text-gray-900">Rp {{ number_format($order->total, 0, ',', '.') }}</span></div>
            </div>
        </section>

        <aside class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-4">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Informasi Pemesan</h2>
                <p class="mt-1 text-sm text-gray-500">Detail kontak dan pengiriman</p>
            </div>
            <dl class="divide-y divide-gray-100 px-5">
                <div class="py-4">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Nama</dt>
                    <dd class="mt-1.5 text-sm font-semibold text-gray-900">{{ $order->user_name }}</dd>
                </div>
                <div class="py-4">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Email</dt>
                    <dd class="mt-1.5 break-all text-sm font-medium text-gray-900">{{ $order->user_email }}</dd>
                </div>
                <div class="py-4">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Penerima</dt>
                    <dd class="mt-1.5 text-sm leading-6 text-gray-900">
                        {{ $order->recipient_name ?: $order->user_name }}<br>{{ $order->user_phone ?: 'No. telepon tidak tersedia' }}
                    </dd>
                    <dt class="mt-4 text-xs font-medium uppercase tracking-wide text-gray-500">Alamat pengiriman</dt>
                    <dd class="mt-1.5 text-sm leading-6 text-gray-900">
                        {{ collect([$order->address, $order->city, $order->state, $order->zip, $order->country])->filter()->join(', ') ?:'Belum diisi' }}
                    </dd>
                </div>
            </dl>
        </aside>
    </div>

    @if ($order->snap_token && in_array($order->payment_status, ['pending', 'challenge'], true))
        @push('head')
            <script src="{{ config('midtrans.snap_js_url') }}" data-client-key="{{ config('midtrans.client_key') }}"></script>
        @endpush
        @push('scripts')
            <script>
                document.getElementById('pay-order-button')?.addEventListener('click', () => {
                    console.log('Pay order button clicked');
                    window.snap.pay(@js($order->snap_token), {
                        onSuccess: () => window.location.reload(),
                        onPending: () => window.location.reload(),
                        onError: () => window.location.reload(),
                        onClose: () => {},
                    });
                });
            </script>
        @endpush
    @endif

    </div>
</x-layout-component>
