<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$code = strtoupper(trim($_GET['code'] ?? ''));

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Order code is required.']);
    exit;
}

$order = getOrderByCode($code);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found for code: ' . $code]);
    exit;
}

// Check expiration if still pending
if ($order['payment_status'] === 'pending' && strtotime($order['expires_at']) < time()) {
    $pdo = getDatabaseConnection();
    $updateStmt = $pdo->prepare("UPDATE orders SET payment_status = 'expired' WHERE id = ?");
    $updateStmt->execute([$order['id']]);
    $order['payment_status'] = 'expired';
}

echo json_encode([
    'success' => true,
    'order' => [
        'order_code' => $order['order_code'],
        'customer_name' => $order['customer_name'],
        'customer_phone' => $order['customer_phone'],
        'delivery_address' => $order['delivery_address'],
        'city' => $order['city'],
        'total_amount' => (float)$order['total_amount'],
        'amount_paid' => (float)$order['amount_paid'],
        'payment_status' => $order['payment_status'],
        'fulfillment_status' => $order['fulfillment_status'],
        'payment_status_badge' => getPaymentStatusBadge($order['payment_status']),
        'fulfillment_status_badge' => getFulfillmentStatusBadge($order['fulfillment_status']),
        'expires_at' => $order['expires_at'],
        'created_at' => $order['created_at'],
        'items' => $order['items'],
        'payments' => $order['payments'],
        'history' => $order['history']
    ]
]);

