<?php
// =========================================================
// Real Payment Gateway Webhook Endpoint
// Compatible with:
// - Safaricom M-PESA Daraja C2B Confirmation
// - Safaricom M-PESA STK Push Callback
// - Generic JSON Webhooks (Bank Transfer / Aggregator)
// =========================================================

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$rawBody = file_get_contents('php://input');

// Log incoming webhook for audit and debugging
$logFile = __DIR__ . '/../storage/webhook.log';
$logEntry = "[" . date('Y-m-d H:i:s') . "] IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . "\nPayload: " . $rawBody . "\n\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

$payload = json_decode($rawBody, true);

if (!$payload) {
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid JSON payload']);
    exit;
}

$orderCode = '';
$amount = 0.00;
$receiptNumber = '';
$phoneNumber = '';
$method = 'mpesa';

// 1. Check for standard M-PESA C2B confirmation
if (isset($payload['BillRefNumber'])) {
    $orderCode = strtoupper(trim($payload['BillRefNumber']));
    $amount = (float)($payload['TransAmount'] ?? 0);
    $receiptNumber = $payload['TransID'] ?? ('MP-' . time());
    $phoneNumber = $payload['MSISDN'] ?? '';
    $method = 'mpesa_c2b';
}
// 2. Check for M-PESA STK Push callback
elseif (isset($payload['Body']['stkCallback'])) {
    $stk = $payload['Body']['stkCallback'];
    $resultCode = $stk['ResultCode'] ?? -1;

    if ($resultCode === 0 && isset($stk['CallbackMetadata']['Item'])) {
        foreach ($stk['CallbackMetadata']['Item'] as $item) {
            if ($item['Name'] === 'Amount') $amount = (float)$item['Value'];
            if ($item['Name'] === 'MpesaReceiptNumber') $receiptNumber = $item['Value'];
            if ($item['Name'] === 'PhoneNumber') $phoneNumber = (string)$item['Value'];
        }
        $method = 'mpesa_stk';
        // Note: For STK push, order code is matched via MerchantRequestID/CheckoutRequestID or account ref
        $orderCode = strtoupper(trim($_GET['ref'] ?? ''));
    } else {
        // Failed transaction
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Failure acknowledged']);
        exit;
    }
}
// 3. Generic custom webhook
elseif (isset($payload['order_code'])) {
    $orderCode = strtoupper(trim($payload['order_code']));
    $amount = (float)($payload['amount'] ?? 0);
    $receiptNumber = $payload['reference'] ?? ('REF-' . time());
    $phoneNumber = $payload['phone'] ?? '';
    $method = $payload['method'] ?? 'bank_transfer';
}

if (!empty($orderCode) && $amount > 0) {
    $result = recordOrderPayment($orderCode, $amount, $receiptNumber, $method, $phoneNumber, $payload);
    if ($result['success']) {
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Payment accepted and reconciled successfully']);
        exit;
    }
}

echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Processed']);

