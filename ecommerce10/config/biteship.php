<?php

return [
    'base_url' => env('BITESHIP_BASE_URL', 'https://api.biteship.com'),
    'api_key' => env('BITESHIP_API_KEY'),
    'couriers' => env('BITESHIP_COURIERS', 'jne,sicepat,jnt,anteraja,pos'),
    'timeout' => (int) env('BITESHIP_TIMEOUT', 15),
    'rates_cache_ttl' => (int) env('BITESHIP_RATES_CACHE_TTL', 300),
    'tracking_environment' => env('BITESHIP_TRACKING_ENVIRONMENT', 'development'),
    'warehouse' => [
        'name' => env('BITESHIP_WAREHOUSE_NAME'),
        'phone' => env('BITESHIP_WAREHOUSE_PHONE'),
        'email' => env('BITESHIP_WAREHOUSE_EMAIL'),
        'address' => env('BITESHIP_WAREHOUSE_ADDRESS'),
        'postal_code' => env('BITESHIP_WAREHOUSE_POSTAL_CODE'),
        'note' => env('BITESHIP_WAREHOUSE_NOTE'),
    ],
    'webhook' => [
        'signature_key' => env('BITESHIP_WEBHOOK_SIGNATURE_KEY'),
        'signature_secret' => env('BITESHIP_WEBHOOK_SIGNATURE_SECRET'),
    ],
];
