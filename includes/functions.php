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



// =========================================================
// Model 2: Lipa Mdogo Mdogo (Installment Accounts) Functions
// =========================================================

/**
 * Normalize phone numbers to local Kenyan standard (07XX / 01XX)
 */
function normalizePhoneNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '', (string)$phone);
    if (strlen($phone) === 12 && substr($phone, 0, 3) === '254') {
        $phone = '0' . substr($phone, 3);
    } elseif (strlen($phone) === 9 && (substr($phone, 0, 1) === '7' || substr($phone, 0, 1) === '1')) {
        $phone = '0' . $phone;
    }
    return $phone;
}

/**
 * Generate a unique Lipa Mdogo Mdogo account number.
 * Example: LMM-94821
 */
function generateLMMAccountNumber() {
    $chars = '0123456789';
    $num = '';
    for ($i = 0; $i < 5; $i++) {
        $num .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return 'LMM-' . $num;
}

/**
 * Find customer by phone number.
 */
function getCustomerByPhone($phone) {
    $pdo = getDatabaseConnection();
    $phone = normalizePhoneNumber($phone);
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone_number = ? LIMIT 1");
    $stmt->execute([$phone]);
    return $stmt->fetch() ?: null;
}

/**
 * Get all installment accounts for a customer.
 */
function getCustomerInstallmentAccounts($customerId) {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        SELECT a.*, p.name AS product_name, p.image_url AS product_image, p.price AS product_price, p.category AS product_category
        FROM installment_accounts a
        JOIN products p ON a.product_id = p.id
        WHERE a.customer_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$customerId]);
    $accounts = $stmt->fetchAll();

    foreach ($accounts as &$acc) {
        $payStmt = $pdo->prepare("SELECT * FROM installment_payments WHERE installment_account_id = ? ORDER BY created_at DESC");
        $payStmt->execute([$acc['id']]);
        $acc['payments'] = $payStmt->fetchAll();
        $acc['progress_percent'] = ($acc['total_amount'] > 0) ? min(100, round(($acc['amount_paid'] / $acc['total_amount']) * 100)) : 0;
    }

    return $accounts;
}

/**
 * Get a single installment account by account number or ID.
 */
function getInstallmentAccountDetails($idOrNumber) {
    $pdo = getDatabaseConnection();
    if (is_numeric($idOrNumber) && strpos((string)$idOrNumber, 'LMM-') === false) {
        $stmt = $pdo->prepare("
            SELECT a.*, c.full_name AS customer_name, c.phone_number AS customer_phone, c.id_number, c.email AS customer_email,
                   p.name AS product_name, p.image_url AS product_image, p.price AS product_price, p.category AS product_category
            FROM installment_accounts a
            JOIN customers c ON a.customer_id = c.id
            JOIN products p ON a.product_id = p.id
            WHERE a.id = ?
        ");
        $stmt->execute([(int)$idOrNumber]);
    } else {
        $stmt = $pdo->prepare("
            SELECT a.*, c.full_name AS customer_name, c.phone_number AS customer_phone, c.id_number, c.email AS customer_email,
                   p.name AS product_name, p.image_url AS product_image, p.price AS product_price, p.category AS product_category
            FROM installment_accounts a
            JOIN customers c ON a.customer_id = c.id
            JOIN products p ON a.product_id = p.id
            WHERE a.account_number = ?
        ");
        $stmt->execute([strtoupper(trim($idOrNumber))]);
    }

    $acc = $stmt->fetch();
    if (!$acc) return null;

    $payStmt = $pdo->prepare("SELECT * FROM installment_payments WHERE installment_account_id = ? ORDER BY created_at DESC");
    $payStmt->execute([$acc['id']]);
    $acc['payments'] = $payStmt->fetchAll();
    $acc['progress_percent'] = ($acc['total_amount'] > 0) ? min(100, round(($acc['amount_paid'] / $acc['total_amount']) * 100)) : 0;

    return $acc;
}

/**
 * Generate a 6-digit OTP for phone number verification.
 */
function generateOTP($phone) {
    $pdo = getDatabaseConnection();
    $phone = normalizePhoneNumber($phone);
    $otpCode = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 minutes expiry

    // Invalidate previous OTPs for this phone
    $invStmt = $pdo->prepare("UPDATE otp_verifications SET is_used = 1 WHERE phone_number = ? AND is_used = 0");
    $invStmt->execute([$phone]);

    // Insert new OTP
    $stmt = $pdo->prepare("INSERT INTO otp_verifications (phone_number, otp_code, expires_at, is_used) VALUES (?, ?, ?, 0)");
    $stmt->execute([$phone, $otpCode, $expiresAt]);

    return $otpCode;
}

/**
 * Verify phone number with OTP.
 */
function verifyOTP($phone, $enteredCode) {
    $pdo = getDatabaseConnection();
    $phone = normalizePhoneNumber($phone);
    $enteredCode = trim($enteredCode);

    $stmt = $pdo->prepare("
        SELECT id FROM otp_verifications 
        WHERE phone_number = ? AND otp_code = ? AND is_used = 0 AND expires_at > CURRENT_TIMESTAMP
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$phone, $enteredCode]);
    $otp = $stmt->fetch();

    if ($otp) {
        $up = $pdo->prepare("UPDATE otp_verifications SET is_used = 1 WHERE id = ?");
        $up->execute([$otp['id']]);
        return true;
    }
    return false;
}

/**
 * Record an installment payment against a Lipa Mdogo Mdogo account.
 */
function recordInstallmentPayment($accountNumber, $amount, $paymentRef = '', $method = 'mpesa_paybill', $notes = '') {
    $pdo = getDatabaseConnection();
    $acc = getInstallmentAccountDetails($accountNumber);

    if (!$acc) {
        return ['success' => false, 'message' => 'Lipa Mdogo Mdogo account not found: ' . $accountNumber];
    }

    $amount = (float)$amount;
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Payment amount must be greater than zero.'];
    }

    if (empty($paymentRef)) {
        $paymentRef = 'MP-' . strtoupper(bin2hex(random_bytes(4)));
    }

    $newAmountPaid = (float)$acc['amount_paid'] + $amount;
    $totalAmount = (float)$acc['total_amount'];
    $newBalance = max(0, $totalAmount - $newAmountPaid);
    
    // Deposit check
    $depositPaid = ($newAmountPaid >= (float)$acc['deposit_required']) ? 1 : $acc['deposit_paid'];

    // Status check
    $newStatus = $acc['status'];
    $newDelivery = $acc['delivery_status'];
    if ($newBalance <= 0) {
        $newStatus = 'completed';
        if ($newDelivery === 'locked') {
            $newDelivery = 'ready_for_collection';
        }
    }

    // Insert payment
    $payStmt = $pdo->prepare("INSERT INTO installment_payments (installment_account_id, payment_method, payment_reference, amount, phone_number, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $payStmt->execute([
        $acc['id'],
        $method,
        $paymentRef,
        $amount,
        $acc['customer_phone'],
        $notes ?: "Installment of " . formatMoney($amount)
    ]);

    // Update account
    $upStmt = $pdo->prepare("
        UPDATE installment_accounts 
        SET amount_paid = ?, balance_remaining = ?, deposit_paid = ?, status = ?, delivery_status = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $upStmt->execute([$newAmountPaid, $newBalance, $depositPaid, $newStatus, $newDelivery, $acc['id']]);

    return [
        'success' => true,
        'account_number' => $acc['account_number'],
        'amount_paid' => $newAmountPaid,
        'balance_remaining' => $newBalance,
        'status' => $newStatus,
        'delivery_status' => $newDelivery,
        'reference' => $paymentRef
    ];
}

