<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$phone = normalizePhoneNumber($_GET['phone'] ?? ($_POST['phone'] ?? ''));

if (empty($phone)) {
    echo json_encode(['success' => false, 'registered' => false, 'message' => 'Phone number is required.']);
    exit;
}

$customer = getCustomerByPhone($phone);

if ($customer) {
    echo json_encode([
        'success' => true,
        'registered' => true,
        'customer' => [
            'id' => $customer['id'],
            'full_name' => $customer['full_name'],
            'phone_number' => $customer['phone_number'],
            'id_number' => $customer['id_number'],
            'delivery_address' => $customer['delivery_address'],
            'city' => $customer['city']
        ],
        'message' => 'Customer registered and verified for Lipa Mdogo Mdogo.'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'registered' => false,
        'phone' => $phone,
        'message' => 'Phone number is not yet registered for Lipa Mdogo Mdogo.'
    ]);
}

