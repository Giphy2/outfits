<?php
define('PAGE_TITLE', 'Order Confirmation & Payment');
require_once __DIR__ . '/includes/header.php';

$code = strtoupper(trim($_GET['code'] ?? ''));
$order = $code ? getOrderByCode($code) : null;

if (!$order): ?>
    <div class="container" style="padding: 80px 20px; text-align: center;">
        <h2 style="font-size: 2rem; margin-bottom: 16px;">Order Not Found</h2>
        <p style="color: var(--text-secondary); margin-bottom: 24px;">No order matches reference code: <?= htmlspecialchars($code) ?></p>
        <a href="index.php" class="btn btn-primary">Return to Shop</a>
    </div>
<?php 
require_once __DIR__ . '/includes/footer.php';
exit;
endif;

$balanceDue = max(0, (float)$order['total_amount'] - (float)$order['amount_paid']);
?>

<div class="container" style="padding-top: 40px; padding-bottom: 80px;">
    <!-- Top Hero Card with Order Code -->
    <div class="code-hero-card" id="status-card">
        <div style="display: inline-block; margin-bottom: 8px;">
            <span id="payment-badge-wrap"><?= getPaymentStatusBadge($order['payment_status']) ?></span>
            <span id="fulfillment-badge-wrap" style="margin-left: 8px;"><?= getFulfillmentStatusBadge($order['fulfillment_status']) ?></span>
        </div>

        <h1 style="font-size: 2.2rem; margin: 10px 0;">Use This Code To Complete Payment</h1>
        <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto;">
            Your items have been reserved. Please submit payment using the code below as your account reference.
        </p>

        <div class="code-display-wrap">
            <span class="code-value" id="display-code"><?= htmlspecialchars($order['order_code']) ?></span>
            <button type="button" class="btn-copy" onclick="copyToClipboard('<?= $order['order_code'] ?>', this)">
                📋 Copy Code
            </button>
        </div>

        <div style="display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; margin-top: 10px;">
            <div>
                <span style="color: var(--text-secondary); font-size: 0.9rem;">Total Order:</span>
                <strong style="color: #fff; font-size: 1.15rem; margin-left: 6px;"><?= formatMoney($order['total_amount']) ?></strong>
            </div>
            <div>
                <span style="color: var(--text-secondary); font-size: 0.9rem;">Amount Paid:</span>
                <strong id="display-amount-paid" style="color: #10b981; font-size: 1.15rem; margin-left: 6px;"><?= formatMoney($order['amount_paid']) ?></strong>
            </div>
            <div>
                <span style="color: var(--text-secondary); font-size: 0.9rem;">Balance Due:</span>
                <strong id="display-balance-due" style="color: #f59e0b; font-size: 1.15rem; margin-left: 6px;"><?= formatMoney($balanceDue) ?></strong>
            </div>
        </div>
    </div>

    <!-- Payment Instructions & Order Details -->
    <div style="display: grid; grid-template-columns: 3fr 2fr; gap: 30px;">
        <!-- Left: Payment Instructions -->
        <div>
            <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px; margin-bottom: 24px;">
                <h3 style="font-size: 1.3rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <span>📱</span> How to Pay via <?= PAYMENT_CHANNEL_NAME ?>
                </h3>

                <div class="payment-steps-grid">
                    <div class="payment-step">
                        <div class="step-num">1</div>
                        <h4 style="margin-bottom: 6px;">Open M-PESA</h4>
                        <p style="color: var(--text-secondary); font-size: 0.88rem;">Select <strong>Lipa na M-PESA</strong> on your phone menu or app, then choose <strong>Paybill</strong>.</p>
                    </div>

                    <div class="payment-step">
                        <div class="step-num">2</div>
                        <h4 style="margin-bottom: 6px;">Business Number</h4>
                        <p style="color: var(--text-secondary); font-size: 0.88rem;">Enter Business Number: <strong style="color: #60a5fa;"><?= PAYMENT_BUSINESS_NUMBER ?></strong></p>
                    </div>

                    <div class="payment-step">
                        <div class="step-num">3</div>
                        <h4 style="margin-bottom: 6px;">Account Number</h4>
                        <p style="color: var(--text-secondary); font-size: 0.88rem;">Enter your Order Code: <strong style="color: #60a5fa;"><?= $order['order_code'] ?></strong></p>
                    </div>

                    <div class="payment-step">
                        <div class="step-num">4</div>
                        <h4 style="margin-bottom: 6px;">Amount & PIN</h4>
                        <p style="color: var(--text-secondary); font-size: 0.88rem;">Enter exact amount <strong style="color: #10b981;"><?= formatMoney($balanceDue) ?></strong>, enter your PIN and send.</p>
                    </div>
                </div>

                <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 14px; font-size: 0.88rem; color: #fde68a;">
                    ⏱ <strong>Payment Window:</strong> Please complete your payment before <strong id="display-expires-at"><?= date('h:i A, M d', strtotime($order['expires_at'])) ?></strong> to prevent reservation cancellation.
                </div>
            </div>

            <!-- Payment Simulation Sandbox for Testing -->
            <div style="background: #111a2e; border: 1px dashed #3b82f6; border-radius: var(--radius); padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="font-size: 1.15rem; color: #60a5fa; display: flex; align-items: center; gap: 8px;">
                        <span>🧪</span> Payment Simulator (Development Sandbox)
                    </h3>
                    <span class="badge badge-primary">TEST TOOL</span>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 16px;">
                    Test the automatic backend payment confirmation without real money. Click a scenario below:
                </p>

                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-success" onclick="simulatePayment(<?= $balanceDue ?>)">
                        ✓ Simulate Full Payment (<?= formatMoney($balanceDue) ?>)
                    </button>
                    <?php if ($balanceDue > 1000): ?>
                        <button type="button" class="btn btn-secondary" onclick="simulatePayment(1000)">
                            Simulate Partial Payment (<?= formatMoney(1000) ?>)
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-secondary" onclick="simulatePayment(<?= $balanceDue + 500 ?>)">
                        Simulate Overpayment (+500)
                    </button>
                </div>
                <div id="sim-feedback" style="margin-top: 14px; font-size: 0.85rem; display: none;"></div>
            </div>
        </div>

        <!-- Right: Order Details & Live Tracking Link -->
        <div>
            <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; position: sticky; top: 90px;">
                <h3 style="font-size: 1.15rem; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                    Order Summary
                </h3>

                <div style="margin-bottom: 16px;">
                    <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 4px;">Delivery To:</p>
                    <p><strong><?= htmlspecialchars($order['customer_name']) ?></strong> (<?= htmlspecialchars($order['customer_phone']) ?>)</p>
                    <p style="color: var(--text-secondary); font-size: 0.88rem;"><?= htmlspecialchars($order['delivery_address']) ?>, <?= htmlspecialchars($order['city']) ?></p>
                </div>

                <div style="border-top: 1px solid var(--border); padding-top: 14px; margin-bottom: 16px;">
                    <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 8px;">Items Ordered:</p>
                    <?php foreach ($order['items'] as $item): ?>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 6px;">
                            <span><?= htmlspecialchars($item['product_name']) ?> <span style="color: var(--text-muted);">x<?= $item['quantity'] ?></span></span>
                            <span><?= formatMoney($item['subtotal']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <a href="track-order.php?code=<?= urlencode($order['order_code']) ?>" class="btn btn-secondary btn-block" style="padding: 12px; margin-top: 10px;">
                    📍 Open Full Tracking Page →
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const ORDER_CODE = "<?= $order['order_code'] ?>";

async function simulatePayment(amount) {
    const feedback = document.getElementById('sim-feedback');
    feedback.style.display = 'block';
    feedback.innerHTML = '<span style="color: #60a5fa;">Simulating payment callback...</span>';

    try {
        const response = await fetch('api/simulate-payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_code: ORDER_CODE,
                amount: amount,
                payment_method: 'mpesa_paybill'
            })
        });
        const res = await response.json();

        if (res.success) {
            feedback.innerHTML = `<span style="color: #10b981;">✓ ${res.message} (Receipt: ${res.reference})</span>`;
            showToast('Payment verified successfully!', 'success');
            pollStatus(); // Immediate refresh
        } else {
            feedback.innerHTML = `<span style="color: #ef4444;">✕ ${res.message}</span>`;
        }
    } catch (e) {
        feedback.innerHTML = '<span style="color: #ef4444;">Network error during simulation</span>';
    }
}

async function pollStatus() {
    try {
        const response = await fetch(`api/track-order.php?code=${ORDER_CODE}`);
        const res = await response.json();

        if (res.success && res.order) {
            const o = res.order;
            document.getElementById('payment-badge-wrap').innerHTML = o.payment_status_badge;
            document.getElementById('fulfillment-badge-wrap').innerHTML = o.fulfillment_status_badge;
            document.getElementById('display-amount-paid').innerText = 'KES ' + o.amount_paid.toLocaleString(undefined, {minimumFractionDigits: 2});
            
            const bal = Math.max(0, o.total_amount - o.amount_paid);
            document.getElementById('display-balance-due').innerText = 'KES ' + bal.toLocaleString(undefined, {minimumFractionDigits: 2});

            if (o.payment_status === 'paid') {
                const card = document.getElementById('status-card');
                card.style.borderColor = '#10b981';
                card.style.background = 'linear-gradient(145deg, #102d24, #0d1e18)';
            }
        }
    } catch (e) {
        console.error('Polling error', e);
    }
}

// Auto-poll every 4 seconds to capture asynchronous payments
setInterval(pollStatus, 4000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

