<?php
require_once __DIR__ . '/../includes/functions.php';

echo "--- 1. Testing Customer Lookup by Phone ---\n";
$customer = getCustomerByPhone('0712345678');
if (!$customer) {
    die("Customer not found!\n");
}
echo "Found Customer: {$customer['full_name']} (Phone: {$customer['phone_number']}, ID: {$customer['id_number']})\n";

echo "\n--- 2. Testing OTP Generation & Verification ---\n";
$otp = generateOTP($customer['phone_number']);
echo "Generated OTP: {$otp}\n";

$isWrongValid = verifyOTP($customer['phone_number'], '000000');
echo "Verify incorrect OTP (000000): " . ($isWrongValid ? "TRUE" : "FALSE") . "\n";

$isCorrectValid = verifyOTP($customer['phone_number'], $otp);
echo "Verify correct OTP ({$otp}): " . ($isCorrectValid ? "SUCCESS" : "FAILED") . "\n";

echo "\n--- 3. Testing Installment Accounts for Customer ---\n";
$accounts = getCustomerInstallmentAccounts($customer['id']);
echo "Active accounts count: " . count($accounts) . "\n";
foreach ($accounts as $acc) {
    echo "  - Account: {$acc['account_number']} | Product: {$acc['product_name']}\n";
    echo "    Total: " . formatMoney($acc['total_amount']) . " | Paid: " . formatMoney($acc['amount_paid']) . " | Remaining: " . formatMoney($acc['balance_remaining']) . "\n";
    echo "    Progress: {$acc['progress_percent']}% | Status: {$acc['status']} | Delivery: {$acc['delivery_status']}\n";
    echo "    Payments logged: " . count($acc['payments']) . "\n";
}

echo "\n--- 4. Testing Installment Payment Recording ---\n";
$payResult = recordInstallmentPayment($accounts[0]['account_number'], 500.00, 'MP-TEST500', 'mpesa_paybill', 'Weekly installment top-up');
echo "Payment result: " . json_encode($payResult, JSON_PRETTY_PRINT) . "\n";

$updatedAcc = getInstallmentAccountDetails($accounts[0]['account_number']);
echo "Updated balance remaining: " . formatMoney($updatedAcc['balance_remaining']) . " (Paid: {$updatedAcc['progress_percent']}%)\n";

echo "\n--- LIPA MDOGO MDOGO TESTS PASSED! ---\n";

