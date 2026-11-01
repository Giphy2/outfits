<?php
define('PAGE_TITLE', 'Checkout');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 40px; padding-bottom: 80px;">
    <div style="margin-bottom: 28px;">
        <h1 style="font-size: 2rem; font-weight: 800;">Order Checkout</h1>
        <p style="color: var(--text-secondary);">Enter your delivery details to generate your unique payment code.</p>
    </div>

    <form id="checkout-form" onsubmit="handleCheckout(event)" style="display: grid; grid-template-columns: 3fr 2fr; gap: 30px;">
        <!-- Left: Customer Details -->
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px;">
            <h3 style="font-size: 1.25rem; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
                1. Delivery & Contact Information
            </h3>

            <div class="form-group">
                <label class="form-label" for="customer_name">Full Name *</label>
                <input type="text" id="customer_name" class="form-control" placeholder="e.g. Brian Otieno" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label" for="customer_phone">Phone Number (M-PESA) *</label>
                    <input type="tel" id="customer_phone" class="form-control" placeholder="e.g. 0712345678" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="customer_email">Email Address (Optional)</label>
                    <input type="email" id="customer_email" class="form-control" placeholder="e.g. brian@example.com">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="delivery_address">Delivery Address / Street *</label>
                <textarea id="delivery_address" class="form-control" rows="2" placeholder="e.g. Kilimani, Wood Avenue, Apartment 4B" required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="city">Town / City</label>
                <input type="text" id="city" class="form-control" value="Nairobi" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="notes">Delivery Notes (Optional)</label>
                <input type="text" id="notes" class="form-control" placeholder="e.g. Ring the bell or leave at security desk">
            </div>

            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 14px; margin-top: 10px; font-size: 0.88rem; color: #6ee7b7;">
                🔒 <strong>No Card Details Required:</strong> On clicking "Generate Payment Code", your order will be reserved and an instant Paybill reference code will be generated for you.
            </div>
        </div>

        <!-- Right: Order Items & Submission -->
        <div>
            <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; position: sticky; top: 90px;">
                <h3 style="font-size: 1.2rem; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                    Order Review
                </h3>

                <div id="checkout-items" style="max-height: 250px; overflow-y: auto; margin-bottom: 16px;">
                    <!-- Rendered by JS -->
                </div>

                <div style="border-top: 1px solid var(--border); padding-top: 14px; margin-bottom: 10px; display: flex; justify-content: space-between; color: var(--text-secondary);">
                    <span>Subtotal</span>
                    <span id="checkout-subtotal" style="color: #fff; font-weight: 600;">KES 0.00</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 14px; color: var(--text-secondary);">
                    <span>Delivery</span>
                    <span style="color: #10b981; font-weight: 600;">FREE</span>
                </div>

                <div style="border-top: 1px solid var(--border); padding-top: 14px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: baseline;">
                    <span style="font-size: 1.1rem; font-weight: 700;">Total Payable</span>
                    <span id="checkout-total" style="font-size: 1.5rem; font-weight: 800; color: #60a5fa;">KES 0.00</span>
                </div>

                <button type="submit" id="btn-submit-order" class="btn btn-primary btn-block" style="padding: 14px; font-size: 1.05rem;">
                    ⚡ Generate Payment Code
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function initCheckout() {
    const items = Cart.getItems();
    if (items.length === 0) {
        window.location.href = 'cart.php';
        return;
    }

    const container = document.getElementById('checkout-items');
    container.innerHTML = items.map(item => `
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed var(--border); font-size: 0.9rem;">
            <div>
                <strong>${item.name}</strong> <span style="color: var(--text-muted);">x${item.quantity}</span>
            </div>
            <div style="font-weight: 600;">
                KES ${(item.price * item.quantity).toLocaleString()}
            </div>
        </div>
    `).join('');

    const total = Cart.getTotalPrice();
    document.getElementById('checkout-subtotal').textContent = 'KES ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('checkout-total').textContent = 'KES ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

async function handleCheckout(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-order');
    btn.disabled = true;
    btn.innerText = 'Generating Code...';

    const payload = {
        customer_name: document.getElementById('customer_name').value,
        customer_phone: document.getElementById('customer_phone').value,
        customer_email: document.getElementById('customer_email').value,
        delivery_address: document.getElementById('delivery_address').value,
        city: document.getElementById('city').value,
        notes: document.getElementById('notes').value,
        items: Cart.getItems()
    };

    try {
        const response = await fetch('api/create-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const res = await response.json();

        if (res.success) {
            Cart.clear();
            window.location.href = res.redirect;
        } else {
            alert('Error: ' + (res.message || 'Unable to place order.'));
            btn.disabled = false;
            btn.innerText = '⚡ Generate Payment Code';
        }
    } catch (err) {
        console.error(err);
        alert('Network error placing order.');
        btn.disabled = false;
        btn.innerText = '⚡ Generate Payment Code';
    }
}

document.addEventListener('DOMContentLoaded', initCheckout);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

