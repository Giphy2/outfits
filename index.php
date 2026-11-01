<?php
define('PAGE_TITLE', 'Home - Urban Apparel & Flexible Checkout');
require_once __DIR__ . '/includes/header.php';

$pdo = getDatabaseConnection();
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn() ?: 0;
?>

<!-- Hero Section -->
<div class="hero" style="padding: 70px 0 50px;">
    <div class="container">
        <div style="display: inline-block; margin-bottom: 12px;">
            <span class="logo-badge" style="font-size: 0.85rem; padding: 4px 12px;">NEW SEASON ARRIVALS</span>
        </div>
        <h1 style="font-size: 3.2rem; font-weight: 800; letter-spacing: -1px; margin-bottom: 16px;">
            Urban Streetwear,<br>Flexible Payments
        </h1>
        <p style="font-size: 1.2rem; max-width: 650px; margin: 0 auto 30px; color: var(--text-secondary); line-height: 1.6;">
            Top-tier heavyweight hoodies, jackets, joggers, and essentials. No debit card required—choose between instant code-based checkout or flexible <strong>Lipa Mdogo Mdogo</strong> installments.
        </p>

        <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-bottom: 30px;">
            <a href="collection.php" class="btn btn-primary" style="padding: 14px 28px; font-size: 1.05rem;">
                🛍️ Explore Collection (<?= $totalProducts ?> Items) →
            </a>
            <a href="lipa-mdogo-mdogo.php" class="btn btn-secondary" style="padding: 14px 24px; font-size: 1.05rem; border-color: #3b82f6; color: #60a5fa;">
                📱 Lipa Mdogo Mdogo Portal
            </a>
            <a href="track-order.php" class="btn btn-secondary" style="padding: 14px 20px; font-size: 1.05rem;">
                🔍 Track Order Code
            </a>
        </div>

        <div class="hero-features">
            <div class="hero-badge">
                <span>⚡</span> Code-Based Paybill Checkout
            </div>
            <div class="hero-badge" style="border-color: #3b82f6; background: rgba(59, 130, 246, 0.1);">
                <span style="color: #60a5fa;">📱</span> Lipa Mdogo Mdogo Installments
            </div>
            <div class="hero-badge">
                <span>🔒</span> Phone & OTP Verification
            </div>
            <div class="hero-badge">
                <span>🚚</span> Nationwide Delivery
            </div>
        </div>
    </div>
</div>

<!-- Business Models Section -->
<div class="container" style="padding: 40px 20px 60px;">
    <div style="text-align: center; margin-bottom: 36px;">
        <span class="badge badge-primary" style="margin-bottom: 8px;">PAYMENT FLEXIBILITY</span>
        <h2 style="font-size: 2rem; font-weight: 800;">Two Transparent Ways To Shop</h2>
        <p style="color: var(--text-secondary); font-size: 1rem; max-width: 550px; margin: 6px auto 0;">
            Choose the payment model that fits your budget at checkout.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 60px;">
        
        <!-- Model 1 Card -->
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 32px; display: flex; flex-direction: column;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 2.2rem;">⚡</span>
                    <div>
                        <h3 style="font-size: 1.35rem; color: #fff;">Model 1: Pay In Full</h3>
                        <span class="badge badge-primary">CODE-BASED CHECKOUT</span>
                    </div>
                </div>
            </div>

            <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px;">
                Add items to cart and click checkout. The system reserves your order and generates an instant, unique <strong>Order Code</strong> (e.g. <code>OFT-8429K</code>).
            </p>

            <ul style="list-style: none; font-size: 0.9rem; color: #cbd5e1; display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; flex-grow: 1;">
                <li>✓ <strong>No Card Details Required:</strong> Pay via M-PESA Paybill 522522 or Bank.</li>
                <li>✓ <strong>2-Hour Reservation Window:</strong> Stock held while payment processes.</li>
                <li>✓ <strong>Live Parcel Tracking:</strong> Enter your code anytime on the tracking page.</li>
            </ul>

            <a href="collection.php" class="btn btn-primary btn-block" style="padding: 12px;">
                Shop Collection & Pay in Full →
            </a>
        </div>

        <!-- Model 2 Card -->
        <div style="background: linear-gradient(145deg, #132238, #111a2e); border: 2px solid #3b82f6; border-radius: var(--radius); padding: 32px; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(59,130,246,0.15);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 2.2rem;">📱</span>
                    <div>
                        <h3 style="font-size: 1.35rem; color: #60a5fa;">Model 2: Lipa Mdogo Mdogo</h3>
                        <span class="badge badge-accent">AGENT-VERIFIED INSTALLMENTS</span>
                    </div>
                </div>
            </div>

            <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px;">
                Pre-register your phone number with an agent, pay a <strong>20% deposit</strong>, and clear the remaining balance in flexible installments at your own pace.
            </p>

            <ul style="list-style: none; font-size: 0.9rem; color: #cbd5e1; display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; flex-grow: 1;">
                <li>✓ <strong>Phone-Based Account:</strong> Log in using your phone and secure SMS OTP.</li>
                <li>✓ <strong>Live Progress Bar:</strong> Monitor percentage completed and balance remaining.</li>
                <li>✓ <strong>Release Upon Completion:</strong> Marked ready for collection when 100% paid.</li>
            </ul>

            <a href="lipa-mdogo-mdogo.php" class="btn btn-success btn-block" style="padding: 12px;">
                Access Lipa Mdogo Mdogo Portal →
            </a>
        </div>

    </div>

    <!-- How It Works (3 Steps) -->
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 40px; text-align: center; margin-bottom: 50px;">
        <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 8px;">How It Works</h2>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 36px;">
            A frictionless shopping and payment experience designed for mobile money users.
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; text-align: left;">
            <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 22px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; margin-bottom: 14px;">1</div>
                <h4 style="font-size: 1.1rem; margin-bottom: 6px;">Browse Collection</h4>
                <p style="color: var(--text-secondary); font-size: 0.88rem; line-height: 1.5;">
                    Visit the collection page, explore our heavyweight streetwear essentials, and add desired items to your cart.
                </p>
            </div>

            <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 22px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #8b5cf6; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; margin-bottom: 14px;">2</div>
                <h4 style="font-size: 1.1rem; margin-bottom: 6px;">Select Payment Plan</h4>
                <p style="color: var(--text-secondary); font-size: 0.88rem; line-height: 1.5;">
                    Choose <strong>Pay in Full</strong> for immediate code generation, or <strong>Lipa Mdogo Mdogo</strong> to pay via installments.
                </p>
            </div>

            <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 22px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #10b981; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; margin-bottom: 14px;">3</div>
                <h4 style="font-size: 1.1rem; margin-bottom: 6px;">Pay & Track</h4>
                <p style="color: var(--text-secondary); font-size: 0.88rem; line-height: 1.5;">
                    Submit payment via M-PESA Paybill referencing your unique code or account number, and track delivery progress in real time.
                </p>
            </div>
        </div>
    </div>

    <!-- Final CTA Banner -->
    <div style="background: linear-gradient(145deg, #16243d, #0f172a); border: 1px solid #3b82f6; border-radius: var(--radius); padding: 40px; text-align: center;">
        <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 10px;">Ready To Upgrade Your Look?</h2>
        <p style="color: var(--text-secondary); font-size: 1rem; max-width: 500px; margin: 0 auto 24px;">
            Explore hoodies, jackets, utility joggers, and sneakers in our complete apparel collection.
        </p>
        <a href="collection.php" class="btn btn-primary" style="padding: 14px 32px; font-size: 1.05rem;">
            Explore All Products (Collection) →
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
