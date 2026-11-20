<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} - {{ $order->order_number }}</title>
</head>

<body style="margin:0;padding:0;background:#f3f4f6;color:#1f2937;font-family:Arial,Helvetica,sans-serif">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 12px;background:#f3f4f6">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                    style="max-width:600px;background:#fff;border-radius:16px;overflow:hidden">
                    <tr>
                        <td style="padding:26px 32px;background:#4f46e5;color:#fff;font-size:20px;font-weight:bold">
                            {{ $appName }}</td>
                    </tr>
                    <tr>
                        <td style="padding:34px 32px">
                            @php
                                $content = match ($type) {
                                    'order_created' => [
                                        'Pesanan berhasil dibuat',
                                        'Pesanan Anda sudah tercatat. Silakan periksa rincian tagihan dan lanjutkan pembayaran melalui halaman pesanan.',
                                    ],
                                    'payment_successful' => [
                                        'Pembayaran berhasil diterima',
                                        'Pembayaran Anda telah diverifikasi. Terima kasih telah berbelanja. Bukti pembayaran PDF terlampir pada email ini.',
                                    ],
                                    'order_processing' => [
                                        'Pesanan sedang disiapkan',
                                        'Pembayaran telah diterima dan pesanan Anda sedang diproses oleh tim kami.',
                                    ],
                                    'order_shipped' => [
                                        'Pesanan telah dikirim',
                                        'Pesanan Anda telah diserahkan kepada kurir. Gunakan nomor resi berikut untuk melacak pengiriman.',
                                    ],
                                    'order_delivered' => [
                                        'Pesanan telah sampai',
                                        'Pesanan Anda telah ditandai diterima. Terima kasih telah berbelanja bersama kami.',
                                    ],
                                    default => ['Pembaruan pesanan', 'Ada pembaruan untuk pesanan Anda.'],
                                };
                            @endphp
                            <p style="margin:0 0 10px;color:#6b7280;font-size:13px">Halo {{ $customerName }},</p>
                            <h1 style="margin:0 0 16px;color:#111827;font-size:25px;line-height:1.3">{{ $content[0] }}
                            </h1>
                            <p style="margin:0 0 22px;color:#4b5563;font-size:15px;line-height:1.7">{{ $content[1] }}
                            </p>
                            <div style="padding:18px;border:1px solid #e5e7eb;border-radius:12px;margin-bottom:24px">
                                <p style="margin:0 0 8px;color:#6b7280;font-size:12px">NOMOR PESANAN</p>
                                <p style="margin:0 0 14px;font-size:17px;font-weight:bold;color:#111827">
                                    {{ $order->order_number }}</p>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="padding:5px 0;color:#6b7280;font-size:13px">Total</td>
                                        <td align="right" style="padding:5px 0;font-size:14px;font-weight:bold">Rp
                                            {{ number_format($order->total, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:5px 0;color:#6b7280;font-size:13px">Status pembayaran</td>
                                        <td align="right" style="padding:5px 0;font-size:13px;font-weight:bold">
                                            {{ ucfirst($order->payment_status ?? 'pending') }}</td>
                                    </tr>
                                    @if ($type === 'order_shipped')
                                        <tr>
                                            <td style="padding:5px 0;color:#6b7280;font-size:13px">Kurir</td>
                                            <td align="right" style="padding:5px 0;font-size:13px">
                                                {{ strtoupper($order->shipping_courier ?? '-') }}
                                                {{ $order->shipping_service ?? '' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:5px 0;color:#6b7280;font-size:13px">Nomor resi</td>
                                            <td align="right" style="padding:5px 0;font-size:13px;font-weight:bold">
                                                {{ $order->tracking_number ?? '-' }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin-bottom:18px">
                                <tr>
                                    <td style="border-radius:8px;background:#4f46e5"><a
                                            href="{{ $type === 'order_created' ? $paymentUrl : $orderUrl }}"
                                            style="display:inline-block;padding:13px 22px;color:#fff;text-decoration:none;font-size:14px;font-weight:bold">{{ $type === 'order_created' ? 'Lanjutkan Pembayaran' : 'Lihat Pesanan' }}</a>
                                    </td>
                                </tr>
                            </table>
                            @if (in_array($type, ['order_created', 'payment_successful'], true))
                                <p style="margin:0 0 18px;color:#6b7280;font-size:13px;line-height:1.6">
                                    {{ $type === 'order_created' ? 'Invoice PDF terlampir pada email ini.' : 'Bukti pembayaran PDF terlampir pada email ini.' }}
                                    Anda juga dapat mengunduh dokumen dari halaman detail pesanan.</p>
                            @endif
                            <p style="margin:26px 0 0;color:#4b5563;font-size:14px;line-height:1.7">
                                Salam,<br><strong>Tim {{ $appName }}</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td
                            style="padding:20px 32px;border-top:1px solid #e5e7eb;background:#fafafa;text-align:center;color:#9ca3af;font-size:11px;line-height:1.6">
                            Email otomatis dari {{ $appName }}. Mohon jangan membalas email ini.<br>&copy;
                            {{ date('Y') }} {{ $appName }}.</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
