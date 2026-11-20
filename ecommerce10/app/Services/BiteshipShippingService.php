<?php

namespace App\Services;

use App\Models\Order;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class BiteshipShippingService
{
    /** Retrieve the public tracking-page data for a Biteship tracking ID. */
    public function getTrackingPage(string $trackingId): array
    {
        if (blank($trackingId)) {
            throw new RuntimeException('Biteship tracking ID tidak tersedia.');
        }

        // Match the headers used by track.biteship.com for its public tracking-page request.
        // This endpoint is public; unlike shipment-management endpoints it does not use the API key.
        $response = Http::withHeaders([
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'en-ID,en-GB;q=0.9,en-US;q=0.8,en;q=0.7',
            'Origin' => 'https://track.biteship.com',
            'Priority' => 'u=1, i',
            'Referer' => 'https://track.biteship.com/',
            'Sec-CH-UA' => '"Not-A.Brand";v="99", "Google Chrome";v="157", "Chromium";v="157"',
            'Sec-CH-UA-Mobile' => '?0',
            'Sec-CH-UA-Platform' => '"Linux"',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-site',
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',
        ])
            ->timeout((int) config('biteship.timeout', 15))
            ->get(rtrim((string) config('biteship.base_url'), '/').'/v1/trackings/tracking_pages/'.rawurlencode($trackingId), [
                'environment' => config('biteship.tracking_environment', 'development'),
            ]);

        if ($response->failed()) {
            Log::warning('Biteship tracking page request failed.', [
                'tracking_id' => $trackingId,
                'http_status' => $response->status(),
            ]);

            throw new RuntimeException('Data tracking Biteship tidak dapat dimuat (HTTP '.$response->status().').');
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Respons tracking Biteship tidak valid.');
        }

        return $data;
    }

    /** @return array<int, array<string, mixed>> */
    public function getRates(iterable $cartItems, UserAddress $address): array
    {
        $apiKey = (string) config('biteship.api_key');
        $originPostalCode = (string) config('biteship.warehouse.postal_code');

        if ($apiKey === '' || $originPostalCode === '') {
            throw new RuntimeException('Integrasi ongkir belum dikonfigurasi. Hubungi administrator toko.');
        }
        if (! preg_match('/^\\d{5}$/', $originPostalCode)) {
            throw new RuntimeException('Kode pos warehouse harus terdiri dari 5 digit.');
        }
        if (! preg_match('/^\\d{5}$/', (string) $address->postal_code)) {
            throw new RuntimeException('Alamat tujuan harus memiliki kode pos Indonesia 5 digit. Perbarui alamat Anda.');
        }

        $items = [];
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            if (! $product) {
                continue;
            }
            $items[] = array_filter([
                'name' => Str::limit($product->name.($cartItem->variant?->name ? ' - '.$cartItem->variant->name : ''), 100, ''),
                'value' => (int) ($cartItem->variant?->price ?? $product->price),
                'quantity' => (int) $cartItem->quantity,
                'weight' => max(1, (int) ($cartItem->variant?->weight_gram ?? $product->weight_gram ?? 1000)),
                'length' => $cartItem->variant?->length_cm ?? $product->length_cm,
                'width' => $cartItem->variant?->width_cm ?? $product->width_cm,
                'height' => $cartItem->variant?->height_cm ?? $product->height_cm,
            ], fn ($value) => $value !== null);
        }
        if ($items === []) {
            throw new RuntimeException('Tidak ada produk yang dapat dihitung ongkirnya.');
        }

        $payload = [
            'origin_postal_code' => $originPostalCode,
            'destination_postal_code' => (string) $address->postal_code,
            'couriers' => (string) config('biteship.couriers'),
            'items' => $items,
        ];

        // Rates are reusable only when every input that affects pricing is identical.
        $cacheKey = 'biteship:rates:'.hash('sha256', json_encode([
            'base_url' => rtrim((string) config('biteship.base_url'), '/'),
            'payload' => $payload,
        ], JSON_THROW_ON_ERROR));

        return Cache::remember(
            $cacheKey,
            now()->addSeconds(max(1, (int) config('biteship.rates_cache_ttl', 300))),
            function () use ($payload): array {
                $response = $this->client()->post('/v1/rates/couriers', $payload);
                if ($response->failed()) {
                    Log::warning('Biteship rates request failed.', ['status' => $response->status(), 'response' => $response->json()]);
                    throw new RuntimeException('Tarif pengiriman belum tersedia. Periksa alamat tujuan atau coba beberapa saat lagi.');
                }

                $pricing = $response->json('pricing', []);
                if (! is_array($pricing)) {
                    throw new RuntimeException('Respons tarif pengiriman tidak valid.');
                }

                return collect($pricing)
                    ->filter(fn ($rate) => is_array($rate) && isset($rate['courier_code'], $rate['courier_service_code'], $rate['price']) && is_numeric($rate['price']) && (int) $rate['price'] >= 0)
                    ->map(fn ($rate) => [
                        'courier_code' => (string) $rate['courier_code'],
                        'courier_name' => (string) ($rate['courier_name'] ?? $rate['company'] ?? strtoupper($rate['courier_code'])),
                        'service_code' => (string) $rate['courier_service_code'],
                        'service_name' => (string) ($rate['courier_service_name'] ?? $rate['courier_service_code']),
                        'price' => (int) $rate['price'],
                        'duration' => (string) ($rate['duration'] ?? $rate['shipment_duration_range'] ?? ''),
                    ])
                    ->unique(fn ($rate) => $rate['courier_code'].':'.$rate['service_code'])
                    ->sortBy('price')->values()->all();
            },
        );
    }

    /** Create one Biteship shipment for an already-paid order, using the order number as an idempotent reference. */
    public function createShipment(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $locked = Order::query()->with(['orderItems.product', 'orderItems.variant'])->lockForUpdate()->findOrFail($order->id);
            if ($locked->biteship_order_id) {
                return $locked;
            }
            if ($locked->status !== 'processing' || $locked->payment_status !== 'paid') {
                throw new RuntimeException('Shipment Biteship hanya dapat dibuat untuk pesanan lunas berstatus Processing.');
            }

            $warehouse = config('biteship.warehouse', []);
            foreach (['name', 'phone', 'address', 'postal_code'] as $required) {
                if (blank($warehouse[$required] ?? null)) {
                    throw new RuntimeException('Data warehouse Biteship belum lengkap. Lengkapi BITESHIP_WAREHOUSE_'.strtoupper($required).' pada konfigurasi server.');
                }
            }
            if (blank($locked->user_phone) || blank($locked->recipient_name) || blank($locked->address) || blank($locked->zip)) {
                throw new RuntimeException('Data penerima pesanan belum lengkap untuk membuat pengiriman.');
            }

            $items = $locked->orderItems->map(function ($item): array {
                return array_filter([
                    'name' => Str::limit(trim($item->product_name.' '.($item->product_variant_name && $item->product_variant_name !== 'Default' ? $item->product_variant_name : '')), 100, ''),
                    'value' => (int) $item->price,
                    'quantity' => (int) $item->quantity,
                    'weight' => max(1, (int) ($item->variant?->weight_gram ?? $item->product?->weight_gram ?? 1000)),
                    'length' => $item->variant?->length_cm ?? $item->product?->length_cm,
                    'width' => $item->variant?->width_cm ?? $item->product?->width_cm,
                    'height' => $item->variant?->height_cm ?? $item->product?->height_cm,
                ], fn ($value) => $value !== null);
            })->all();
            if ($items === []) {
                throw new RuntimeException('Detail produk pesanan tidak tersedia untuk pengiriman.');
            }

            $payload = [
                'reference_id' => $locked->order_number,
                'origin_contact_name' => (string) $warehouse['name'],
                'origin_contact_phone' => (string) $warehouse['phone'],
                'origin_contact_email' => $warehouse['email'] ?: null,
                'origin_address' => (string) $warehouse['address'],
                'origin_note' => $warehouse['note'] ?: null,
                'origin_postal_code' => (int) $warehouse['postal_code'],
                'destination_contact_name' => (string) $locked->recipient_name,
                'destination_contact_phone' => (string) $locked->user_phone,
                'destination_contact_email' => (string) $locked->user_email,
                'destination_address' => trim($locked->address.', '.$locked->city.', '.$locked->state),
                'destination_postal_code' => (int) $locked->zip,
                'courier_company' => (string) $locked->shipping_courier_code,
                'courier_type' => (string) $locked->shipping_service_code,
                'delivery_type' => 'now',
                'order_note' => 'RevStore '.$locked->order_number,
                'items' => $items,
            ];
            $response = $this->client()->post('/v1/orders', $payload);
            $data = $response->json() ?? [];

            // If a previous request timed out after Biteship accepted it, its unique reference_id
            // prevents a second shipment. Biteship returns the existing IDs in this error response.
            if ($response->failed() && data_get($data, 'details.reference_id') === $locked->order_number && filled(data_get($data, 'details.order_id'))) {
                $data = [
                    'id' => data_get($data, 'details.order_id'),
                    'waybill_id' => data_get($data, 'details.waybill_id'),
                    'status' => 'confirmed',
                ];
            } elseif ($response->failed()) {
                Log::error('Biteship create order failed.', ['order_id' => $locked->id, 'status' => $response->status(), 'response' => $data]);
                $apiMessage = data_get($data, 'message') ?: data_get($data, 'error') ?: data_get($data, 'error_message');
                $details = data_get($data, 'details');
                $detailMessage = is_string($details) ? $details : (is_array($details) ? collect($details)->map(fn ($value, $key) => is_scalar($value) ? $key.': '.$value : null)->filter()->implode('; ') : '');
                $message = trim(collect([
                    'Biteship API HTTP '.$response->status(),
                    is_string($apiMessage) ? $apiMessage : null,
                    $detailMessage !== '' ? $detailMessage : null,
                    data_get($data, 'code') ? 'Kode: '.data_get($data, 'code') : null,
                ])->filter()->implode(' — '));
                throw new RuntimeException($message !== '' ? $message : 'Biteship gagal membuat pengiriman dan tidak mengembalikan detail error. Periksa dashboard Biteship.');
            }

            $externalId = (string) ($data['id'] ?? '');
            if ($externalId === '') {
                throw new RuntimeException('Biteship tidak mengembalikan ID pengiriman yang valid.');
            }
            $waybill = data_get($data, 'courier_waybill_id') ?: data_get($data, 'waybill_id');
            $trackingId = data_get($data, 'courier_tracking_id') ?: data_get($data, 'tracking_id');
            $locked->forceFill([
                'biteship_order_id' => $externalId,
                'biteship_tracking_id' => $trackingId,
                'biteship_waybill_id' => $waybill,
                'tracking_number' => $waybill ?: $locked->tracking_number,
                'biteship_status' => data_get($data, 'status', 'confirmed'),
                'biteship_price' => is_numeric(data_get($data, 'price')) ? (int) data_get($data, 'price') : null,
                'biteship_order_created_at' => now(),
                'biteship_last_synced_at' => now(),
            ])->save();

            return $locked->fresh();
        }, 3);
    }

    /**
     * Cancel a shipment only while Biteship still permits cancellation.
     * The caller must complete the local cancellation only after this succeeds.
     */
    public function cancelShipment(Order $order, string $reason): Order
    {
        $locked = $order->fresh();

        if (! $locked?->biteship_order_id) {
            return $locked ?? $order;
        }

        $cancellableStatuses = ['confirmed', 'scheduled', 'allocated', 'picking_up'];
        if (! in_array($locked->biteship_status, $cancellableStatuses, true)) {
            throw new RuntimeException(
                'Shipment tidak dapat dibatalkan melalui Biteship pada status '.($locked->biteship_status ?: 'tidak diketahui').'. '
                .'Biteship hanya mengizinkan pembatalan sebelum paket dijemput kurir.',
            );
        }

        $reasonCode = match ($reason) {
            'wrong_address' => 'change_address',
            'wrong_order', 'duplicate' => 'change_item',
            'changed_mind' => 'others',
            default => 'others',
        };

        $response = $this->client()->post(
            '/v1/orders/'.rawurlencode($locked->biteship_order_id).'/cancel',
            [
                'cancellation_reason_code' => $reasonCode,
                'cancellation_reason' => Str::limit($reason, 500, ''),
            ],
        );

        $data = $response->json() ?? [];
        if ($response->failed() || data_get($data, 'success') === false) {
            Log::warning('Biteship shipment cancellation failed.', [
                'order_id' => $locked->id,
                'biteship_order_id' => $locked->biteship_order_id,
                'http_status' => $response->status(),
                'response' => $data,
            ]);
            throw new RuntimeException(
                (string) (data_get($data, 'error') ?: data_get($data, 'message') ?: 'Biteship menolak pembatalan shipment.'),
            );
        }

        $locked->forceFill([
            'biteship_status' => 'cancelled',
            'biteship_last_synced_at' => now(),
        ])->save();

        return $locked->fresh();
    }

    private function client()
    {
        $apiKey = (string) config('biteship.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('BITESHIP_API_KEY belum dikonfigurasi.');
        }

        return Http::baseUrl(rtrim((string) config('biteship.base_url'), '/'))
            ->withHeaders(['Authorization' => $apiKey, 'Accept' => 'application/json'])
            ->asJson()->timeout((int) config('biteship.timeout', 15));
    }
}
