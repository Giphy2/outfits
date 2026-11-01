<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDatabaseConnection();
$p = $pdo->query('SELECT * FROM products LIMIT 1')->fetch();

echo "--- 1. Testing Unregistered Phone on Lipa Mdogo Mdogo ---\n";
// Attempt to create Lipa Mdogo Mdogo order with unregistered phone 0799111222
$payloadUnreg = [
    'payment_mode' => 'lipa_mdogo',
    'customer_name' => 'Stranger Danger',
    'customer_phone' => '0799111222',
    'delivery_address' => 'Nairobi CBD',
    'city' => 'Nairobi',
    'items' => [
        ['id' => $p['id'], 'quantity' => 1]
    ]
];

ob_start();
stream_wrapper_unregister("php");
class MockStreamUnreg {
    public $context;
    private static $data = '';
    public static function setData($d) { self::$data = $d; }
    public function stream_open($path, $mode, $options, &$opened_path) { return true; }
    public function stream_read($count) {
        $ret = substr(self::$data, 0, $count);
        self::$data = substr(self::$data, $count);
        return $ret;
    }
    public function stream_eof() { return strlen(self::$data) === 0; }
    public function stream_stat() { return []; }
}
stream_wrapper_register("php", "MockStreamUnreg");
MockStreamUnreg::setData(json_encode($payloadUnreg));

$_SERVER['REQUEST_METHOD'] = 'POST';
require __DIR__ . '/../api/create-order.php';
$outUnreg = ob_get_clean();

$resUnreg = json_decode($outUnreg, true);
echo "Result for unregistered phone: " . ($resUnreg['success'] ? 'FAILED (should have rejected)' : 'PASSED (Rejected as expected)') . "\n";
echo "Message: " . $resUnreg['message'] . "\n";

echo "\n--- 2. Testing Registered Phone (0712345678) on Lipa Mdogo Mdogo ---\n";
$payloadReg = [
    'payment_mode' => 'lipa_mdogo',
    'customer_name' => 'John Kamau',
    'customer_phone' => '0712345678',
    'delivery_address' => 'Kilimani, Nairobi',
    'city' => 'Nairobi',
    'items' => [
        ['id' => $p['id'], 'quantity' => 1]
    ]
];

MockStreamUnreg::setData(json_encode($payloadReg));
ob_start();
require __DIR__ . '/../api/create-order.php';
$outReg = ob_get_clean();
$resReg = json_decode($outReg, true);

echo "Result for registered phone: " . ($resReg['success'] ? 'PASSED (Account created)' : 'FAILED') . "\n";
echo "Account Number: " . ($resReg['account_number'] ?? 'N/A') . " | Deposit: KES " . ($resReg['deposit_required'] ?? '0') . "\n";
echo "Redirect: " . ($resReg['redirect'] ?? 'N/A') . "\n";
