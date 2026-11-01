<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verify authentication: Must be authenticated customer (via OTP session) or authenticated Admin
$isCustomerLoggedIn = !empty($_SESSION['lmm_customer_id']);
$isAdminLoggedIn = !empty($_SESSION['admin_logged_in']);

if (!$isCustomerLoggedIn && !$isAdminLoggedIn) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required: Please verify your phone number via OTP or log in as admin to process installments.'
    ]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$accountNumber = strtoupper(trim($data['account_number'] ?? ''));
$amount = (float)($data['amount'] ?? 0);
$notes = sanitize($data['notes'] ?? 'Installment Payment Simulation');

if (empty($accountNumber) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid account number and amount are required.']);
    exit;
}

// If customer, verify ownership of this installment agreement
if ($isCustomerLoggedIn && !$isAdminLoggedIn) {
    $acc = getInstallmentAccountDetails($accountNumber);
    if (!$acc || (int)$acc['customer_id'] !== (int)$_SESSION['lmm_customer_id']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied: You can only make installments toward your own registered account.'
        ]);
        exit;
    }
}

// Generate realistic mock payment receipt (e.g. MP-INST8392)
$ref = 'MP-LMM' . strtoupper(bin2hex(random_bytes(3)));

$result = recordInstallmentPayment($accountNumber, $amount, $ref, 'mpesa_paybill', $notes);

if ($result['success']) {
    $updatedAcc = getInstallmentAccountDetails($accountNumber);
    echo json_encode([
        'success' => true,
        'message' => "Installment of " . formatMoney($amount) . " credited to {$accountNumber}!",
        'reference' => $ref,
        'account' => $updatedAcc
    ]);
} else {
    echo json_encode($result);
}
