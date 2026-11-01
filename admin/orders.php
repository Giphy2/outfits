<?php
define('ADMIN_PAGE_TITLE', 'Orders Management');
require_once __DIR__ . '/header.php';

$pdo = getDatabaseConnection();

$filterStatus = $_GET['payment_status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$viewCode = strtoupper(trim($_GET['view'] ?? ''));

$sql = "SELECT * FROM orders WHERE 1=1";
$params = [];

if ($filterStatus !== 'all') {
    $sql .= " AND payment_status = ?";
    $params[] = $filterStatus;
}

if ($search !== '') {
    $sql .= " AND (order_code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$viewOrder = $viewCode ? getOrderByCode($viewCode) : null;
?>

<div class="container" style="padding-top: 36px; padding-bottom: 60px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 800;">Order Management</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">Review, verify, and fulfill orders.</p>
        </div>

        <form method="GET" action="orders.php" style="display: flex; gap: 10px;">
            <select name="payment_status" class="form-control" style="width: 160px;" onchange="this.form.submit()">
                <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Payments</option>
                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending Only</option>
                <option value="paid" <?= $filterStatus === 'paid' ? 'selected' : '' ?>>Paid Only</option>
            </select>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search code/name/phone" style="width: 200px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 16px;">Filter</button>
        </form>
    </div>

    <?php if ($viewOrder): ?>
        <!-- Order Detail Drawer -->
        <div style="background: var(--bg-card); border: 2px solid #3b82f6; border-radius: var(--radius); padding: 24px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 14px; margin-bottom: 20px;">
                <div>
                    <span style="font-size: 0.85rem; color: var(--text-secondary);">MANAGING ORDER</span>
                    <h2 style="font-family: monospace; color: #60a5fa;"><?= htmlspecialchars($viewOrder['order_code']) ?></h2>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="../track-order.php?code=<?= urlencode($viewOrder['order_code']) ?>" target="_blank" class="btn btn-secondary" style="font-size: 0.85rem;">
                        ↗ View Customer Tracker
                    </a>
                    <a href="orders.php" class="btn btn-secondary" style="font-size: 0.85rem;">✕ Close</a>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
                <div>
                    <h4 style="margin-bottom: 10px;">Customer & Delivery Info</h4>
                    <p><strong>Name:</strong> <?= htmlspecialchars($viewOrder['customer_name']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($viewOrder['customer_phone']) ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($viewOrder['delivery_address']) ?>, <?= htmlspecialchars($viewOrder['city']) ?></p>
                    <p><strong>Created:</strong> <?= date('M d, Y h:i A', strtotime($viewOrder['created_at'])) ?></p>

                    <h4 style="margin-top: 18px; margin-bottom: 8px;">Order Items</h4>
                    <ul style="list-style: none; margin-bottom: 16px;">
                        <?php foreach ($viewOrder['items'] as $item): ?>
                            <li style="padding: 6px 0; border-bottom: 1px dashed var(--border); display: flex; justify-content: space-between;">
                                <span><?= htmlspecialchars($item['product_name']) ?> x <?= $item['quantity'] ?></span>
                                <strong><?= formatMoney($item['subtotal']) ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; padding: 20px;">
                    <h4 style="margin-bottom: 12px;">Update Fulfillment Status</h4>
                    <div class="form-group">
                        <label class="form-label">Current Status</label>
                        <div><?= getFulfillmentStatusBadge($viewOrder['fulfillment_status']) ?></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="new_status">Change Status</label>
                        <select id="new_status" class="form-control">
                            <option value="placed" <?= $viewOrder['fulfillment_status'] === 'placed' ? 'selected' : '' ?>>Placed</option>
                            <option value="confirmed" <?= $viewOrder['fulfillment_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="processing" <?= $viewOrder['fulfillment_status'] === 'processing' ? 'selected' : '' ?>>Processing & Packing</option>
                            <option value="dispatched" <?= $viewOrder['fulfillment_status'] === 'dispatched' ? 'selected' : '' ?>>Dispatched for Delivery</option>
                            <option value="delivered" <?= $viewOrder['fulfillment_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $viewOrder['fulfillment_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="status_comment">Timeline Note (Optional)</label>
                        <input type="text" id="status_comment" class="form-control" placeholder="e.g. Rider dispatched on bike">
                    </div>
                    <button type="button" class="btn btn-primary btn-block" onclick="updateOrderStatus(<?= $viewOrder['id'] ?>)">
                        Save Status Update
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Orders Table -->
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px;">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Payment Status</th>
                        <th>Fulfillment</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 30px; color: var(--text-secondary);">No orders match criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>
                                    <span style="font-family: monospace; font-weight: 700; color: #60a5fa;"><?= htmlspecialchars($o['order_code']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($o['customer_name']) ?></td>
                                <td><?= htmlspecialchars($o['customer_phone']) ?></td>
                                <td style="font-weight: 600;"><?= formatMoney($o['total_amount']) ?></td>
                                <td style="font-weight: 600; color: <?= $o['amount_paid'] >= $o['total_amount'] ? '#10b981' : '#f59e0b' ?>;">
                                    <?= formatMoney($o['amount_paid']) ?>
                                </td>
                                <td><?= getPaymentStatusBadge($o['payment_status']) ?></td>
                                <td><?= getFulfillmentStatusBadge($o['fulfillment_status']) ?></td>
                                <td style="font-size: 0.82rem; color: var(--text-muted);"><?= date('M d, H:i', strtotime($o['created_at'])) ?></td>
                                <td>
                                    <a href="orders.php?view=<?= urlencode($o['order_code']) ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.8rem;">
                                        Inspect
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function updateOrderStatus(orderId) {
    const newStatus = document.getElementById('new_status').value;
    const comment = document.getElementById('status_comment').value;

    try {
        const response = await fetch('update-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                fulfillment_status: newStatus,
                comment: comment
            })
        });
        const res = await response.json();
        if (res.success) {
            alert(res.message);
            window.location.reload();
        } else {
            alert('Error: ' + res.message);
        }
    } catch (e) {
        alert('Failed to update status.');
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>

