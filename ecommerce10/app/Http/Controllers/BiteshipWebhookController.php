<?php

namespace App\Http\Controllers;

use App\Events\OrderStatusUpdated;
use App\Models\BiteshipWebhookLog;
use App\Models\Order;
use App\Services\OrderEmailService;
use App\Services\OrderNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BiteshipWebhookController extends Controller
{
    private const SAFE_PAYLOAD_KEYS = [
        'event', 'reference_id', 'order_id', 'id', 'status', 'courier_status',
        'courier_company', 'courier_type', 'courier_waybill_id', 'waybill_id',
        'courier_tracking_id', 'tracking_id', 'price', 'currency', 'code',
        'success', 'data',
    ];

    /**
     * Menjalankan alur bisnis untuk menangani event atau permintaan yang diterima.
     */
    public function handle(Request $request): JsonResponse|Response
    {
        $payload = $request->all();
        $event = (string) data_get($payload, 'event', '');
        $referenceId = (string) (data_get($payload, 'reference_id') ?: data_get($payload, 'data.reference_id') ?: '');
        $externalId = (string) (data_get($payload, 'order_id') ?: data_get($payload, 'id') ?: data_get($payload, 'data.order_id') ?: data_get($payload, 'data.id') ?: '');
        $remoteStatus = (string) (data_get($payload, 'status') ?: data_get($payload, 'courier_status') ?: data_get($payload, 'data.status') ?: '');

        // Biteship validates a newly registered webhook with an empty JSON POST.
        // Acknowledge only that empty handshake; all actual event payloads remain signed.
        if ($request->isMethod('POST') && trim($request->getContent()) === '') {
            BiteshipWebhookLog::query()->create([
                'event' => 'webhook.handshake',
                'result' => 'handshake',
                'http_status' => 200,
                'message' => 'Empty webhook installation handshake acknowledged.',
                'received_at' => now(),
                'processed_at' => now(),
            ]);

            return response('ok', 200)->header('Content-Type', 'text/plain');
        }

        $signatureKey = (string) config('biteship.webhook.signature_key');
        $signatureSecret = (string) config('biteship.webhook.signature_secret');
        $signatureValid = $signatureKey !== '' && $signatureSecret !== ''
            && hash_equals($signatureSecret, (string) $request->header($signatureKey, ''));

        $log = BiteshipWebhookLog::query()->create([
            'event' => $event !== '' ? $event : 'unknown',
            'reference_id' => $referenceId ?: null,
            'biteship_order_id' => $externalId ?: null,
            'remote_status' => $remoteStatus ?: null,
            'signature_valid' => $signatureValid,
            'result' => 'received',
            'message' => 'Webhook received; validation pending.',
            'payload' => $this->safePayload($payload),
            'received_at' => now(),
        ]);

        if (! $signatureValid) {
            $log->update([
                'result' => 'rejected',
                'http_status' => 401,
                'message' => 'Signature header missing, invalid, or webhook secret is not configured.',
                'processed_at' => now(),
            ]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($referenceId === '' && $externalId === '') {
            $log->update([
                'result' => 'rejected',
                'http_status' => 422,
                'message' => 'Webhook payload does not contain reference_id or Biteship order ID.',
                'processed_at' => now(),
            ]);

            return response()->json(['message' => 'Missing order reference'], 422);
        }

        try {
            $query = Order::query();
            if ($referenceId !== '' && $externalId !== '') {
                $query->where(function ($builder) use ($referenceId, $externalId): void {
                    $builder->where('order_number', $referenceId)->orWhere('biteship_order_id', $externalId);
                });
            } elseif ($referenceId !== '') {
                $query->where('order_number', $referenceId);
            } else {
                $query->where('biteship_order_id', $externalId);
            }

            $order = $query->first();
            if (! $order) {
                $log->update([
                    'result' => 'rejected',
                    'http_status' => 404,
                    'message' => 'No RevStore order matches the supplied reference or Biteship order ID.',
                    'processed_at' => now(),
                ]);
                Log::notice('Biteship webhook references an unknown order.', [
                    'webhook_log_id' => $log->id,
                    'event' => $event,
                    'reference_id' => $referenceId,
                    'external_id' => $externalId,
                ]);

                return response()->json(['message' => 'Order not found'], 404);
            }

            $log->update(['order_id' => $order->id]);
            $before = $order->status;
            $beforeTrackingNumber = $order->tracking_number;

            $order = DB::transaction(function () use ($order, $payload, $externalId): Order {
                $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
                $status = (string) (data_get($payload, 'status') ?: data_get($payload, 'courier_status') ?: data_get($payload, 'data.status') ?: '');
                $waybill = data_get($payload, 'courier_waybill_id') ?: data_get($payload, 'waybill_id') ?: data_get($payload, 'data.courier_waybill_id') ?: data_get($payload, 'data.waybill_id');
                $trackingId = data_get($payload, 'courier_tracking_id') ?: data_get($payload, 'tracking_id') ?: data_get($payload, 'data.courier_tracking_id') ?: data_get($payload, 'data.tracking_id');
                $price = data_get($payload, 'price') ?? data_get($payload, 'data.price');
                $updates = ['biteship_last_synced_at' => now()];
                if ($externalId !== '' && ! $locked->biteship_order_id) {
                    $updates['biteship_order_id'] = $externalId;
                }
                if ($status !== '') {
                    $currentRank = $this->statusRank((string) $locked->biteship_status);
                    $incomingRank = $this->statusRank($status);
                    $specialStatus = in_array($status, [
                        'cancelled', 'on_hold', 'courier_not_found', 'rejected',
                        'return_in_transit', 'returned', 'disposed',
                    ], true);
                    $currentIsTerminal = in_array((string) $locked->biteship_status, [
                        'cancelled', 'courier_not_found', 'rejected',
                        'return_in_transit', 'returned', 'disposed',
                    ], true);
                    if ($specialStatus || (! $currentIsTerminal && $incomingRank >= $currentRank) || $status === 'delivered') {
                        $updates['biteship_status'] = $status;
                    } else {
                        $status = (string) $locked->biteship_status;
                    }
                    if ($currentIsTerminal && ! $specialStatus) {
                        $status = (string) $locked->biteship_status;
                        unset($updates['biteship_status']);
                    }
                }
                if ($waybill) {
                    $updates['biteship_waybill_id'] = (string) $waybill;
                    $updates['tracking_number'] = (string) $waybill;
                }
                if ($trackingId) {
                    $updates['biteship_tracking_id'] = (string) $trackingId;
                }
                if (is_numeric($price)) {
                    $updates['biteship_price'] = (int) $price;
                }

                // Preserve every Biteship status verbatim. Only normal fulfillment
                // milestones advance the RevStore order lifecycle; exceptions and
                // return-to-sender states remain shipment-level states.
                $nextStatus = match ($status) {
                    'picked', 'in_transit', 'dropping_off' => 'shipped',
                    'delivered' => 'delivered',
                    default => null,
                };
                if ($nextStatus === 'shipped' && in_array($locked->status, ['processing', 'shipped'], true)) {
                    $updates['status'] = 'shipped';
                    $updates['shipped_at'] = $locked->shipped_at ?? now();
                    if (! $locked->estimated_delivery_at) {
                        preg_match_all('/\\d+/', (string) $locked->shipping_etd, $matches);
                        $days = collect($matches[0] ?? [])->map(fn ($value) => (int) $value)->max();
                        $updates['estimated_delivery_at'] = $days ? now()->addDays($days) : null;
                    }
                } elseif ($nextStatus === 'delivered' && in_array($locked->status, ['processing', 'shipped', 'delivered'], true)) {
                    $updates['status'] = 'delivered';
                    $updates['delivered_at'] = $locked->delivered_at ?? now();
                    $updates['auto_complete_at'] = $locked->auto_complete_at ?? now()->addDays(3);
                }

                $locked->forceFill($updates)->save();

                return $locked->fresh();
            });

            $trackingChanged = $beforeTrackingNumber !== $order->tracking_number;
            if ($before !== $order->status || $trackingChanged) {
                app(OrderNotificationService::class)->statusUpdated($order, $trackingChanged);
                app(OrderEmailService::class)->statusUpdated($order, $trackingChanged);
                if (config('realtime.enabled') && config('realtime.order_status')) {
                    OrderStatusUpdated::dispatch((string) $order->id, [
                        'id' => (string) $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'tracking_number' => $order->tracking_number,
                        'payment_status' => $order->payment_status,
                        'shipped_at' => $order->shipped_at?->toIso8601String(),
                        'delivered_at' => $order->delivered_at?->toIso8601String(),
                        'updated_at' => $order->updated_at?->toIso8601String(),
                    ]);
                }
            }

            $log->update([
                'result' => 'processed',
                'http_status' => 200,
                'order_id' => $order->id,
                'message' => 'Webhook berhasil diproses. Status RevStore: '.$order->status.'.',
                'processed_at' => now(),
            ]);

            return response()->json(['received' => true]);
        } catch (Throwable $exception) {
            report($exception);
            $log->update([
                'result' => 'failed',
                'http_status' => 500,
                'message' => mb_substr($exception->getMessage(), 0, 4000),
                'processed_at' => now(),
            ]);
            Log::error('Biteship webhook processing failed.', [
                'webhook_log_id' => $log->id,
                'order_id' => $log->order_id,
                'event' => $event,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }

    /** Keep only non-sensitive integration fields; omit contact/address/customer data. */
    private function safePayload(array $payload): array
    {
        $safe = [];
        foreach ($payload as $key => $value) {
            if (! in_array((string) $key, self::SAFE_PAYLOAD_KEYS, true)) {
                continue;
            }
            if ($key === 'data' && is_array($value)) {
                $safe['data'] = $this->safePayload($value);
            } elseif (is_scalar($value) || $value === null) {
                $safe[$key] = $value;
            }
        }

        return $safe;
    }

    private function statusRank(string $status): int
    {
        return match ($status) {
            'confirmed', 'scheduled' => 1,
            'allocated' => 2,
            'picking_up' => 3,
            'picked' => 4,
            'in_transit' => 5,
            'dropping_off' => 6,
            'delivered', 'cancelled', 'rejected', 'courier_not_found',
            'return_in_transit', 'returned', 'disposed' => 7,
            default => 0,
        };
    }
}
