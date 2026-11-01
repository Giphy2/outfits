<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$currentAdminPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('ADMIN_PAGE_TITLE') ? ADMIN_PAGE_TITLE . ' - Admin' : 'Admin Dashboard - ' . APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-nav {
            background: #0f172a;
            border-bottom: 1px solid #1e293b;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
        }
        .stat-val {
            font-size: 1.8rem;
            font-weight: 800;
            color: #fff;
            margin-top: 6px;
        }
    </style>
</head>
<body>

<header class="admin-nav">
    <div class="container nav-wrap">
        <a href="dashboard.php" class="logo">
            <span><?= APP_NAME ?></span>
            <span class="logo-badge" style="background: #e11d48;">ADMIN</span>
        </a>

        <nav class="nav-links">
            <a href="dashboard.php" class="nav-link <?= $currentAdminPage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
            <a href="orders.php" class="nav-link <?= $currentAdminPage === 'orders.php' ? 'active' : '' ?>">Orders</a>
            <a href="../index.php" class="nav-link" target="_blank" style="color: #10b981;">↗ Customer Store</a>
        </nav>
    </div>
</header>

