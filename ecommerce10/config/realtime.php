<?php

return [
    'enabled' => (bool) env('REALTIME_ENABLED', false),
    'order_status' => (bool) env('REALTIME_ORDER_STATUS_ENABLED', false),
    'notifications' => (bool) env('REALTIME_NOTIFICATIONS_ENABLED', false),
];
