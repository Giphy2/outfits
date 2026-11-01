<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Generate a human-friendly, unambiguous order code.
 * Excludes easily confused characters: 0, O, 1, I, L.
 * Example result: OFT-7842K
 */
function generateOrderCode($prefix = 'OFT-') {
    $chars = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    $random = '';
    $len = 5;
    for ($i = 0; $i < $len; $i++) {
        $random .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $prefix . $random;
}

/**
 * Format monetary amount with currency prefix.
 */
function formatMoney($amount) {
    return CURRENCY_SYMBOL . number_format((float)$amount, 2);
}

/**
 * Sanitize strings for safe output and storage.
 */
function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Get an order by code with items, payments, and status timeline.
 */
function getOrderByCode($code) {
    $pdo = getDatabaseConnection();
    $code = strtoupper(trim($code));

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? LIMIT 1");
    $stmt->execute([$code]);
    $order = $stmt->fetch();

    if (!$order) {
        return null;
    }

    // Get items
    $itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemsStmt->execute([$order['id']]);
    $order['items'] = $itemsStmt->fetchAll();

    // Get payments
    $paymentsStmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC");
    $paymentsStmt->execute([$order['id']]);
    $order['payments'] = $paymentsStmt->fetchAll();

    // Get timeline
    $historyStmt = $pdo->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC");
    $historyStmt->execute([$order['id']]);
    $order['history'] = $historyStmt->fetchAll();

    return $order;
}

/**
 * Record an order timeline event.
 */
function addOrderHistory($orderId, $statusType, $statusValue, $comment = '') {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status_type, status_value, comment) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$orderId, $statusType, $statusValue, $comment]);
}

/**
 * Process a payment against an order code.
 * Handles exact, under, and over payments.
 */
function recordOrderPayment($orderCode, $amount, $paymentRef, $method = 'mpesa_paybill', $phone = '', $payload = '') {
    $pdo = getDatabaseConnection();
    $order = getOrderByCode($orderCode);

    if (!$order) {
        return [
            'success' => false,
            'message' => 'Order not found for code: ' . $orderCode
        ];
    }

    $amount = (float)$amount;
    $newTotalPaid = (float)$order['amount_paid'] + $amount;
    $totalAmount = (float)$order['total_amount'];

    // Determine new payment status
    if ($newTotalPaid >= $totalAmount) {
        $paymentStatus = 'paid';
        $fulfillmentStatus = ($order['fulfillment_status'] === 'placed') ? 'confirmed' : $order['fulfillment_status'];
    } elseif ($newTotalPaid > 0) {
        $paymentStatus = 'partially_paid';
        $fulfillmentStatus = $order['fulfillment_status'];
    } else {
        $paymentStatus = 'pending';
        $fulfillmentStatus = $order['fulfillment_status'];
    }

    // Record the payment
    $stmt = $pdo->prepare("INSERT INTO payments (order_id, payment_method, payment_reference, phone_number, amount, status, raw_payload) VALUES (?, ?, ?, ?, ?, 'completed', ?)");
    $stmt->execute([
        $order['id'],
        $method,
        $paymentRef,
        $phone ?: $order['customer_phone'],
        $amount,
        $payload ? (is_string($payload) ? $payload : json_encode($payload)) : null
    ]);

    // Update order
    $updateStmt = $pdo->prepare("UPDATE orders SET amount_paid = ?, payment_status = ?, fulfillment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $updateStmt->execute([$newTotalPaid, $paymentStatus, $fulfillmentStatus, $order['id']]);

    // Add timeline entries
    addOrderHistory(
        $order['id'],
        'payment',
        $paymentStatus,
        "Received payment of " . formatMoney($amount) . " via " . strtoupper($method) . " (Ref: {$paymentRef}). Total paid: " . formatMoney($newTotalPaid) . " of " . formatMoney($totalAmount)
    );

    if ($fulfillmentStatus === 'confirmed' && $order['fulfillment_status'] === 'placed') {
        addOrderHistory(
            $order['id'],
            'fulfillment',
            'confirmed',
            'Payment verified. Order confirmed and queued for fulfillment.'
        );
    }

    return [
        'success' => true,
        'order_code' => $orderCode,
        'payment_status' => $paymentStatus,
        'fulfillment_status' => $fulfillmentStatus,
        'amount_paid' => $newTotalPaid,
        'balance_remaining' => max(0, $totalAmount - $newTotalPaid)
    ];
}

/**
 * Return CSS badge class for payment status
 */
function getPaymentStatusBadge($status) {
    $map = [
        'paid' => ['label' => 'PAID', 'class' => 'badge-success'],
        'pending' => ['label' => 'PENDING PAYMENT', 'class' => 'badge-warning'],
        'partially_paid' => ['label' => 'PARTIAL PAYMENT', 'class' => 'badge-info'],
        'failed' => ['label' => 'FAILED', 'class' => 'badge-danger'],
        'expired' => ['label' => 'EXPIRED', 'class' => 'badge-muted'],
        'refunded' => ['label' => 'REFUNDED', 'class' => 'badge-secondary']
    ];
    $badge = $map[$status] ?? ['label' => strtoupper($status), 'class' => 'badge-secondary'];
    return "<span class=\"badge {$badge['class']}\">{$badge['label']}</span>";
}

/**
 * Return CSS badge class for fulfillment status
 */
function getFulfillmentStatusBadge($status) {
    $map = [
        'placed' => ['label' => 'ORDER PLACED', 'class' => 'badge-warning'],
        'confirmed' => ['label' => 'CONFIRMED', 'class' => 'badge-primary'],
        'processing' => ['label' => 'PACKING / PROCESSING', 'class' => 'badge-info'],
        'dispatched' => ['label' => 'DISPATCHED FOR DELIVERY', 'class' => 'badge-accent'],
        'delivered' => ['label' => 'DELIVERED', 'class' => 'badge-success'],
        'cancelled' => ['label' => 'CANCELLED', 'class' => 'badge-danger']
    ];
    $badge = $map[$status] ?? ['label' => strtoupper($status), 'class' => 'badge-secondary'];
    return "<span class=\"badge {$badge['class']}\">{$badge['label']}</span>";
}

