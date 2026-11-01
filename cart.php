<?php
define('PAGE_TITLE', 'Shopping Cart');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 40px; padding-bottom: 80px;">
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 2rem; font-weight: 800;">Shopping Cart</h1>
        <p style="color: var(--text-secondary);">Review your selected items before proceeding to code generation.</p>
    </div>

    <div id="cart-empty-view" style="display: none; text-align: center; padding: 60px 20px; background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border);">
        <div style="font-size: 3rem; margin-bottom: 16px;">🛍️</div>
        <h2 style="margin-bottom: 12px;">Your Cart is Empty</h2>
        <p style="color: var(--text-secondary); margin-bottom: 24px;">Explore our catalog and pick something fresh.</p>
        <a href="index.php" class="btn btn-primary">Browse Catalogue</a>
    </div>

    <div id="cart-content-view" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 16px;">
                <h3 style="font-size: 1.15rem;">Items in Cart</h3>
                <button type="button" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.8rem;" onclick="Cart.clear(); renderCart();">Clear Cart</button>
            </div>
            <div id="cart-items-list">
                <!-- Dynamically rendered -->
            </div>
        </div>

        <div>
            <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; position: sticky; top: 90px;">
                <h3 style="font-size: 1.2rem; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">Order Summary</h3>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; color: var(--text-secondary);">
                    <span>Subtotal</span>
                    <span id="cart-subtotal" style="color: #fff; font-weight: 600;">KES 0.00</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 16px; color: var(--text-secondary);">
                    <span>Estimated Shipping</span>
                    <span style="color: #10b981; font-weight: 600;">Free Delivery</span>
                </div>

                <div style="border-top: 1px solid var(--border); padding-top: 16px; margin-top: 16px; display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 24px;">
                    <span style="font-size: 1.1rem; font-weight: 600;">Total</span>
                    <span id="cart-total" style="font-size: 1.5rem; font-weight: 800; color: #60a5fa;">KES 0.00</span>
                </div>

                <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; padding: 12px; margin-bottom: 20px; font-size: 0.85rem; color: #93c5fd;">
                    ℹ You will receive a unique order code on the next step to make your payment.
                </div>

                <a href="checkout.php" class="btn btn-primary btn-block" style="padding: 14px;">
                    Proceed to Checkout →
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function renderCart() {
    const items = Cart.getItems();
    const emptyView = document.getElementById('cart-empty-view');
    const contentView = document.getElementById('cart-content-view');
    const list = document.getElementById('cart-items-list');

    if (items.length === 0) {
        emptyView.style.display = 'block';
        contentView.style.display = 'none';
        return;
    }

    emptyView.style.display = 'none';
    contentView.style.display = 'grid';

    list.innerHTML = items.map(item => `
        <div style="display: flex; gap: 16px; padding: 16px 0; border-bottom: 1px solid var(--border); align-items: center;">
            <img src="${item.image_url}" alt="${item.name}" style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">
            <div style="flex-grow: 1;">
                <h4 style="font-size: 1rem; margin-bottom: 4px;">${item.name}</h4>
                <div style="color: var(--text-secondary); font-size: 0.9rem;">KES ${parseFloat(item.price).toLocaleString()}</div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="btn btn-secondary" style="padding: 4px 10px;" onclick="Cart.updateQuantity(${item.id}, ${item.quantity - 1}); renderCart();">-</button>
                <span style="font-weight: 600; width: 24px; text-align: center;">${item.quantity}</span>
                <button type="button" class="btn btn-secondary" style="padding: 4px 10px;" onclick="Cart.updateQuantity(${item.id}, ${item.quantity + 1}); renderCart();">+</button>
            </div>
            <div style="min-width: 90px; text-align: right; font-weight: 700;">
                KES ${(item.price * item.quantity).toLocaleString()}
            </div>
            <div>
                <button type="button" onclick="Cart.removeItem(${item.id}); renderCart();" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 6px;">✕</button>
            </div>
        </div>
    `).join('');

    const total = Cart.getTotalPrice();
    document.getElementById('cart-subtotal').textContent = 'KES ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('cart-total').textContent = 'KES ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

document.addEventListener('DOMContentLoaded', renderCart);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

