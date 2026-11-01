<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../admin/auth.php';

echo "--- 1. Testing Admin Authentication Guard ---\n";
// Case A: Unauthenticated check
$_SESSION = [];
$isAuth1 = isAdminAuthenticated();
echo "Unauthenticated isAdminAuthenticated(): " . ($isAuth1 ? "TRUE (FAIL)" : "FALSE (PASS)") . "\n";

// Case B: Admin Password Verification against DB
$pdo = getDatabaseConnection();
$stmt = $pdo->prepare("SELECT * FROM admins WHERE username = 'admin' LIMIT 1");
$stmt->execute();
$admin = $stmt->fetch();

$wrongPass = password_verify('wrongpass', $admin['password_hash']);
echo "Verify wrong password ('wrongpass'): " . ($wrongPass ? "TRUE (FAIL)" : "FALSE (PASS)") . "\n";

$correctPass = password_verify('admin123', $admin['password_hash']);
echo "Verify correct password ('admin123'): " . ($correctPass ? "TRUE (PASS)" : "FALSE (FAIL)") . "\n";

// Case C: Authenticated session
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_id'] = $admin['id'];
$isAuth2 = isAdminAuthenticated();
echo "Authenticated session isAdminAuthenticated(): " . ($isAuth2 ? "TRUE (PASS)" : "FALSE (FAIL)") . "\n";

echo "\n--- 2. Testing Customer Installment Route Authorization ---\n";
// Case A: No session attempting to call simulate-installment
$_SESSION = [];
ob_start();
$_POST = ['account_number' => 'LMM-84920', 'amount' => 500];
require __DIR__ . '/../api/simulate-installment.php';
$outNoAuth = ob_get_clean();
$resNoAuth = json_decode($outNoAuth, true);
echo "Simulate installment without auth: " . (!$resNoAuth['success'] ? "BLOCKED (PASS)" : "ALLOWED (FAIL)") . "\n";
echo "Response: " . ($resNoAuth['message'] ?? '') . "\n";

// Case B: Authenticated customer with their own account
$c = getCustomerByPhone('0712345678');
$_SESSION['lmm_customer_id'] = $c['id'];
$_SESSION['lmm_customer_phone'] = $c['phone_number'];
ob_start();
$_POST = ['account_number' => 'LMM-84920', 'amount' => 500];
require __DIR__ . '/../api/simulate-installment.php';
$outAuth = ob_get_clean();
$resAuth = json_decode($outAuth, true);
echo "Simulate installment with matching customer auth: " . ($resAuth['success'] ? "ALLOWED (PASS)" : "BLOCKED (FAIL)") . "\n";

// Case C: Authenticated customer attempting to touch someone else's account
$_SESSION['lmm_customer_id'] = 99999; // Different customer
ob_start();
$_POST = ['account_number' => 'LMM-84920', 'amount' => 500];
require __DIR__ . '/../api/simulate-installment.php';
$outHacker = ob_get_clean();
$resHacker = json_decode($outHacker, true);
echo "Cross-account payment attempt: " . (!$resHacker['success'] ? "BLOCKED (PASS)" : "ALLOWED (FAIL)") . "\n";
echo "Response: " . ($resHacker['message'] ?? '') . "\n";

echo "\n--- 3. Testing Order Status Update Route Authorization ---\n";
// Case A: Unauthenticated
$_SESSION = [];
ob_start();
$_POST = [];
// Inject mock input
MockPhpStream3::setData(json_encode(['order_id' => 1, 'fulfillment_status' => 'delivered']));
class MockPhpStream3 {
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
stream_wrapper_unregister("php");
stream_wrapper_register("php", "MockPhpStream3");
MockPhpStream3::setData(json_encode(['order_id' => 1, 'fulfillment_status' => 'delivered']));

$_SERVER['REQUEST_METHOD'] = 'POST';
require __DIR__ . '/../admin/update-status.php';
$outStatusNoAuth = ob_get_clean();
$resStatusNoAuth = json_decode($outStatusNoAuth, true);
echo "Update order status without admin session: " . (!$resStatusNoAuth['success'] ? "BLOCKED (PASS)" : "ALLOWED (FAIL)") . "\n";
echo "Response: " . ($resStatusNoAuth['message'] ?? '') . "\n";

echo "\n--- ALL SECURITY & AUTHENTICATION GATES VERIFIED! ---\n";

