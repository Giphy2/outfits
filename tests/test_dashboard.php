<?php
$_SERVER['PHP_SELF'] = 'dashboard.php';

$tables = ['orders', 'installment_accounts', 'customers', 'products', 'payments', 'installment_payments', 'order_status_history', 'otp_verifications'];

foreach ($tables as $t) {
    $_GET['table'] = $t;
    ob_start();
    require __DIR__ . '/../admin/dashboard.php';
    $html = ob_get_clean();
    echo "Table [{$t}]: OK (" . strlen($html) . " bytes)\n";
}
