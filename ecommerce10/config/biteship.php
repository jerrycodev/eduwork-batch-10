<?php

return [
    'base_url' => env('BITESHIP_BASE_URL', 'https://api.biteship.com'),
    'api_key' => env('BITESHIP_API_KEY'),
    'couriers' => env('BITESHIP_COURIERS', 'jne,sicepat,jnt,anteraja,pos'),
    'timeout' => (int) env('BITESHIP_TIMEOUT', 15),
    'warehouse' => [
        'postal_code' => env('BITESHIP_WAREHOUSE_POSTAL_CODE'),
    ],
];
