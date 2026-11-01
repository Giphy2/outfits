<?php
define('ADMIN_PAGE_TITLE', 'Overview');
require_once __DIR__ . '/header.php';

$pdo = getDatabaseConnection();

// Metrics
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;
$totalRevenue = $pdo->query("SELECT SUM(amount_paid) FROM orders")->fetchColumn() ?: 0.00;
$pendingPayments = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending'")->fetchColumn() ?: 0;
$activeFulfillment = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid' AND fulfillment_status NOT IN ('delivered', 'cancelled')")->fetchColumn() ?: 0;

// Recent orders
$stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");
$recentOrders = $stmt->fetchAll();
?>

<div class="container" style="padding-top: 36px; padding-bottom: 60px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 800;">Store Overview</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">Monitor real-time code generations, payments, and delivery fulfillment.</p>
        </div>
        <div>
            <a href="orders.php" class="btn btn-secondary">View All Orders</a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px;">
        <div class="stat-card">
            <span style="color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase;">Total Collected</span>
            <div class="stat-val" style="color: #10b981;"><?= formatMoney($totalRevenue) ?></div>
        </div>
        <div class="stat-card">
            <span style="color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase;">Total Orders</span>
            <div class="stat-val"><?= number_format($totalOrders) ?></div>
        </div>
        <div class="stat-card">
            <span style="color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase;">Pending Payment</span>
            <div class="stat-val" style="color: #f59e0b;"><?= number_format($pendingPayments) ?></div>
        </div>
        <div class="stat-card">
            <span style="color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase;">Awaiting Delivery</span>
            <div class="stat-val" style="color: #60a5fa;"><?= number_format($activeFulfillment) ?></div>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 1.25rem;">Recent Orders & Payment References</h2>
            <span style="color: var(--text-muted); font-size: 0.85rem;">Showing latest 10 orders</span>
        </div>

        <?php if (empty($recentOrders)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                No orders placed yet. Visit the <a href="../index.php" style="color: #3b82f6; text-decoration: underline;">storefront</a> to create a test order!
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order Code</th>
                            <th>Customer</th>
                            <th>Amount Due</th>
                            <th>Paid</th>
                            <th>Payment</th>
                            <th>Fulfillment</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>
                                    <a href="../track-order.php?code=<?= urlencode($order['order_code']) ?>" target="_blank" style="font-family: monospace; font-weight: 700; color: #60a5fa; text-decoration: underline;">
                                        <?= htmlspecialchars($order['order_code']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div><strong><?= htmlspecialchars($order['customer_name']) ?></strong></div>
                                    <div style="font-size: 0.82rem; color: var(--text-secondary);"><?= htmlspecialchars($order['customer_phone']) ?></div>
                                </td>
                                <td style="font-weight: 600;"><?= formatMoney($order['total_amount']) ?></td>
                                <td style="font-weight: 600; color: <?= $order['amount_paid'] >= $order['total_amount'] ? '#10b981' : '#f59e0b' ?>;">
                                    <?= formatMoney($order['amount_paid']) ?>
                                </td>
                                <td><?= getPaymentStatusBadge($order['payment_status']) ?></td>
                                <td><?= getFulfillmentStatusBadge($order['fulfillment_status']) ?></td>
                                <td style="font-size: 0.82rem; color: var(--text-muted);"><?= date('M d, H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <a href="orders.php?view=<?= urlencode($order['order_code']) ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.82rem;">
                                        Manage
                                    </a>
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

