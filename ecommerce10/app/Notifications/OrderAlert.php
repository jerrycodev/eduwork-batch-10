<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class OrderAlert extends Notification
{
    use Queueable;

    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return config('realtime.enabled') && config('realtime.notifications')
            ? ['database', 'broadcast']
            : ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return $this->data;
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->data);
    }
}
