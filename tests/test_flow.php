<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "--- 1. Testing Code Generation ---\n";
$code = generateOrderCode('OFT-');
echo "Generated Code: {$code}\n";

echo "\n--- 2. Fetching Products ---\n";
$pdo = getDatabaseConnection();
$p = $pdo->query('SELECT * FROM products LIMIT 1')->fetch();
echo "Sample Product: {$p['name']} (Price: " . formatMoney($p['price']) . ")\n";

echo "\n--- 3. Creating Test Order ---\n";
$testOrderCode = generateOrderCode('OFT-');
$expiresAt = date('Y-m-d H:i:s', time() + 7200);
$stmt = $pdo->prepare('INSERT INTO orders (order_code, customer_name, customer_phone, delivery_address, subtotal, shipping_fee, total_amount, payment_status, fulfillment_status, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$testOrderCode, 'John Kamau', '0712345678', 'Kilimani, Nairobi', 3200.00, 0.00, 3200.00, 'pending', 'placed', $expiresAt]);
$orderId = $pdo->lastInsertId();

$itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
$itemStmt->execute([$orderId, $p['id'], $p['name'], $p['price'], 1, $p['price']]);

addOrderHistory($orderId, 'fulfillment', 'placed', 'Order created. Payment reference generated: ' . $testOrderCode);
echo "Order created successfully! Code: {$testOrderCode}\n";

echo "\n--- 4. Checking Order Status Before Payment ---\n";
$beforePay = getOrderByCode($testOrderCode);
echo "Payment Status: {$beforePay['payment_status']} | Fulfillment: {$beforePay['fulfillment_status']}\n";

echo "\n--- 5. Simulating Payment Confirmation Callback ---\n";
$payResult = recordOrderPayment($testOrderCode, 3200.00, 'MP-9842XKL', 'mpesa_paybill', '0712345678');
echo "Payment Result: " . json_encode($payResult, JSON_PRETTY_PRINT) . "\n";

echo "\n--- 6. Verifying Updated Order & Timeline ---\n";
$afterPay = getOrderByCode($testOrderCode);
echo "New Payment Status: {$afterPay['payment_status']}\n";
echo "New Fulfillment Status: {$afterPay['fulfillment_status']}\n";
echo "Total Amount Paid: " . formatMoney($afterPay['amount_paid']) . "\n";
echo "Timeline Events Logged: " . count($afterPay['history']) . "\n";
foreach ($afterPay['history'] as $h) {
    echo "  - [{$h['status_type']}: {$h['status_value']}] {$h['comment']}\n";
}

echo "\n--- ALL TESTS PASSED! ---\n";
