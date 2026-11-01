<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('PAGE_TITLE') ? PAGE_TITLE . ' - ' . APP_NAME : APP_NAME . ' | ' . APP_TAGLINE ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container nav-wrap">
        <a href="index.php" class="logo">
            <span><?= APP_NAME ?></span>
            <span class="logo-badge">STORE</span>
        </a>

        <nav class="nav-links">
            <a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a>
            <a href="collection.php" class="nav-link <?= $currentPage === 'collection.php' ? 'active' : '' ?>" style="color: #60a5fa; font-weight: 600;">
                Collection
            </a>
            <a href="track-order.php" class="nav-link <?= $currentPage === 'track-order.php' ? 'active' : '' ?>">Track Order</a>
            <a href="lipa-mdogo-mdogo.php" class="nav-link <?= $currentPage === 'lipa-mdogo-mdogo.php' ? 'active' : '' ?>" style="color: #a78bfa; font-weight: 600;">
                📱 Lipa Mdogo Mdogo
            </a>
            <a href="admin/dashboard.php" class="nav-link" style="color: #e11d48;">Admin</a>
            <a href="cart.php" class="cart-btn">
                <span>🛒 Cart</span>
                <span class="cart-count" style="display: none;">0</span>
            </a>
        </nav>
    </div>
</header>
