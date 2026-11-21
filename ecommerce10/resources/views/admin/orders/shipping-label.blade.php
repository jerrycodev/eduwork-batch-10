<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label Pengiriman {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; color: #111827; font-family: Arial, sans-serif; background: #f3f4f6; }
        .toolbar { max-width: 100mm; margin: 0 auto 16px; display: flex; justify-content: flex-end; }
        .toolbar button { border: 0; border-radius: 8px; padding: 10px 16px; color: white; background: #0f766e; font-weight: 700; cursor: pointer; }
        .label { width: 100mm; min-height: 150mm; margin: auto; padding: 7mm; background: white; border: 1px solid #d1d5db; }
        .top { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; border-bottom: 2px solid #111827; padding-bottom: 4mm; }
        .brand { font-size: 18px; font-weight: 800; letter-spacing: .5px; }
        .order { text-align: right; font-size: 11px; overflow-wrap: anywhere; }
        .section { padding: 4mm 0; border-bottom: 1px solid #9ca3af; }
        .eyebrow { margin-bottom: 2mm; color: #4b5563; font-size: 9px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; }
        .recipient { font-size: 16px; font-weight: 800; }
        .phone { margin-top: 1mm; font-size: 13px; font-weight: 700; }
        .address { margin-top: 2mm; font-size: 12px; line-height: 1.45; overflow-wrap: anywhere; }
        .courier { display: flex; justify-content: space-between; gap: 8px; font-size: 12px; font-weight: 700; }
        .awb { margin-top: 2mm; padding: 4mm 2mm; border: 2px dashed #111827; text-align: center; }
        .awb-label { font-size: 9px; font-weight: 700; letter-spacing: 1px; }
        .awb-number { margin-top: 2mm; font-family: monospace; font-size: 19px; font-weight: 800; overflow-wrap: anywhere; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th, td { padding: 2mm 1mm; border-bottom: 1px solid #d1d5db; text-align: left; }
        th:last-child, td:last-child { text-align: right; }
        .footer { padding-top: 4mm; font-size: 9px; color: #4b5563; text-align: center; }
        @page { size: 100mm 150mm; margin: 0; }
        @media print {
            body { padding: 0; background: white; }
            .toolbar { display: none; }
            .label { width: 100mm; min-height: 150mm; border: 0; margin: 0; page-break-after: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Cetak label pengiriman</button></div>
    <main class="label">
        <header class="top">
            <div class="brand">RevStore</div>
            <div class="order"><strong>{{ $order->order_number }}</strong><br>{{ $order->created_at?->format('d M Y') }}</div>
        </header>

        <section class="section">
            <div class="eyebrow">Kepada / Penerima</div>
            <div class="recipient">{{ $order->recipient_name ?: $order->user_name }}</div>
            <div class="phone">{{ $order->user_phone ?: 'Nomor telepon tidak tersedia' }}</div>
            <div class="address">
                {{ $order->address }}<br>
                {{ collect([$order->city, $order->state, $order->zip, $order->country])->filter()->join(', ') }}
            </div>
        </section>

        <section class="section">
            <div class="eyebrow">Kurir pengiriman</div>
            <div class="courier">
                <span>{{ strtoupper($order->shipping_courier ?: 'Kurir') }}</span>
                <span>{{ $order->shipping_service }}</span>
            </div>
            <div class="awb">
                <div class="awb-label">NOMOR RESI / AWB</div>
                <div class="awb-number">{{ $order->biteship_waybill_id ?: $order->tracking_number ?: 'Belum diterbitkan' }}</div>
            </div>
        </section>

        <section class="section">
            <div class="eyebrow">Isi paket · {{ $order->orderItems->sum('quantity') }} item</div>
            <table>
                <thead><tr><th>Produk</th><th>Qty</th></tr></thead>
                <tbody>
                    @foreach ($order->orderItems as $item)
                        <tr>
                            <td>{{ $item->product_name }}@if ($item->product_variant_name && $item->product_variant_name !== 'Default')<br><small>{{ $item->product_variant_name }}</small>@endif</td>
                            <td>{{ $item->quantity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="section">
            <div class="eyebrow">Pengirim</div>
            <strong>{{ $warehouse['name'] ?? 'RevStore' }}</strong>
            <div class="address">{{ $warehouse['phone'] ?? '' }}<br>{{ $warehouse['address'] ?? '' }}<br>{{ $warehouse['postal_code'] ?? '' }}</div>
        </section>
        <footer class="footer">Terima kasih telah berbelanja di RevStore.</footer>
    </main>
</body>
</html>
