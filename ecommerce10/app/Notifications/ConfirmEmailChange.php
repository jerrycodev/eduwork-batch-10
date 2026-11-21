<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmEmailChange extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $confirmationUrl,
        private readonly string $deliveryLogId,
        private readonly string $recipientName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Konfirmasi Perubahan Email RevStore')
            ->greeting('Halo '.$this->recipientName.',')
            ->line('Kami menerima permintaan untuk mengubah alamat email akun RevStore Anda.')
            ->line('Klik tombol di bawah untuk mengonfirmasi alamat email baru. Email akun Anda belum berubah sampai konfirmasi berhasil.')
            ->action('Konfirmasi Email Baru', $this->confirmationUrl)
            ->line('Tautan ini hanya berlaku selama 60 menit dan hanya dapat digunakan satu kali.')
            ->line('Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini. Email lama Anda akan tetap aktif.')
            ->withSymfonyMessage(fn ($message) => $message->getHeaders()->addTextHeader('X-Email-Delivery-Log-ID', $this->deliveryLogId));
    }
}
