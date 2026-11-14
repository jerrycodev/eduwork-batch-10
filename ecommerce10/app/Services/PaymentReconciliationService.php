<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;

class PaymentReconciliationService
{
    /** @return Collection<int, array{severity:string, code:string, title:string, detail:string, action:string, order:Order}> */
    public function findings(): Collection
    {
        return Order::query()
            ->with(['cancellationRequest', 'returnRequest'])
            ->latest()
            ->limit(1000)
            ->get()
            ->flatMap(fn (Order $order): array => $this->inspect($order))
            ->sortBy(fn (array $finding): int => $finding['severity'] === 'critical' ? 0 : ($finding['severity'] === 'warning' ? 1 : 2))
            ->values();
    }

    private function toMinorUnits(string $amount): int
    {
        if (preg_match('/^\\d+(?:\\.\\d{1,2})?$/D', $amount) !== 1) {
            return -1;
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    /** @return array<int, array{severity:string, code:string, title:string, detail:string, action:string, order:Order}> */
    private function inspect(Order $order): array
    {
        $findings = [];
        $add = function (string $severity, string $code, string $title, string $detail, string $action) use (&$findings, $order): void {
            $findings[] = compact('severity', 'code', 'title', 'detail', 'action', 'order');
        };

        if (in_array($order->status, ['processing', 'shipped', 'delivered'], true) && $order->payment_status !== 'paid') {
            $add('critical', 'fulfillment_unpaid', 'Fulfillment tanpa pembayaran terverifikasi', 'Status pesanan '.$order->status.' tetapi pembayaran '.$order->payment_status.'.', 'Periksa bukti pembayaran dan status transaksi Midtrans sebelum melanjutkan fulfillment.');
        }

        if ($order->payment_status === 'paid') {
            if (! $order->transaction_id || ! $order->paid_at) {
                $add('warning', 'paid_missing_evidence', 'Data pembayaran belum lengkap', 'Pesanan berstatus paid tetapi transaction ID atau waktu pembayaran tidak tersedia.', 'Verifikasi transaksi langsung ke Midtrans.');
            }

            $remoteGross = data_get($order->payment_payload, 'gross_amount');
            if ($remoteGross !== null && is_numeric($remoteGross) && $this->toMinorUnits((string) $remoteGross) !== ((int) $order->total * 100)) {
                $add('critical', 'paid_amount_mismatch', 'Nominal pembayaran tidak cocok', 'Nominal notifikasi tersimpan berbeda dari total pesanan.', 'Tahan pemrosesan lanjutan dan verifikasi transaksi ke Midtrans.');
            }

            if ($order->status === 'cancelled') {
                $add('critical', 'paid_cancelled_order', 'Pesanan dibatalkan tetapi sudah dibayar', 'Pembayaran tercatat paid sedangkan fulfillment berstatus cancelled.', 'Periksa transaksi terlambat dan selesaikan refund atau tindak lanjut secara manual.');
            }

            if ($order->cancellationRequest?->status === 'approved') {
                $add('warning', 'approved_cancellation_unsettled', 'Pembatalan disetujui, refund belum tercatat', 'Pengajuan pembatalan disetujui sementara pembayaran masih paid.', 'Verifikasi transfer/refund sebelum menyelesaikan pembatalan.');
            }
        }

        if ($order->returnRequest?->resolution_status === 'processing' && $order->returnRequest?->resolution_type === 'refund') {
            $add('info', 'refund_processing', 'Refund masih diproses', 'Permintaan refund retur belum ditandai selesai.', 'Verifikasi status refund Midtrans atau bukti transfer manual sebelum menutup proses.');
        }

        return $findings;
    }
}
