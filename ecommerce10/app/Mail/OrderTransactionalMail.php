<?php

namespace App\Mail;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderTransactionalMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $type,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            'order_created' => 'Pesanan '.$this->order->order_number.' berhasil dibuat',
            'payment_successful' => 'Pembayaran pesanan '.$this->order->order_number.' berhasil',
            'order_processing' => 'Pesanan '.$this->order->order_number.' sedang diproses',
            'order_shipped' => 'Pesanan '.$this->order->order_number.' telah dikirim',
            'order_delivered' => 'Pesanan '.$this->order->order_number.' telah sampai',
            default => 'Pembaruan pesanan '.$this->order->order_number,
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.transactional',
            with: [
                'appName' => config('app.name'),
                'customerName' => $this->order->user_name,
                'order' => $this->order,
                'type' => $this->type,
                'orderUrl' => route('orders.customer.show', $this->order),
                'paymentUrl' => route('orders.customer.show', $this->order),
                'invoiceUrl' => route('orders.invoice', $this->order),
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        if (! in_array($this->type, ['order_created', 'payment_successful'], true)) {
            return [];
        }

        $isPaid = $this->type === 'payment_successful';
        $filename = ($isPaid ? 'bukti-pembayaran-' : 'invoice-').$this->order->order_number.'.pdf';
        $pdf = Pdf::loadView('invoices.order', [
            'appName' => config('app.name'),
            'order' => $this->order,
            'isPaid' => $isPaid,
        ])->setPaper('a4');

        return [
            Attachment::fromData(
                fn (): string => $pdf->output(),
                $filename,
            )->withMime('application/pdf'),
        ];
    }
}
