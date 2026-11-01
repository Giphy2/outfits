<?php
// =========================================================
// Real Payment Gateway Webhook Endpoint
// Compatible with:
// - Safaricom M-PESA Daraja C2B Confirmation
// - Safaricom M-PESA STK Push Callback
// - Generic JSON Webhooks (Bank Transfer / Aggregator)
// Handles both:
// Model 1: Pay In Full (Order Codes: OFT-XXXX)
// Model 2: Lipa Mdogo Mdogo (Installment Accounts: LMM-XXXX)
// =========================================================

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verify Webhook Authenticity (Secret token header or query parameter)
$incomingSecret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? ($_GET['secret'] ?? '');
if (defined('WEBHOOK_SECRET_KEY') && !empty(WEBHOOK_SECRET_KEY) && !empty($incomingSecret)) {
    if (!hash_equals(WEBHOOK_SECRET_KEY, $incomingSecret)) {
        http_response_code(403);
        echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Forbidden: Invalid webhook secret token']);
        exit;
    }
}

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

$refCode = '';
$amount = 0.00;
$receiptNumber = '';
$phoneNumber = '';
$method = 'mpesa';

// 1. Check for standard M-PESA C2B confirmation
if (isset($payload['BillRefNumber'])) {
    $refCode = strtoupper(trim($payload['BillRefNumber']));
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
        $refCode = strtoupper(trim($_GET['ref'] ?? ''));
    } else {
        // Failed transaction
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Failure acknowledged']);
        exit;
    }
}
// 3. Generic custom webhook
elseif (isset($payload['order_code']) || isset($payload['account_number'])) {
    $refCode = strtoupper(trim($payload['order_code'] ?? $payload['account_number']));
    $amount = (float)($payload['amount'] ?? 0);
    $receiptNumber = $payload['reference'] ?? ('REF-' . time());
    $phoneNumber = $payload['phone'] ?? '';
    $method = $payload['method'] ?? 'bank_transfer';
}

if (!empty($refCode) && $amount > 0) {
    // Check if Lipa Mdogo Mdogo installment payment
    if (strpos($refCode, 'LMM-') === 0) {
        $result = recordInstallmentPayment($refCode, $amount, $receiptNumber, $method, 'Payment via M-PESA Webhook');
        if ($result['success']) {
            echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Installment credited successfully']);
            exit;
        }
    } else {
        // Pay In Full Order
        $result = recordOrderPayment($refCode, $amount, $receiptNumber, $method, $phoneNumber, $payload);
        if ($result['success']) {
            echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Payment accepted and reconciled successfully']);
            exit;
        }
    }
}

echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Processed']);
