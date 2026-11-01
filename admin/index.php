<?php
require_once __DIR__ . '/auth.php';

if (isAdminAuthenticated()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;

