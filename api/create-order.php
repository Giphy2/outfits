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

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
    exit;
}

$customerName = sanitize($data['customer_name'] ?? '');
$customerPhone = sanitize($data['customer_phone'] ?? '');
$customerEmail = sanitize($data['customer_email'] ?? '');
$deliveryAddress = sanitize($data['delivery_address'] ?? '');
$city = sanitize($data['city'] ?? 'Nairobi');
$notes = sanitize($data['notes'] ?? '');
$cartItems = $data['items'] ?? [];

if (empty($customerName) || empty($customerPhone) || empty($deliveryAddress)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in your name, phone number, and delivery address.']);
    exit;
}

if (empty($cartItems) || !is_array($cartItems)) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit;
}

$pdo = getDatabaseConnection();

try {
    $pdo->beginTransaction();

    // Verify products & calculate verified total from database
    $subtotal = 0.00;
    $verifiedItems = [];

    $productStmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ? AND is_active = 1");

    foreach ($cartItems as $item) {
        $pId = (int)($item['id'] ?? 0);
        $qty = max(1, (int)($item['quantity'] ?? 1));

        $productStmt->execute([$pId]);
        $prod = $productStmt->fetch();

        if ($prod) {
            $linePrice = (float)$prod['price'];
            $lineSubtotal = $linePrice * $qty;
            $subtotal += $lineSubtotal;

            $verifiedItems[] = [
                'product_id' => $prod['id'],
                'product_name' => $prod['name'],
                'price' => $linePrice,
                'quantity' => $qty,
                'subtotal' => $lineSubtotal
            ];
        }
    }

    if (empty($verifiedItems)) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'None of the items in the cart are valid or available.']);
        exit;
    }

    $shippingFee = 0.00; // Free shipping
    $totalAmount = $subtotal + $shippingFee;

    // Generate unique order code (with retry if duplicate)
    $orderCode = '';
    for ($i = 0; $i < 5; $i++) {
        $candidateCode = generateOrderCode('OFT-');
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_code = ?");
        $checkStmt->execute([$candidateCode]);
        if ($checkStmt->fetchColumn() == 0) {
            $orderCode = $candidateCode;
            break;
        }
    }

    if (!$orderCode) {
        $orderCode = 'OFT-' . strtoupper(bin2hex(random_bytes(3)));
    }

    // Expiry time (2 hours from now)
    $expiresAt = date('Y-m-d H:i:s', time() + (ORDER_EXPIRY_MINUTES * 60));

    // Insert Order
    $insertOrder = $pdo->prepare("
        INSERT INTO orders (
            order_code, customer_name, customer_phone, customer_email, 
            delivery_address, city, subtotal, shipping_fee, total_amount, 
            amount_paid, payment_status, fulfillment_status, expires_at, notes
        ) VALUES (
            ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, 
            0.00, 'pending', 'placed', ?, ?
        )
    ");
    $insertOrder->execute([
        $orderCode, $customerName, $customerPhone, $customerEmail,
        $deliveryAddress, $city, $subtotal, $shippingFee, $totalAmount,
        $expiresAt, $notes
    ]);

    $orderId = $pdo->lastInsertId();

    // Insert Order Items
    $insertItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($verifiedItems as $vi) {
        $insertItem->execute([
            $orderId,
            $vi['product_id'],
            $vi['product_name'],
            $vi['price'],
            $vi['quantity'],
            $vi['subtotal']
        ]);
    }

    // Insert Status History
    $insertHistory = $pdo->prepare("INSERT INTO order_status_history (order_id, status_type, status_value, comment) VALUES (?, 'fulfillment', 'placed', ?)");
    $insertHistory->execute([$orderId, 'Order created. Payment reference generated: ' . $orderCode]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'order_code' => $orderCode,
        'total_amount' => $totalAmount,
        'redirect' => 'order-confirmation.php?code=' . urlencode($orderCode)
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error placing order: ' . $e->getMessage()]);
}

