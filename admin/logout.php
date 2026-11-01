<?php
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_name']);

header('Location: login.php');
exit;

