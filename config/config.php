<?php
// =========================================================
// Outfit Store Configuration
// =========================================================

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// App Details
define('APP_NAME', 'OUTFIT');
define('APP_TAGLINE', 'Urban Apparel & Streetwear');
define('APP_URL', 'http://localhost:8000');

// Currency & Location
define('CURRENCY_CODE', 'KES');
define('CURRENCY_SYMBOL', 'KES ');

// Code-Based Payment Instructions
// These are displayed to the customer upon generating their order code
define('PAYMENT_CHANNEL_NAME', 'M-PESA Paybill');
define('PAYMENT_BUSINESS_NUMBER', '522522'); // Example Paybill/Till number
define('PAYMENT_ACCOUNT_PREFIX', 'OFT-');   // Order code acts as Account Number
define('ORDER_EXPIRY_MINUTES', 120);        // 2 hours to complete payment

// Bank Transfer Alternative Details
define('BANK_NAME', 'Equity Bank');
define('BANK_ACCOUNT_NAME', 'Outfit Fashion Ltd');
define('BANK_ACCOUNT_NUMBER', '0123456789012');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'outfit_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configure local session directory to avoid permission issues
$sessionDir = __DIR__ . '/../storage/sessions';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0777, true);
}
if (is_dir($sessionDir) && is_writable($sessionDir)) {
    session_save_path($sessionDir);
}

// Session start if not already started and in web environment
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}
