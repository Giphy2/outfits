<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Session is already configured and started by config/config.php.
// Ensure session is started if not active
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

function isAdminAuthenticated() {
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdminAuth() {
    if (!isAdminAuthenticated()) {
        $returnUrl = urlencode($_SERVER['REQUEST_URI'] ?? 'dashboard.php');
        header("Location: login.php?return=" . $returnUrl);
        exit;
    }
}

function getCurrentAdmin() {
    if (!isAdminAuthenticated()) return null;
    return [
        'id' => $_SESSION['admin_id'] ?? 0,
        'username' => $_SESSION['admin_username'] ?? 'admin',
        'full_name' => $_SESSION['admin_name'] ?? 'Administrator'
    ];
}

