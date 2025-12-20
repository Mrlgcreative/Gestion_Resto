<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = \App\Models\Order::with('currencyRelation')->latest()->first();

echo "=== Dernière commande ===" . PHP_EOL;
echo "Order ID: " . $order->id . PHP_EOL;
echo "currency (string field): " . $order->currency . PHP_EOL;
echo "currency_id: " . $order->currency_id . PHP_EOL;
echo "currencyRelation->code: " . ($order->currencyRelation ? $order->currencyRelation->code : 'null') . PHP_EOL;
echo "total_amount: " . $order->total_amount . PHP_EOL;

// Simuler la sérialisation Inertia
$orderArray = $order->toArray();
echo PHP_EOL . "=== Sérialisation (toArray) ===" . PHP_EOL;
echo "currency_relation dans array: " . (isset($orderArray['currency_relation']) ? json_encode($orderArray['currency_relation']) : 'NOT FOUND') . PHP_EOL;
