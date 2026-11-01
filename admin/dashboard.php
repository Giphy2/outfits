<?php
if (!defined('ADMIN_PAGE_TITLE')) {
    define('ADMIN_PAGE_TITLE', 'Dashboard & Table Explorer');
}
require_once __DIR__ . '/header.php';

$pdo = getDatabaseConnection();

// =========================================================
// 1. Overall System Metrics
// =========================================================

// Pay in Full Metrics
$totalOrders = (int)($pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0);
$totalRevenue = (float)($pdo->query("SELECT SUM(amount_paid) FROM orders")->fetchColumn() ?: 0.00);
$pendingPayments = (int)($pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending'")->fetchColumn() ?: 0);
$paidOrders = (int)($pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid'")->fetchColumn() ?: 0);
$activeFulfillment = (int)($pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid' AND fulfillment_status NOT IN ('delivered', 'cancelled')")->fetchColumn() ?: 0);

// Lipa Mdogo Mdogo Metrics
$lmmAccounts = (int)($pdo->query("SELECT COUNT(*) FROM installment_accounts")->fetchColumn() ?: 0);
$lmmCollected = (float)($pdo->query("SELECT SUM(amount_paid) FROM installment_accounts")->fetchColumn() ?: 0.00);
$lmmOutstanding = (float)($pdo->query("SELECT SUM(balance_remaining) FROM installment_accounts WHERE status = 'active'")->fetchColumn() ?: 0.00);
$lmmCompleted = (int)($pdo->query("SELECT COUNT(*) FROM installment_accounts WHERE status = 'completed'")->fetchColumn() ?: 0);
$totalCustomers = (int)($pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn() ?: 0);
$totalProducts = (int)($pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0);

// =========================================================
// 2. Database Tables Explorer Config
// =========================================================
$allowedTables = [
    'orders' => [
        'name' => 'orders',
        'label' => 'Orders (Pay in Full)',
        'icon' => '🛒',
        'description' => 'Code-based checkout purchases and fulfillment states'
    ],
    'installment_accounts' => [
        'name' => 'installment_accounts',
        'label' => 'Installment Accounts',
        'icon' => '📱',
        'description' => 'Lipa Mdogo Mdogo customer repayment agreements'
    ],
    'customers' => [
        'name' => 'customers',
        'label' => 'Customers',
        'icon' => '👤',
        'description' => 'Phone-registered profiles for installment accounts'
    ],
    'products' => [
        'name' => 'products',
        'label' => 'Products',
        'icon' => '📦',
        'description' => 'Store apparel catalogue, prices, and inventory stock'
    ],
    'payments' => [
        'name' => 'payments',
        'label' => 'Order Payments',
        'icon' => '💳',
        'description' => 'Transactions received for pay-in-full orders'
    ],
    'installment_payments' => [
        'name' => 'installment_payments',
        'label' => 'Installment Ledger',
        'icon' => '💰',
        'description' => 'Ledger of all installment top-ups per account'
    ],
    'order_status_history' => [
        'name' => 'order_status_history',
        'label' => 'Status History',
        'icon' => '📜',
        'description' => 'Chronological audit events for order tracking'
    ],
    'otp_verifications' => [
        'name' => 'otp_verifications',
        'label' => 'OTP Verification Logs',
        'icon' => '🔑',
        'description' => 'One-time password requests, codes, and expiry'
    ]
];

// Active table selection
$selectedTable = $_GET['table'] ?? 'orders';
if (!array_key_exists($selectedTable, $allowedTables)) {
    $selectedTable = 'orders';
}

// Fetch row counts for all tables
$tableCounts = [];
foreach ($allowedTables as $tKey => $meta) {
    try {
        $tableCounts[$tKey] = (int)$pdo->query("SELECT COUNT(*) FROM `{$tKey}`")->fetchColumn();
    } catch (Exception $e) {
        $tableCounts[$tKey] = 0;
    }
}

// Search term within table
$tableSearch = trim($_GET['tsearch'] ?? '');

// Fetch rows for the active table
$tableRows = [];
$tableColumns = [];

try {
    $querySql = "SELECT * FROM `{$selectedTable}`";
    $queryParams = [];

    if ($tableSearch !== '') {
        // Build search across text columns depending on table
        if ($selectedTable === 'orders') {
            $querySql .= " WHERE order_code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?";
            $queryParams = ["%$tableSearch%", "%$tableSearch%", "%$tableSearch%"];
        } elseif ($selectedTable === 'installment_accounts') {
            $querySql .= " WHERE account_number LIKE ? OR notes LIKE ?";
            $queryParams = ["%$tableSearch%", "%$tableSearch%"];
        } elseif ($selectedTable === 'customers') {
            $querySql .= " WHERE full_name LIKE ? OR phone_number LIKE ? OR id_number LIKE ?";
            $queryParams = ["%$tableSearch%", "%$tableSearch%", "%$tableSearch%"];
        } elseif ($selectedTable === 'products') {
            $querySql .= " WHERE name LIKE ? OR category LIKE ?";
            $queryParams = ["%$tableSearch%", "%$tableSearch%"];
        } elseif ($selectedTable === 'payments' || $selectedTable === 'installment_payments') {
            $querySql .= " WHERE payment_reference LIKE ? OR phone_number LIKE ?";
            $queryParams = ["%$tableSearch%", "%$tableSearch%"];
        } elseif ($selectedTable === 'otp_verifications') {
            $querySql .= " WHERE phone_number LIKE ? OR otp_code LIKE ?";
            $queryParams = ["%$tableSearch%", "%$tableSearch%"];
        }
    }

    $querySql .= " ORDER BY id DESC LIMIT 50";
    $tStmt = $pdo->prepare($querySql);
    $tStmt->execute($queryParams);
    $tableRows = $tStmt->fetchAll();

    if (!empty($tableRows)) {
        $tableColumns = array_keys($tableRows[0]);
    }
} catch (Exception $e) {
    $tableRows = [];
    $tableColumns = [];
}
?>

<div class="container" style="padding-top: 36px; padding-bottom: 70px;">

    <!-- Dashboard Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                <span class="badge badge-primary">COMMAND CENTER</span>
                <span style="font-size: 0.85rem; color: var(--text-muted);">Admin Portal</span>
            </div>
            <h1 style="font-size: 2rem; font-weight: 800;">Administration Dashboard</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                Manage business models, execute operational functions, and inspect real-time database tables.
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="#table-explorer-section" class="btn btn-secondary">
                📊 View Database Tables
            </a>
            <a href="../index.php" class="btn btn-primary" target="_blank">
                ↗ Customer Storefront
            </a>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 36px;">
        <div class="stat-card">
            <span style="color: var(--text-secondary); font-size: 0.82rem; text-transform: uppercase;">Total Collections</span>
            <div class="stat-val" style="color: #10b981;"><?= formatMoney($totalRevenue + $lmmCollected) ?></div>
            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                Orders: <?= formatMoney($totalRevenue) ?> | LMM: <?= formatMoney($lmmCollected) ?>
            </div>
        </div>

        <div class="stat-card">
            <span style="color: var(--text-secondary); font-size: 0.82rem; text-transform: uppercase;">Pay-in-Full Orders</span>
            <div class="stat-val"><?= number_format($totalOrders) ?></div>
            <div style="font-size: 0.78rem; margin-top: 4px;">
                <span style="color: #10b981;">✓ <?= $paidOrders ?> Paid</span> · 
                <span style="color: #f59e0b;">⏳ <?= $pendingPayments ?> Pending</span>
            </div>
        </div>

        <div class="stat-card">
            <span style="color: var(--text-secondary); font-size: 0.82rem; text-transform: uppercase;">Lipa Mdogo Mdogo Plans</span>
            <div class="stat-val" style="color: #60a5fa;"><?= number_format($lmmAccounts) ?></div>
            <div style="font-size: 0.78rem; margin-top: 4px;">
                <span style="color: #10b981;">✓ <?= $lmmCompleted ?> Complete</span> · 
                <span style="color: #f59e0b;">Bal: <?= formatMoney($lmmOutstanding) ?></span>
            </div>
        </div>

        <div class="stat-card">
            <span style="color: var(--text-secondary); font-size: 0.82rem; text-transform: uppercase;">Registered Customers</span>
            <div class="stat-val" style="color: #c084fc;"><?= number_format($totalCustomers) ?></div>
            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                Catalog: <?= number_format($totalProducts) ?> Products
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- Function Navigation Cards Grid -->
    <!-- ========================================================= -->
    <div style="margin-bottom: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 18px;">
            <div>
                <h2 style="font-size: 1.35rem; font-weight: 700;">Operations & Management Functions</h2>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Direct shortcuts to manage workflows, verify payments, and process customer fulfillment.</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
            
            <!-- Card 1: Pay in Full Orders -->
            <div class="product-card" style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.8rem;">🛒</span>
                        <div>
                            <h3 style="font-size: 1.15rem; color: #fff;">Pay in Full Orders</h3>
                            <span class="badge badge-primary">Model 1</span>
                        </div>
                    </div>
                    <span style="font-weight: 700; color: #60a5fa; font-size: 1.1rem;"><?= $totalOrders ?> orders</span>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 16px;">
                    Inspect customer checkouts, confirm code-based payments, update fulfillment status (processing, dispatched, delivered), and print details.
                </p>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: auto;">
                    <a href="orders.php" class="btn btn-primary" style="font-size: 0.82rem; padding: 6px 14px;">
                        View All Orders →
                    </a>
                    <a href="orders.php?payment_status=pending" class="btn btn-secondary" style="font-size: 0.82rem; padding: 6px 12px; color: #f59e0b;">
                        Pending (<?= $pendingPayments ?>)
                    </a>
                    <a href="orders.php?payment_status=paid" class="btn btn-secondary" style="font-size: 0.82rem; padding: 6px 12px; color: #10b981;">
                        Paid (<?= $paidOrders ?>)
                    </a>
                </div>
            </div>

            <!-- Card 2: Lipa Mdogo Mdogo Management -->
            <div class="product-card" style="padding: 24px; border-color: rgba(59, 130, 246, 0.4);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.8rem;">📱</span>
                        <div>
                            <h3 style="font-size: 1.15rem; color: #60a5fa;">Lipa Mdogo Mdogo</h3>
                            <span class="badge badge-accent">Model 2</span>
                        </div>
                    </div>
                    <span style="font-weight: 700; color: #10b981; font-size: 1.1rem;"><?= $lmmAccounts ?> plans</span>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 16px;">
                    Create installment plans, track repayment progress, credit cash payments, and release products when 100% paid.
                </p>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: auto;">
                    <a href="lipa-mdogo.php" class="btn btn-primary" style="font-size: 0.82rem; padding: 6px 14px;">
                        Manage Plans →
                    </a>
                    <a href="lipa-mdogo.php#create-plan-section" class="btn btn-secondary" style="font-size: 0.82rem; padding: 6px 12px;">
                        + New Agreement
                    </a>
                    <a href="../lipa-mdogo-mdogo.php" target="_blank" class="btn btn-secondary" style="font-size: 0.82rem; padding: 6px 12px;">
                        Customer Portal ↗
                    </a>
                </div>
            </div>

            <!-- Card 3: Customer Profiles & Phone Registrations -->
            <div class="product-card" style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.8rem;">👤</span>
                        <div>
                            <h3 style="font-size: 1.15rem; color: #fff;">Customer Registrations</h3>
                            <span class="badge badge-info">Phone Identity</span>
                        </div>
                    </div>
                    <span style="font-weight: 700; color: #c084fc; font-size: 1.1rem;"><?= $totalCustomers ?> registered</span>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 16px;">
                    Register customer phone numbers, National IDs, delivery addresses, and manage access to the OTP customer portal.
                </p>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: auto;">
                    <a href="lipa-mdogo.php#register-customer-section" class="btn btn-primary" style="font-size: 0.82rem; padding: 6px 14px;">
                        + Register Customer →
                    </a>
                    <a href="dashboard.php?table=customers#table-explorer-section" class="btn btn-secondary" style="font-size: 0.82rem; padding: 6px 12px;">
                        View Customers Table
                    </a>
                </div>
            </div>

            <!-- Card 4: Inventory & Products -->
            <div class="product-card" style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.8rem;">📦</span>
                        <div>
                            <h3 style="font-size: 1.15rem; color: #fff;">Inventory & Catalogue</h3>
                            <span class="badge badge-success">Catalogue</span>
                        </div>
                    </div>
                    <span style="font-weight: 700; color: #fff; font-size: 1.1rem;"><?= $totalProducts ?> items</span>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 16px;">
                    View apparel catalogue items, active stock quantities, retail prices, and installment eligibility.
                </p>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: auto;">
                    <a href="products.php" class="btn btn-primary" style="font-size: 0.82rem; padding: 6px 14px;">
                        Manage Products →
                    </a>
                    <a href="products.php#add-product-form" class="btn btn-secondary" style="font-size: 0.82rem; padding: 6px 12px;">
                        + Add Product
                    </a>
                    <a href="../collection.php" target="_blank" class="btn btn-secondary" style="font-size: 0.82rem; padding: 6px 12px;">
                        Live Collection ↗
                    </a>
                </div>
            </div>

            <!-- Card 5: Payment Ledger & Webhook Logs -->
            <div class="product-card" style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.8rem;">💳</span>
                        <div>
                            <h3 style="font-size: 1.15rem; color: #fff;">Payment Audits & Ledgers</h3>
                            <span class="badge badge-warning">Financials</span>
                        </div>
                    </div>
                    <span style="font-weight: 700; color: #10b981; font-size: 1.1rem;"><?= $tableCounts['payments'] + $tableCounts['installment_payments'] ?> txns</span>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 16px;">
                    Audit every M-PESA Paybill, Till, and bank receipt recorded for order codes and installment accounts.
                </p>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: auto;">
                    <a href="dashboard.php?table=payments#table-explorer-section" class="btn btn-secondary" style="font-size: 0.82rem; padding: 6px 12px;">
                        Order Payments
                    </a>
                    <a href="dashboard.php?table=installment_payments#table-explorer-section" class="btn btn-secondary" style="font-size: 0.82rem; padding: 6px 12px;">
                        Installment Ledger
                    </a>
                </div>
            </div>

            <!-- Card 6: Order Tracking & OTP Verification -->
            <div class="product-card" style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.8rem;">🔍</span>
                        <div>
                            <h3 style="font-size: 1.15rem; color: #fff;">Tracking & Security Logs</h3>
                            <span class="badge badge-muted">Verification</span>
                        </div>
                    </div>
                    <span style="font-weight: 700; color: #60a5fa; font-size: 1.1rem;"><?= $tableCounts['otp_verifications'] ?> OTPs</span>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 16px;">
                    Inspect customer parcel tracking timelines and audit generated OTPs and authentication attempts.
                </p>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: auto;">
                    <a href="../track-order.php" target="_blank" class="btn btn-primary" style="font-size: 0.82rem; padding: 6px 14px;">
                        Live Order Tracker ↗
                    </a>
                    <a href="dashboard.php?table=otp_verifications#table-explorer-section" class="btn btn-secondary" style="font-size: 0.82rem; padding: 6px 12px;">
                        OTP Logs
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================================= -->
    <!-- Interactive Database Table Explorer -->
    <!-- ========================================================= -->
    <div id="table-explorer-section" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <span style="font-size: 1.4rem;"><?= $allowedTables[$selectedTable]['icon'] ?></span>
                    <h2 style="font-size: 1.4rem; font-weight: 800;">
                        Database Table: <span style="font-family: monospace; color: #60a5fa;"><?= htmlspecialchars($selectedTable) ?></span>
                    </h2>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.88rem;">
                    <?= htmlspecialchars($allowedTables[$selectedTable]['description']) ?> · Showing latest <?= count($tableRows) ?> records of <?= $tableCounts[$selectedTable] ?> total.
                </p>
            </div>

            <!-- Search inside table -->
            <form method="GET" action="dashboard.php" style="display: flex; gap: 8px;">
                <input type="hidden" name="table" value="<?= htmlspecialchars($selectedTable) ?>">
                <input type="text" name="tsearch" value="<?= htmlspecialchars($tableSearch) ?>" class="form-control" placeholder="Search in <?= $selectedTable ?>..." style="width: 220px; font-size: 0.88rem; padding: 8px 12px;">
                <button type="submit" class="btn btn-primary" style="padding: 8px 14px; font-size: 0.85rem;">Search</button>
                <?php if ($tableSearch !== ''): ?>
                    <a href="dashboard.php?table=<?= urlencode($selectedTable) ?>#table-explorer-section" class="btn btn-secondary" style="padding: 8px 12px; font-size: 0.85rem;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table Switcher Tabs -->
        <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 20px; border-bottom: 1px solid var(--border);">
            <?php foreach ($allowedTables as $tKey => $meta): 
                $isActive = ($tKey === $selectedTable);
            ?>
                <a href="dashboard.php?table=<?= urlencode($tKey) ?>#table-explorer-section" 
                   style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none; white-space: nowrap; transition: all 0.2s; background: <?= $isActive ? 'var(--accent)' : 'var(--bg-secondary)' ?>; color: <?= $isActive ? '#fff' : 'var(--text-secondary)' ?>; border: 1px solid <?= $isActive ? 'var(--accent)' : 'var(--border)' ?>;">
                    <span><?= $meta['icon'] ?></span>
                    <span><?= htmlspecialchars($meta['name']) ?></span>
                    <span style="font-size: 0.75rem; background: <?= $isActive ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.3)' ?>; padding: 1px 6px; border-radius: 10px;">
                        <?= $tableCounts[$tKey] ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Table Display -->
        <?php if (empty($tableRows)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-secondary); background: var(--bg-secondary); border-radius: 8px;">
                <p>No records found in table <strong><?= htmlspecialchars($selectedTable) ?></strong><?= $tableSearch ? " matching '$tableSearch'" : "" ?>.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap" style="max-height: 520px; overflow-y: auto;">
                <table class="data-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <?php foreach ($tableColumns as $col): ?>
                                <th style="position: sticky; top: 0; z-index: 10;"><?= htmlspecialchars($col) ?></th>
                            <?php endforeach; ?>
                            <th style="position: sticky; top: 0; z-index: 10;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableRows as $row): ?>
                            <tr>
                                <?php foreach ($tableColumns as $col): 
                                    $val = $row[$col];
                                ?>
                                    <td>
                                        <?php 
                                        // Custom formatted cells for optimal readability
                                        if ($col === 'order_code' || $col === 'account_number' || $col === 'otp_code') {
                                            echo '<strong style="font-family: monospace; color: #60a5fa;">' . htmlspecialchars((string)$val) . '</strong>';
                                        } elseif ($col === 'payment_status') {
                                            echo getPaymentStatusBadge((string)$val);
                                        } elseif ($col === 'fulfillment_status') {
                                            echo getFulfillmentStatusBadge((string)$val);
                                        } elseif ($col === 'status' && $selectedTable === 'installment_accounts') {
                                            echo getInstallmentStatusBadge((string)$val);
                                        } elseif ($col === 'delivery_status') {
                                            echo getDeliveryStatusBadge((string)$val);
                                        } elseif (in_array($col, ['total_amount', 'subtotal', 'amount_paid', 'balance_remaining', 'amount', 'price', 'deposit_required'])) {
                                            echo '<span style="font-weight: 600;">' . formatMoney((float)$val) . '</span>';
                                        } elseif ($col === 'image_url' && !empty($val)) {
                                            echo '<img src="' . htmlspecialchars((string)$val) . '" style="width: 36px; height: 36px; object-fit: cover; border-radius: 4px;">';
                                        } elseif ($col === 'is_used' || $col === 'deposit_paid' || $col === 'is_active') {
                                            echo $val ? '<span style="color: #10b981; font-weight: 700;">✓ Yes</span>' : '<span style="color: #ef4444;">✕ No</span>';
                                        } elseif ($col === 'raw_payload' && !empty($val)) {
                                            echo '<span title="' . htmlspecialchars((string)$val) . '" style="font-family: monospace; font-size: 0.75rem; color: var(--text-muted); cursor: help;">{payload}</span>';
                                        } else {
                                            echo htmlspecialchars((string)($val ?? ''));
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>

                                <!-- Quick Row Actions -->
                                <td>
                                    <?php if ($selectedTable === 'orders'): ?>
                                        <a href="orders.php?view=<?= urlencode($row['order_code']) ?>" class="btn btn-secondary" style="padding: 2px 8px; font-size: 0.75rem;">
                                            Inspect
                                        </a>
                                    <?php elseif ($selectedTable === 'installment_accounts'): ?>
                                        <a href="lipa-mdogo.php?view=<?= urlencode($row['account_number']) ?>" class="btn btn-secondary" style="padding: 2px 8px; font-size: 0.75rem;">
                                            Inspect
                                        </a>
                                    <?php elseif ($selectedTable === 'customers'): ?>
                                        <a href="lipa-mdogo.php?search=<?= urlencode($row['phone_number']) ?>" class="btn btn-secondary" style="padding: 2px 8px; font-size: 0.75rem;">
                                            Plans
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.75rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
