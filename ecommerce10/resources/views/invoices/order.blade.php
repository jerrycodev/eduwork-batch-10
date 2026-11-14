<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $isPaid ? 'Bukti Pembayaran' : 'Invoice' }} {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; line-height: 1.55; }
        .header { border-bottom: 3px solid #4f46e5; padding-bottom: 18px; margin-bottom: 24px; }
        .brand { color: #4f46e5; font-size: 22px; font-weight: bold; }
        .document-title { color: #111827; font-size: 20px; font-weight: bold; text-align: right; }
        .muted { color: #6b7280; }
        .grid { width: 100%; margin-bottom: 24px; }
        .grid td { width: 50%; vertical-align: top; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .items th { background: #f3f4f6; color: #374151; text-align: left; padding: 10px 8px; }
        .items td { border-bottom: 1px solid #e5e7eb; padding: 10px 8px; }
        .right { text-align: right !important; }
        .totals { width: 48%; margin: 18px 0 0 auto; border-collapse: collapse; }
        .totals td { padding: 5px 0; }
        .grand-total { border-top: 2px solid #111827; font-size: 14px; font-weight: bold; }
        .status { display: inline-block; margin-top: 8px; padding: 5px 9px; border-radius: 4px; background: {{ $isPaid ? '#dcfce7' : '#fef3c7' }}; color: {{ $isPaid ? '#166534' : '#92400e' }}; font-weight: bold; }
        .footer { margin-top: 42px; border-top: 1px solid #e5e7eb; padding-top: 14px; text-align: center; color: #9ca3af; font-size: 9px; }
    </style>
</head>
<body>
    <table class="header" width="100%"><tr>
        <td><div class="brand">{{ $appName }}</div><div class="muted">Dokumen transaksi elektronik</div></td>
        <td class="right"><div class="document-title">{{ $isPaid ? 'BUKTI PEMBAYARAN' : 'INVOICE' }}</div><strong>{{ $order->order_number }}</strong><br><span class="muted">Tanggal: {{ $order->created_at->format('d/m/Y H:i') }}</span><br><span class="status">{{ $isPaid ? 'LUNAS' : 'MENUNGGU PEMBAYARAN' }}</span></td>
    </tr></table>

    <table class="grid"><tr>
        <td><strong>Ditagihkan kepada</strong><br>{{ $order->user_name }}<br>{{ $order->user_email }}<br>{{ $order->user_phone }}</td>
        <td><strong>Alamat pengiriman</strong><br>{{ $order->recipient_name }}<br>{{ $order->address }}<br>{{ collect([$order->city, $order->state, $order->zip, $order->country])->filter()->implode(', ') }}</td>
    </tr></table>

    <strong>Rincian pesanan</strong>
    <table class="items">
        <thead><tr><th>Produk</th><th class="right">Qty</th><th class="right">Harga</th><th class="right">Subtotal</th></tr></thead>
        <tbody>
            @foreach ($order->orderItems as $item)
                <tr><td>{{ $item->product_name }}</td><td class="right">{{ $item->quantity }}</td><td class="right">Rp {{ number_format($item->price, 0, ',', '.') }}</td><td class="right">Rp {{ number_format($item->total, 0, ',', '.') }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal produk</td><td class="right">Rp {{ number_format($order->subtotal ?? $order->orderItems->sum('total'), 0, ',', '.') }}</td></tr>
        <tr><td>Pengiriman</td><td class="right">Rp {{ number_format($order->shipping_cost ?? 0, 0, ',', '.') }}</td></tr>
        <tr class="grand-total"><td>Total</td><td class="right">Rp {{ number_format($order->total, 0, ',', '.') }}</td></tr>
    </table>

    @if ($isPaid)
        <p style="margin-top:28px"><strong>Pembayaran diterima</strong><br>Metode: {{ strtoupper($order->payment_type ?? '-') }}<br>ID Transaksi: {{ $order->transaction_id ?? '-' }}<br>Waktu pembayaran: {{ $order->paid_at?->format('d/m/Y H:i') ?? '-' }}</p>
    @else
        <p style="margin-top:28px" class="muted">Invoice ini merupakan tagihan untuk pesanan Anda. Status pembayaran akan diperbarui setelah pembayaran diverifikasi.</p>
    @endif

    <div class="footer">Dokumen ini dibuat secara otomatis oleh {{ $appName }} dan sah tanpa tanda tangan atau stempel.</div>
</body>
</html>
