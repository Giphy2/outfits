<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div style="max-width: 380px;">
                <div class="logo" style="margin-bottom: 12px;">
                    <span><?= APP_NAME ?></span>
                    <span class="logo-badge">STORE</span>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
                    Next-generation code-based checkout. Add to cart, receive a unique payment reference, pay via M-PESA or Bank, and track real-time delivery status.
                </p>
            </div>

            <div>
                <h4 style="color: #fff; margin-bottom: 12px; font-size: 1rem;">Customer Care</h4>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 8px;">
                    <li><a href="track-order.php" style="color: var(--text-secondary);">Track Your Order</a></li>
                    <li><a href="cart.php" style="color: var(--text-secondary);">View Shopping Cart</a></li>
                    <li><a href="index.php" style="color: var(--text-secondary);">Catalogue</a></li>
                </ul>
            </div>

            <div>
                <h4 style="color: #fff; margin-bottom: 12px; font-size: 1rem;">Payment Channels</h4>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 8px;">
                    <strong><?= PAYMENT_CHANNEL_NAME ?>:</strong> <?= PAYMENT_BUSINESS_NUMBER ?>
                </p>
                <p style="color: var(--text-secondary); font-size: 0.88rem;">
                    <strong>Bank:</strong> <?= BANK_NAME ?>
                </p>
            </div>
        </div>

        <div style="border-top: 1px solid var(--border); padding-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
            <p style="font-size: 0.82rem; color: var(--text-muted);">
                Code-Based Checkout Architecture Prototype
            </p>
        </div>
    </div>
</footer>

<script src="assets/js/app.js"></script>
</body>
</html>

