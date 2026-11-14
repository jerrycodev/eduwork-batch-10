<?php

namespace App\Services;

use App\Models\UserAddress;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class BiteshipShippingService
{
    /** @return array<int, array<string, mixed>> */
    public function getRates(iterable $cartItems, UserAddress $address): array
    {
        $apiKey = (string) config('biteship.api_key');
        $originPostalCode = (string) config('biteship.warehouse.postal_code');

        if ($apiKey === '' || $originPostalCode === '') {
            throw new RuntimeException('Integrasi ongkir belum dikonfigurasi. Hubungi administrator toko.');
        }

        if (! preg_match('/^\d{5}$/', $originPostalCode)) {
            throw new RuntimeException('Kode pos warehouse harus terdiri dari 5 digit.');
        }

        if (! preg_match('/^\d{5}$/', (string) $address->postal_code)) {
            throw new RuntimeException('Alamat tujuan harus memiliki kode pos Indonesia 5 digit. Perbarui alamat Anda.');
        }

        $items = [];
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            if (! $product) {
                continue;
            }

            $items[] = [
                'name' => Str::limit($product->name, 100, ''),
                'value' => (int) $product->price,
                'quantity' => (int) $cartItem->quantity,
                'weight' => max(1, (int) ($product->weight_gram ?? 1000)),
            ];
        }

        if ($items === []) {
            throw new RuntimeException('Tidak ada produk yang dapat dihitung ongkirnya.');
        }

        $response = Http::baseUrl(rtrim((string) config('biteship.base_url'), '/'))
            ->withHeaders(['Authorization' => $apiKey, 'Accept' => 'application/json'])
            ->asJson()
            ->timeout((int) config('biteship.timeout', 15))
            ->post('/v1/rates/couriers', [
                'origin_postal_code' => $originPostalCode,
                'destination_postal_code' => $address->postal_code,
                'couriers' => config('biteship.couriers'),
                'items' => $items,
            ]);

        if ($response->failed()) {
            Log::warning('Biteship rates request failed.', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            throw new RuntimeException('Tarif pengiriman belum tersedia. Periksa alamat tujuan atau coba beberapa saat lagi.');
        }

        $pricing = $response->json('pricing', []);
        if (! is_array($pricing)) {
            throw new RuntimeException('Respons tarif pengiriman tidak valid.');
        }

        return collect($pricing)
            ->filter(fn ($rate) => is_array($rate)
                && isset($rate['courier_code'], $rate['courier_service_code'], $rate['price'])
                && is_numeric($rate['price'])
                && (int) $rate['price'] >= 0)
            ->map(fn ($rate) => [
                'courier_code' => (string) $rate['courier_code'],
                'courier_name' => (string) ($rate['courier_name'] ?? $rate['company'] ?? strtoupper($rate['courier_code'])),
                'service_code' => (string) $rate['courier_service_code'],
                'service_name' => (string) ($rate['courier_service_name'] ?? $rate['courier_service_code']),
                'price' => (int) $rate['price'],
                'duration' => (string) ($rate['duration'] ?? $rate['shipment_duration_range'] ?? ''),
            ])
            ->unique(fn ($rate) => $rate['courier_code'].':'.$rate['service_code'])
            ->sortBy('price')
            ->values()
            ->all();
    }
}
