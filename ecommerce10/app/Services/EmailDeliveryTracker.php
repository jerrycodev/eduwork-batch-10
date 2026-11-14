<?php

namespace App\Services;

use App\Models\EmailDeliveryLog;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Throwable;

class EmailDeliveryTracker
{
    private const HEADER = 'X-Email-Delivery-Log-ID';

    public function sending(MessageSending $event): void
    {
        $log = $this->findFromMessage($event->message);

        if ($log) {
            $log->forceFill([
                'attempt_count' => $log->attempt_count + 1,
                'last_attempt_at' => now(),
            ])->save();
        }
    }

    public function sent(MessageSent $event): void
    {
        $log = $this->findFromMessage($event->message);

        if ($log) {
            $log->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
                'failed_at' => null,
                'last_error' => null,
            ])->save();
        }
    }

    public function failed(string $logId, Throwable $exception): void
    {
        EmailDeliveryLog::query()->whereKey($logId)->update([
            'status' => 'failed',
            'failed_at' => now(),
            'last_error' => mb_substr($exception->getMessage(), 0, 10000),
            'updated_at' => now(),
        ]);
    }

    private function findFromMessage(object $message): ?EmailDeliveryLog
    {
        $header = $message->getHeaders()->get(self::HEADER);
        $id = $header?->getBodyAsString();

        return filled($id) ? EmailDeliveryLog::query()->find($id) : null;
    }
}
