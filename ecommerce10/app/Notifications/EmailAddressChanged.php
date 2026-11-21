<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailAddressChanged extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $newEmail,
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
            ->subject('Alamat Email Akun RevStore Berubah')
            ->greeting('Halo '.$this->recipientName.',')
            ->line('Alamat email akun RevStore Anda telah berhasil diubah menjadi '.$this->newEmail.'.')
            ->line('Jika perubahan ini bukan dilakukan oleh Anda, segera hubungi tim RevStore dan amankan akun Anda.')
            ->withSymfonyMessage(fn ($message) => $message->getHeaders()->addTextHeader('X-Email-Delivery-Log-ID', $this->deliveryLogId));
    }
}
