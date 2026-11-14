<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-900">Detail Order {{ $order->order_number }}</h2>
                <p class="mt-1 text-sm text-gray-500">Placed {{ $order->created_at->format('d M Y, H:i') }}</p>
            </div><a href="{{ route('orders.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"><i
                    data-lucide="arrow-left" class="h-4 w-4"></i>Back to orders</a>
        </div>
    </x-slot>
    @if (session('success'))
        <div class="mx-auto mt-5 max-w-7xl px-4 sm:px-6 lg:px-8"><x-alert
                class="border border-green-200 bg-green-50 text-green-800">{{ session('success') }}</x-alert></div>
    @endif
    @if ($errors->any())
        <div class="mx-auto mt-5 max-w-7xl space-y-1 px-4 text-sm text-red-600 sm:px-6 lg:px-8">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    <div class="py-8 flex flex-col gap-4">
        <div class="mx-auto grid w-full max-w-7xl grid-cols-1 items-start gap-5 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-2">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">Order items</h3>
                    <p class="mt-0.5 text-sm text-gray-500">{{ $order->orderItems->count() }} line items</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3.5 font-semibold">Product</th>
                                <th class="px-5 py-3.5 font-semibold">Unit price</th>
                                <th class="px-5 py-3.5 font-semibold">Qty</th>
                                <th class="px-5 py-3.5 text-right font-semibold">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($order->orderItems as $item)
                                <tr>
                                    <td class="px-5 py-4 font-medium text-gray-900">{{ $item->product_name }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-600">Rp
                                        {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-gray-600">{{ $item->quantity }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-gray-900">Rp
                                        {{ number_format($item->total, 0, ',', '.') }}</td>
                            </tr>@empty<tr>
                                    <td colspan="4" class="px-5 py-10 text-center text-gray-500">No items in this
                                        order.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="space-y-2 border-t border-gray-100 bg-gray-50 px-5 py-4 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Subtotal produk</span><span>Rp
                            {{ number_format($order->subtotal ?? $order->total, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Ongkir @if ($order->shipping_courier)
                                ({{ $order->shipping_courier }} — {{ $order->shipping_service }})
                            @endif
                        </span>
                        <span>Rp {{ number_format($order->shipping_cost ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-3 font-semibold"><span>Total
                            order</span><span>Rp {{ number_format($order->total, 0, ',', '.') }}</span></div>
                </div>
            </section>
            <aside class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">Customer information</h3>
                </div>
                <dl class="divide-y divide-gray-100 px-5">
                    @foreach ([
        ['Customer', $order->user_name],
        ['Email', $order->user_email],
        ['Recipient', ($order->recipient_name ?: $order->user_name) . ' · ' . ($order->user_phone ?: 'No. telepon tidak tersedia')],
        [
            'Shipping address',
            collect([$order->address, $order->city, $order->state, $order->zip, $order->country])->filter()->join(', ') ?:
            'Belum diisi',
        ],
        ['Order date', $order->created_at->format('d M Y, H:i')],
    ] as [$label, $value])
                        <div class="py-3.5">
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}
                            </dt>
                            <dd class="mt-1 break-words text-sm font-medium text-gray-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                    <div class="py-3.5">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Status pesanan</dt>
                        <dd class="mt-2 text-sm font-semibold text-gray-900">{{ ucfirst($order->status) }}</dd>
                    </div>
                    <div class="py-3.5">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Status pembayaran</dt>
                        <dd class="mt-2 text-sm font-semibold text-gray-900">
                            {{ ucfirst($order->payment_status ?? 'unpaid') }}</dd>
                    </div>
                </dl>
            </aside>

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-3">
                <h3 class="font-semibold text-gray-900">Update status pesanan</h3>
                <p class="mt-1 text-sm text-gray-500">Admin mengatur proses pesanan. Completed hanya ditetapkan oleh
                    konfirmasi pelanggan atau sistem otomatis.</p>
                @if ($order->cancellationRequest && in_array($order->cancellationRequest->status, ['requested', 'approved'], true))
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <p class="font-semibold">Update status pesanan dikunci</p>
                        <p class="mt-1">Ada pengajuan pembatalan yang {{ $order->cancellationRequest->status === 'requested' ? 'menunggu keputusan admin' : 'telah disetujui dan menunggu penyelesaian refund' }}. Pesanan tidak dapat diproses atau dilanjutkan ke tahap berikutnya sampai pengajuan ditolak atau pembatalan diselesaikan.</p>
                    </div>
                @elseif (in_array($order->status, ['pending', 'processing', 'shipped'], true))
                    <form action="{{ route('orders.status.update', $order) }}" method="POST"
                        class="mt-4 grid gap-3 sm:grid-cols-2">@csrf @method('PATCH')
                        <div><label for="order-status" class="mb-1.5 block text-sm font-medium text-gray-700">Status
                                berikutnya</label><select id="order-status" name="status" required
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm">
                                @if ($order->status === 'pending')
                                    <option value="processing">Processing</option>
                                    @if ($order->payment_status !== 'paid')<option value="cancelled">Cancelled</option>@endif
                                @elseif ($order->status === 'processing')
                                    <option value="shipped">Shipped</option>
                                    @if ($order->payment_status !== 'paid')<option value="cancelled">Cancelled</option>@endif
                                @elseif ($order->status === 'shipped')
                                    <option value="delivered">Delivered</option>
                                @endif
                            </select></div>
                        @if ($order->status === 'processing')
                            <div><label for="tracking-number"
                                    class="mb-1.5 block text-sm font-medium text-gray-700">Nomor
                                    resi</label><input id="tracking-number" name="tracking_number"
                                    value="{{ old('tracking_number', $order->tracking_number) }}" required
                                    maxlength="100"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
                                    placeholder="Masukkan nomor resi">
                                @error('tracking_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                        <div class="sm:col-span-2"><button type="submit"
                                class="inline-flex justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Simpan
                                status</button></div>
                    </form>
                @endif
                @if ($order->tracking_number)
                    <p class="mt-3 text-sm text-gray-700">Nomor resi: <strong>{{ $order->tracking_number }}</strong>
                    </p>
                @endif
                @if ($order->delivered_at && $order->status === 'delivered')
                    <p class="mt-3 text-xs text-amber-700">Auto-complete dijadwalkan pada
                        {{ $order->auto_complete_at?->format('d M Y, H:i') ?? '—' }}.</p>
                @endif
                @if ($order->payment_status !== 'paid' && $order->payment_status !== 'refunded')
                    <p class="mt-3 text-xs text-amber-700">Pesanan belum dapat diproses sebelum pembayaran
                        terverifikasi.
                    </p>
                @endif
                @if ($order->status === 'cancelled')
                    <p class="mt-3 text-xs text-red-700">Pesanan dibatalkan. Tidak dapat diproses lebih lanjut.</p>
                @endif
            </section>

        </div>

        @if ($order->cancellationRequest)
        <section class="mx-auto w-full mb-8 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-amber-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-semibold text-gray-900">Pengajuan pembatalan</h3>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">{{ str($order->cancellationRequest->status)->replace('_', ' ')->title() }}</span>
                </div>
                <p class="mt-3 text-sm text-gray-700">Alasan: {{ str($order->cancellationRequest->reason)->replace('_', ' ')->title() }}</p>
                @if ($order->cancellationRequest->description)<p class="mt-1 whitespace-pre-line text-sm text-gray-600">{{ $order->cancellationRequest->description }}</p>@endif
                @if ($order->cancellationRequest->admin_note)<p class="mt-2 text-sm text-gray-600">Catatan admin: {{ $order->cancellationRequest->admin_note }}</p>@endif
                @if ($order->cancellationRequest->status === 'requested' || $order->cancellationRequest->status === 'approved')
                    <form action="{{ route('order-cancellations.update', $order->cancellationRequest) }}" method="POST" enctype="multipart/form-data" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf @method('PATCH')
                        <div><label for="cancellation-status" class="mb-1 block text-sm font-medium text-gray-700">Keputusan</label><select id="cancellation-status" name="status" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                            @if ($order->cancellationRequest->status === 'requested')<option value="approved">Setujui pembatalan</option><option value="rejected">Tolak pengajuan</option>@else<option value="completed">Selesaikan pembatalan (refund sudah diproses)</option>@endif
                        </select></div>
                        <div><label for="cancellation-admin-note" class="mb-1 block text-sm font-medium text-gray-700">Catatan admin {{ $order->payment_status === 'paid' ? '(wajib saat refund selesai)' : '(wajib saat menolak)' }}</label><textarea id="cancellation-admin-note" name="admin_note" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" placeholder="Alasan keputusan / referensi refund">{{ old('admin_note') }}</textarea>@error('admin_note')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        @if ($order->cancellationRequest->status === 'approved' && $order->payment_status === 'paid')
                            <div class="sm:col-span-2">
                                @include('admin.products._file_upload', [
                                    'fileUploadId' => 'refund-proof-upload-'.$order->cancellationRequest->id,
                                    'fileUploadLabel' => 'Bukti transaksi refund',
                                    'fileUploadName' => 'refund_proof',
                                    'fileUploadAccept' => 'image/jpeg,image/png,image/webp,application/pdf',
                                    'fileUploadHelpText' => 'JPG, PNG, WEBP, or PDF',
                                    'fileUploadMaxSize' => 5,
                                    'fileUploadMode' => 'deferred',
                                    'fileUploadMultiple' => false,
                                ])
                                @error('refund_proof')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @endif
                        <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Simpan keputusan</button></div>
                    </form>
                    @if ($order->cancellationRequest->status === 'approved')<p class="mt-3 text-xs text-amber-700">Pesanan ditahan. Untuk pesanan yang sudah dibayar, proses refund melalui kanal pembayaran terlebih dahulu. Isi referensi refund pada catatan admin sebelum menyelesaikan.</p>@endif
                @endif
                @if ($order->cancellationRequest->refund_proof_path)
                    <div class="mt-4 rounded-lg border border-purple-100 bg-purple-50 p-3 text-sm"><p class="font-semibold text-purple-900">Bukti refund tersimpan</p><a href="{{ route('order-cancellations.proof', $order->cancellationRequest) }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex font-medium text-purple-700 underline">Lihat bukti transaksi · {{ $order->cancellationRequest->refund_proof_original_name }}</a></div>
                @endif
            </div>
        </section>
    @endif

    @if ($order->returnRequest)
        <section class="mx-auto w-full mb-8 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-gray-900">Pengajuan pengembalian</h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ str($order->returnRequest->reason)->replace('_', ' ')->title() }} ·
                    {{ ucfirst($order->returnRequest->status) }}</p>
                <p class="mt-3 whitespace-pre-line text-sm text-gray-700">{{ $order->returnRequest->description }}</p>
                @if ($order->returnRequest->evidence_paths)
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach ($order->returnRequest->evidence_paths as $path)
                            <a href="{{ route('order-returns.evidence', [$order->returnRequest, $loop->index]) }}"
                                target="_blank" rel="noopener noreferrer"
                                class="rounded-lg border px-3 py-2 text-sm font-medium text-indigo-700">Lihat bukti
                                {{ $loop->iteration }}</a>
                        @endforeach
                    </div>
                @endif
                @if ($order->returnRequest->admin_note)
                    <p class="mt-3 text-sm text-gray-600">Catatan admin: {{ $order->returnRequest->admin_note }}</p>
                @endif
                @if (!in_array($order->returnRequest->status, ['rejected', 'completed'], true))
                    <form action="{{ route('order-returns.update', $order->returnRequest) }}" method="POST"
                        class="mt-4 grid gap-3 sm:grid-cols-2">@csrf @method('PATCH')
                        <div><label for="return-status"
                                class="mb-1 block text-sm font-medium text-gray-700">Tindakan</label><select
                                id="return-status" name="status"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                                @if ($order->returnRequest->status === 'requested')
                                    <option value="approved">Setujui pengembalian</option>
                                    <option value="rejected">Tolak pengajuan</option>
                                    @endif @if ($order->returnRequest->status === 'approved')
                                        <option value="completed">Tandai selesai (refund sudah diproses)</option>
                                    @endif
                            </select></div>
                        <div><label for="admin-note" class="mb-1 block text-sm font-medium text-gray-700">Catatan
                                admin (wajib untuk penolakan)</label>
                            <textarea id="admin-note" name="admin_note" rows="2"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"></textarea>
                        </div>
                        <div class="sm:col-span-2"><button type="submit"
                                class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Simpan
                                keputusan</button></div>
                    </form>
                @endif
            </div>
        </section>
    @endif
    </div>

</x-app-layout>
