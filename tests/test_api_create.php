<?php
// Simulate POST request to create-order.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$postData = [
    'customer_name' => 'Sarah Wanjiku',
    'customer_phone' => '0722001122',
    'customer_email' => 'sarah@example.com',
    'delivery_address' => 'Riverside Drive, Block C',
    'city' => 'Nairobi',
    'notes' => 'Call on arrival',
    'items' => [
        ['id' => 1, 'quantity' => 2], // Classic Urban Hoodie
        ['id' => 3, 'quantity' => 1]  // Minimalist Cotton Tee
    ]
];

// Capture output
ob_start();
// Inject mock php://input
stream_wrapper_unregister("php");
class MockPhpStream {
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
stream_wrapper_register("php", "MockPhpStream");
MockPhpStream::setData(json_encode($postData));

require __DIR__ . '/../api/create-order.php';
$output = ob_get_clean();

echo "API Output: " . $output . "\n";
$res = json_decode($output, true);
if ($res && $res['success']) {
    echo "SUCCESS: Order Code {$res['order_code']} generated for total KES {$res['total_amount']}\n";
} else {
    echo "FAILED: " . ($res['message'] ?? 'Unknown error') . "\n";
}

