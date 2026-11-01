<?php
define('PAGE_TITLE', 'Track Your Order');
require_once __DIR__ . '/includes/header.php';

$code = strtoupper(trim($_GET['code'] ?? ''));
$order = $code ? getOrderByCode($code) : null;
?>

<div class="container" style="padding-top: 40px; padding-bottom: 80px;">
    <!-- Tracker Search Header -->
    <div style="max-width: 650px; margin: 0 auto 40px; text-align: center;">
        <h1 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 12px;">Track Your Order</h1>
        <p style="color: var(--text-secondary); margin-bottom: 24px;">Enter the unique code generated during checkout to view real-time payment confirmation and delivery status.</p>

        <form method="GET" action="track-order.php" style="display: flex; gap: 10px;">
            <input type="text" name="code" value="<?= htmlspecialchars($code) ?>" class="form-control" placeholder="Enter Order Code (e.g. OFT-8429)" style="font-size: 1.1rem; text-transform: uppercase; font-family: monospace;" required>
            <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">
                🔍 Track
            </button>
        </form>
    </div>

    <?php if ($code && !$order): ?>
        <div style="max-width: 600px; margin: 0 auto; text-align: center; background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 40px;">
            <div style="font-size: 2.5rem; margin-bottom: 12px;">🔎</div>
            <h3 style="margin-bottom: 8px;">Order Not Found</h3>
            <p style="color: var(--text-secondary);">We couldn't find any order matching code <strong style="color: #ef4444;"><?= htmlspecialchars($code) ?></strong>. Please double-check the code and try again.</p>
        </div>
    <?php elseif ($order): 
        // Fulfillment steps mapping
        $steps = [
            'placed' => ['label' => 'Order Placed', 'icon' => '📝'],
            'confirmed' => ['label' => 'Payment Verified', 'icon' => '💳'],
            'processing' => ['label' => 'Processing & Packing', 'icon' => '📦'],
            'dispatched' => ['label' => 'Dispatched / In Transit', 'icon' => '🚚'],
            'delivered' => ['label' => 'Delivered', 'icon' => '🎉']
        ];
        $orderSteps = array_keys($steps);
        $currentIndex = array_search($order['fulfillment_status'], $orderSteps);
        if ($currentIndex === false) $currentIndex = 0;
    ?>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <!-- Left: Status & Timeline -->
            <div>
                <!-- Top Status Card -->
                <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px; margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 16px;">
                        <div>
                            <span style="color: var(--text-secondary); font-size: 0.85rem;">ORDER REFERENCE</span>
                            <h2 style="font-family: monospace; font-size: 1.6rem; color: #60a5fa;"><?= htmlspecialchars($order['order_code']) ?></h2>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <?= getPaymentStatusBadge($order['payment_status']) ?>
                            <?= getFulfillmentStatusBadge($order['fulfillment_status']) ?>
                        </div>
                    </div>

                    <!-- Visual Step Progress Tracker -->
                    <div style="display: flex; justify-content: space-between; position: relative; margin: 30px 10px 20px;">
                        <div style="position: absolute; top: 18px; left: 20px; right: 20px; height: 3px; background: var(--border); z-index: 1;"></div>
                        <?php foreach ($steps as $key => $s): 
                            $stepIndex = array_search($key, $orderSteps);
                            $isComplete = $stepIndex <= $currentIndex;
                            $isActive = $stepIndex === $currentIndex;
                        ?>
                            <div style="position: relative; z-index: 2; text-align: center; width: 80px;">
                                <div style="width: 38px; height: 38px; border-radius: 50%; margin: 0 auto 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; background: <?= $isComplete ? '#10b981' : '#1e293b' ?>; border: 2px solid <?= $isActive ? '#3b82f6' : ($isComplete ? '#10b981' : '#334155') ?>; box-shadow: <?= $isActive ? '0 0 12px rgba(59,130,246,0.6)' : 'none' ?>;">
                                    <?= $s['icon'] ?>
                                </div>
                                <span style="font-size: 0.75rem; color: <?= $isComplete ? '#fff' : 'var(--text-muted)' ?>; font-weight: <?= $isActive ? '700' : '500' ?>;">
                                    <?= $s['label'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Event Timeline -->
                <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 24px;">
                    <h3 style="font-size: 1.15rem; margin-bottom: 16px;">Status History & Audit Log</h3>
                    
                    <div class="timeline">
                        <?php if (empty($order['history'])): ?>
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">No status history recorded yet.</p>
                        <?php else: ?>
                            <?php foreach ($order['history'] as $h): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot <?= $h['status_value'] === 'paid' || $h['status_value'] === 'delivered' ? 'completed' : '' ?>"></div>
                                    <div class="timeline-content">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                            <strong style="text-transform: uppercase; font-size: 0.85rem; color: #60a5fa;"><?= htmlspecialchars($h['status_value']) ?></strong>
                                            <span class="timeline-time"><?= date('M d, Y h:i A', strtotime($h['created_at'])) ?></span>
                                        </div>
                                        <p style="color: var(--text-secondary); font-size: 0.88rem;"><?= htmlspecialchars($h['comment'] ?: 'Status updated.') ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Records -->
                <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px;">
                    <h3 style="font-size: 1.15rem; margin-bottom: 16px;">Recorded Payments</h3>
                    <?php if (empty($order['payments'])): ?>
                        <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 8px; padding: 14px; font-size: 0.9rem; color: #fde68a;">
                            ⏳ No payment detected yet. If you have not paid, <a href="order-confirmation.php?code=<?= urlencode($order['order_code']) ?>" style="text-decoration: underline; font-weight: 600;">click here to view payment instructions</a>.
                        </div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Channel</th>
                                        <th>Reference</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order['payments'] as $p): ?>
                                        <tr>
                                            <td style="font-size: 0.85rem;"><?= date('M d, H:i', strtotime($p['created_at'])) ?></td>
                                            <td><span class="badge badge-primary"><?= strtoupper($p['payment_method']) ?></span></td>
                                            <td style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars($p['payment_reference'] ?: '-') ?></td>
                                            <td style="font-weight: 700; color: #10b981;"><?= formatMoney($p['amount']) ?></td>
                                            <td><span class="badge badge-success"><?= strtoupper($p['status']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Order Summary & Delivery Address -->
            <div>
                <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; position: sticky; top: 90px;">
                    <h3 style="font-size: 1.15rem; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                        Delivery Details
                    </h3>

                    <div style="margin-bottom: 16px; font-size: 0.9rem;">
                        <p style="color: var(--text-secondary); margin-bottom: 2px;">Recipient:</p>
                        <p style="font-weight: 600;"><?= htmlspecialchars($order['customer_name']) ?></p>
                        <p style="color: var(--text-secondary);"><?= htmlspecialchars($order['customer_phone']) ?></p>
                    </div>

                    <div style="margin-bottom: 16px; font-size: 0.9rem;">
                        <p style="color: var(--text-secondary); margin-bottom: 2px;">Destination:</p>
                        <p><?= htmlspecialchars($order['delivery_address']) ?>, <?= htmlspecialchars($order['city']) ?></p>
                    </div>

                    <h4 style="font-size: 1rem; margin-top: 20px; margin-bottom: 10px; border-top: 1px solid var(--border); padding-top: 14px;">
                        Items (<?= count($order['items']) ?>)
                    </h4>
                    <div style="margin-bottom: 16px;">
                        <?php foreach ($order['items'] as $item): ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.88rem; margin-bottom: 8px;">
                                <span><?= htmlspecialchars($item['product_name']) ?> <span style="color: var(--text-muted);">x<?= $item['quantity'] ?></span></span>
                                <span style="font-weight: 600;"><?= formatMoney($item['subtotal']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="border-top: 1px solid var(--border); padding-top: 12px; font-size: 0.9rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; color: var(--text-secondary);">
                            <span>Total Due:</span>
                            <span style="color: #fff; font-weight: 600;"><?= formatMoney($order['total_amount']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; color: var(--text-secondary);">
                            <span>Amount Paid:</span>
                            <span style="color: #10b981; font-weight: 600;"><?= formatMoney($order['amount_paid']) ?></span>
                        </div>
                    </div>

                    <?php if ($order['payment_status'] !== 'paid'): ?>
                        <a href="order-confirmation.php?code=<?= urlencode($order['order_code']) ?>" class="btn btn-primary btn-block" style="margin-top: 16px; padding: 12px;">
                            💳 Pay or Simulate Payment
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

