<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$orderId = (int)($data['order_id'] ?? 0);
$newFulfillment = sanitize($data['fulfillment_status'] ?? '');
$comment = sanitize($data['comment'] ?? 'Status updated by store manager');

$allowed = ['placed', 'confirmed', 'processing', 'dispatched', 'delivered', 'cancelled'];
if (!$orderId || !in_array($newFulfillment, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$pdo = getDatabaseConnection();
$stmt = $pdo->prepare("UPDATE orders SET fulfillment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
$success = $stmt->execute([$newFulfillment, $orderId]);

if ($success) {
    addOrderHistory($orderId, 'fulfillment', $newFulfillment, $comment);
    echo json_encode(['success' => true, 'message' => 'Fulfillment status updated to ' . strtoupper($newFulfillment)]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update order']);
}