/**
 * Return CSS badge for Lipa Mdogo Mdogo Account Status
 */
function getInstallmentStatusBadge($status) {
    $map = [
        'active' => ['label' => 'ACTIVE INSTALLMENTS', 'class' => 'badge-info'],
        'completed' => ['label' => 'FULLY PAID', 'class' => 'badge-success'],
        'defaulted' => ['label' => 'OVERDUE / DEFAULTED', 'class' => 'badge-danger'],
        'suspended' => ['label' => 'SUSPENDED', 'class' => 'badge-warning']
    ];
    $badge = $map[$status] ?? ['label' => strtoupper($status), 'class' => 'badge-secondary'];
    return "<span class=\"badge {$badge['class']}\">{$badge['label']}</span>";
}

/**
 * Return CSS badge for Product Release / Delivery Status
 */
function getDeliveryStatusBadge($status) {
    $map = [
        'locked' => ['label' => '🔒 LOCKED (PAYMENTS PENDING)', 'class' => 'badge-warning'],
        'ready_for_collection' => ['label' => '🎁 READY FOR COLLECTION', 'class' => 'badge-success'],
        'delivered' => ['label' => '✓ COLLECTED / DELIVERED', 'class' => 'badge-primary']
    ];
    $badge = $map[$status] ?? ['label' => strtoupper($status), 'class' => 'badge-secondary'];
    return "<span class=\"badge {$badge['class']}\">{$badge['label']}</span>";
}


// =========================================================
// Privacy & Masking Helpers
// =========================================================

function maskPhoneNumber($phone) {
    $phone = (string)$phone;
    if (strlen($phone) < 7) return '***';
    return substr($phone, 0, 4) . '***' . substr($phone, -3);
}

function maskName($name) {
    $parts = explode(' ', trim((string)$name));
    $masked = [];
    foreach ($parts as $p) {
        if (strlen($p) <= 2) {
            $masked[] = $p;
        } else {
            $masked[] = substr($p, 0, 1) . str_repeat('*', min(4, strlen($p) - 1));
        }
    }
    return implode(' ', $masked);
}

function maskAddress($address) {
    $parts = explode(',', (string)$address);
    if (count($parts) > 1) {
        return trim($parts[0]) . ', **********';
    }
    return substr((string)$address, 0, 5) . ' **********';
}
