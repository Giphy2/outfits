<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$action = $data['action'] ?? ($_GET['action'] ?? '');
$phone = normalizePhoneNumber($data['phone'] ?? '');

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid phone number.']);
    exit;
}

if ($action === 'send') {
    $customer = getCustomerByPhone($phone);
    if (!$customer) {
        echo json_encode([
            'success' => false,
            'message' => 'Phone number not registered. Please contact the store administrator to open a Lipa Mdogo Mdogo installment account.'
        ]);
        exit;
    }

    $otp = generateOTP($phone);

    // In production, integrate SMS provider (e.g. Africa's Talking / Twilio).
    // For local prototype & testing, we return the OTP in the response for instantaneous UX.
    echo json_encode([
        'success' => true,
        'message' => "Verification code sent via SMS to {$phone}.",
        'demo_otp' => $otp, // Convenient for local testing
        'customer_name' => $customer['full_name']
    ]);
    exit;
}

if ($action === 'verify') {
    $code = trim($data['code'] ?? '');
    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Please enter the 6-digit OTP code.']);
        exit;
    }

    $isValid = verifyOTP($phone, $code);
    if (!$isValid) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired verification code. Please request a new one.']);
        exit;
    }

    $customer = getCustomerByPhone($phone);
    if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'Customer account not found.']);
        exit;
    }

    // Set authenticated session
    $_SESSION['lmm_customer_id'] = $customer['id'];
    $_SESSION['lmm_customer_phone'] = $customer['phone_number'];
    $_SESSION['lmm_customer_name'] = $customer['full_name'];

    echo json_encode([
        'success' => true,
        'message' => 'Verification successful! Logging in...',
        'redirect' => 'lipa-mdogo-mdogo.php'
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);

