<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

$orderCode = strtoupper(trim($data['order_code'] ?? ''));
$amount = (float)($data['amount'] ?? 0);
$method = sanitize($data['payment_method'] ?? 'mpesa_paybill');
$phone = sanitize($data['phone'] ?? '');

if (empty($orderCode) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Order code and valid amount are required.']);
    exit;
}

// Generate realistic mock payment receipt number (e.g. M-Pesa format: QHJ93K81P)
$chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
$mockRef = 'MP-';
for ($i = 0; $i < 8; $i++) {
    $mockRef .= $chars[random_int(0, strlen($chars) - 1)];
}

$mockPayload = [
    'TransactionType' => 'Pay Bill',
    'TransID' => $mockRef,
    'TransTime' => date('YmdHis'),
    'TransAmount' => $amount,
    'BusinessShortCode' => PAYMENT_BUSINESS_NUMBER,
    'BillRefNumber' => $orderCode,
    'MSISDN' => $phone ?: '254712345678',
    'Simulated' => true
];

$result = recordOrderPayment($orderCode, $amount, $mockRef, $method, $phone, $mockPayload);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => 'Payment simulated & verified successfully!',
        'reference' => $mockRef,
        'order' => $result
    ]);
} else {
    echo json_encode($result);
}

