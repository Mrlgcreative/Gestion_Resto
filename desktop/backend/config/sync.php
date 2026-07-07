<?php

return [
    'gateway_url' => env('GATEWAY_URL', 'http://localhost:8001'),
    'device_id' => env('DEVICE_ID', 'desktop-1'),
    'sync_interval' => env('SYNC_INTERVAL', 30), // seconds
    'tables' => [
        'orders', 'order_items', 'payments', 'products',
        'categories', 'ingredients', 'stock_movements',
        'servers', 'cashier_sessions', 'kitchen_sessions',
    ],
];
