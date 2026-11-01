<?php
define('PAGE_TITLE', 'Checkout & Payment Method');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 40px; padding-bottom: 80px;">
    <div style="margin-bottom: 28px;">
        <h1 style="font-size: 2rem; font-weight: 800;">Order Checkout</h1>
        <p style="color: var(--text-secondary);">Select your preferred payment method below to get started.</p>
    </div>

    <form id="checkout-form" onsubmit="handleCheckout(event)" style="display: grid; grid-template-columns: 3fr 2fr; gap: 30px;">
        <div>
            
            <!-- STEP 1: SELECT PAYMENT METHOD FIRST -->
            <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px; margin-bottom: 24px;">
                <h3 style="font-size: 1.25rem; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <span>💳</span> 1. Select Payment Method
                </h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    
                    <!-- Option A: Pay In Full -->
                    <label id="card-mode-full" style="display: block; cursor: pointer; padding: 20px; border-radius: var(--radius-sm); border: 2px solid #3b82f6; background: rgba(59, 130, 246, 0.08); transition: all 0.2s;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="radio" name="payment_mode" value="full" checked onchange="onPaymentMethodChanged('full')">
                                <strong style="font-size: 1.05rem; color: #fff;">Pay in Full</strong>
                            </div>
                            <span class="badge badge-primary" style="font-size: 0.7rem;">ONE-TIME</span>
                        </div>
                        <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.4;">
                            Generate an instant unique order code. Pay 100% upfront via M-PESA or Bank and track immediate delivery.
                        </p>
                    </label>

                    <!-- Option B: Lipa Mdogo Mdogo -->
                    <label id="card-mode-lmm" style="display: block; cursor: pointer; padding: 20px; border-radius: var(--radius-sm); border: 2px solid var(--border); background: var(--bg-secondary); transition: all 0.2s;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="radio" name="payment_mode" value="lipa_mdogo" onchange="onPaymentMethodChanged('lipa_mdogo')">
                                <strong style="font-size: 1.05rem; color: #60a5fa;">Lipa Mdogo Mdogo</strong>
                            </div>
                            <span class="badge badge-accent" style="font-size: 0.7rem;">INSTALLMENTS</span>
                        </div>
                        <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.4;">
                            Pay in flexible installments over time. Requires prior customer registration by a store agent.
                        </p>
                    </label>

                </div>
            </div>

            <!-- STEP 1.5: LIPA MDOGO MDOGO PHONE REGISTRATION VERIFICATION -->
            <div id="lmm-verification-box" style="display: none; background: var(--bg-card); border: 1px solid #3b82f6; border-radius: var(--radius); padding: 28px; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
                    <h3 style="font-size: 1.2rem; color: #60a5fa; display: flex; align-items: center; gap: 8px;">
                        <span>📱</span> Lipa Mdogo Mdogo Customer Verification
                    </h3>
                    <span class="badge badge-primary">AGENT REGISTRATION REQUIRED</span>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 16px;">
                    Lipa Mdogo Mdogo is available to pre-registered customers. Please enter your registered phone number to verify your account in our database.
                </p>

                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                    <input type="tel" id="lmm_check_phone" class="form-control" placeholder="Enter phone number (e.g. 0712345678)" style="font-size: 1.05rem; letter-spacing: 0.5px;">
                    <button type="button" id="btn-verify-phone" class="btn btn-primary" onclick="verifyCustomerPhone()" style="padding: 10px 20px; white-space: nowrap;">
                        Verify Number →
                    </button>
                </div>

                <div style="margin-bottom: 14px;">
                    <button type="button" class="btn btn-secondary" style="font-size: 0.78rem; padding: 4px 10px;" onclick="useDemoPhone('0712345678')">
                        💡 Test with registered phone: 0712345678
                    </button>
                    <button type="button" class="btn btn-secondary" style="font-size: 0.78rem; padding: 4px 10px; margin-left: 6px;" onclick="useDemoPhone('0799000111')">
                        Test unregistered phone: 0799000111
                    </button>
                </div>

                <!-- Checking state -->
                <div id="lmm-loading" style="display: none; padding: 12px; font-size: 0.88rem; color: #60a5fa;">
                    ⏳ Checking database registration...
                </div>

                <!-- CASE 1: NOT FOUND IN DATABASE -> PROMPT TO REACH AGENT -->
                <div id="lmm-prompt-agent" style="display: none; background: rgba(245, 158, 11, 0.12); border: 2px solid #f59e0b; border-radius: var(--radius-sm); padding: 22px; margin-top: 14px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span style="font-size: 1.5rem;">⚠️</span>
                        <h4 style="font-size: 1.15rem; color: #fde68a;">Phone Number Not Found in Database</h4>
                    </div>
                    <p style="color: #cbd5e1; font-size: 0.9rem; line-height: 1.5; margin-bottom: 16px;">
                        The phone number <strong id="prompt-unregistered-phone" style="color: #fff; font-family: monospace;"></strong> is not yet registered for Lipa Mdogo Mdogo installment plans.
                    </p>

                    <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                        <h5 style="color: #fde68a; font-size: 0.95rem; margin-bottom: 8px;">📞 Please Contact an Agent for Registration:</h5>
                        <ul style="list-style: none; font-size: 0.88rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 6px;">
                            <li>• <strong>Store Agent Phone / WhatsApp:</strong> <span style="color: #fff;">+254 712 345 678</span></li>
                            <li>• <strong>Customer Service Desk:</strong> <span style="color: #fff;">Available 8:00 AM - 6:00 PM</span></li>
                            <li>• <strong>Registration Requirement:</strong> Provide your Full Name & National ID number to the agent.</li>
                        </ul>
                    </div>

                    <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 16px;">
                        <strong>Once added to the system by an agent</strong>, simply click "Verify Number" above to proceed with your purchase.
                    </p>

                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="admin/lipa-mdogo.php#register-customer-section" target="_blank" class="btn btn-secondary" style="font-size: 0.82rem;">
                            ⚙ Open Admin Agent Registration Desk ↗
                        </a>
                        <button type="button" class="btn btn-secondary" style="font-size: 0.82rem;" onclick="onPaymentMethodChanged('full')">
                            Switch to Pay in Full Instead
                        </button>
                    </div>
                </div>

                <!-- CASE 2: VERIFIED IN DATABASE -> PROCEED -->
                <div id="lmm-verified-box" style="display: none; background: rgba(16, 185, 129, 0.12); border: 2px solid #10b981; border-radius: var(--radius-sm); padding: 22px; margin-top: 14px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                        <span style="font-size: 1.5rem; color: #10b981;">✓</span>
                        <h4 style="font-size: 1.15rem; color: #6ee7b7;">Registration Verified in Database!</h4>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 14px;">
                        Welcome back, <strong id="verified-name" style="color: #fff;"></strong>! Your account is active and eligible for Lipa Mdogo Mdogo installment agreements.
                    </p>

                    <!-- Installment Terms Breakdown -->
                    <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 14px; font-size: 0.88rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; color: var(--text-secondary);">
                            <span>Total Item Value:</span>
                            <strong id="lmm-verified-total" style="color: #fff;">KES 0.00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; color: var(--text-secondary);">
                            <span>Initial Deposit Due (20%):</span>
                            <strong id="lmm-verified-deposit" style="color: #60a5fa;">KES 0.00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; color: var(--text-secondary);">
                            <span>Remaining Installments Balance:</span>
                            <strong id="lmm-verified-balance" style="color: #f59e0b;">KES 0.00</strong>
                        </div>
                    </div>
                    <p style="font-size: 0.82rem; color: #6ee7b7; margin-top: 10px;">
                        ✓ You may now complete delivery details below and proceed with your purchase.
                    </p>
                </div>

            </div>

            <!-- STEP 2: DELIVERY DETAILS (LOCKED IF LMM & UNVERIFIED) -->
            <div id="delivery-details-section" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px;">
                <h3 style="font-size: 1.25rem; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <span>📍</span> 2. Delivery & Recipient Details
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

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="city">Town / City</label>
                        <input type="text" id="city" class="form-control" value="Nairobi" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="notes">Delivery Notes (Optional)</label>
                        <input type="text" id="notes" class="form-control" placeholder="e.g. Ring the bell on arrival">
                    </div>
                </div>

                <div id="full-pay-banner" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 14px; margin-top: 10px; font-size: 0.88rem; color: #6ee7b7;">
                    🔒 <strong>No Card Details Required:</strong> On clicking "Generate Payment Code", your items are reserved and an instant Paybill reference code will be generated for you.
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: ORDER SUMMARY & SUBMIT -->
        <div>
            <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; position: sticky; top: 90px;">
                <h3 style="font-size: 1.2rem; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                    Order Summary
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
                    <span style="font-size: 1.1rem; font-weight: 700;" id="label-total-payable">Total Payable</span>
                    <span id="checkout-total" style="font-size: 1.5rem; font-weight: 800; color: #60a5fa;">KES 0.00</span>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="btn-submit-order" class="btn btn-primary btn-block" style="padding: 14px; font-size: 1.05rem;">
                    ⚡ Generate Payment Code
                </button>

                <div id="btn-blocked-hint" style="display: none; text-align: center; margin-top: 10px; font-size: 0.82rem; color: #f59e0b;">
                    ⚠️ Please verify your registered phone number above to proceed.
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let selectedMethod = 'full';
let isLmmVerified = false;

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

    updateLmmPreviews(total);
}

function updateLmmPreviews(total) {
    const deposit = Math.max(500, Math.round(total * 0.20));
    const balance = Math.max(0, total - deposit);
    
    document.getElementById('lmm-verified-total').textContent = 'KES ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('lmm-verified-deposit').textContent = 'KES ' + deposit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('lmm-verified-balance').textContent = 'KES ' + balance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function onPaymentMethodChanged(method) {
    selectedMethod = method;
    const cardFull = document.getElementById('card-mode-full');
    const cardLmm = document.getElementById('card-mode-lmm');
    const lmmVerificationBox = document.getElementById('lmm-verification-box');
    const deliverySection = document.getElementById('delivery-details-section');
    const fullPayBanner = document.getElementById('full-pay-banner');
    const btnSubmit = document.getElementById('btn-submit-order');
    const blockedHint = document.getElementById('btn-blocked-hint');
    const totalLabel = document.getElementById('label-total-payable');

    // Update radio inputs
    document.querySelector('input[name="payment_mode"][value="full"]').checked = (method === 'full');
    document.querySelector('input[name="payment_mode"][value="lipa_mdogo"]').checked = (method === 'lipa_mdogo');

    if (method === 'full') {
        // UI styling
        cardFull.style.borderColor = '#3b82f6';
        cardFull.style.background = 'rgba(59, 130, 246, 0.08)';
        cardLmm.style.borderColor = 'var(--border)';
        cardLmm.style.background = 'var(--bg-secondary)';

        // Show standard delivery section
        lmmVerificationBox.style.display = 'none';
        deliverySection.style.display = 'block';
        deliverySection.style.opacity = '1';
        deliverySection.style.pointerEvents = 'auto';
        fullPayBanner.style.display = 'block';

        btnSubmit.disabled = false;
        btnSubmit.innerText = '⚡ Generate Payment Code';
        btnSubmit.className = 'btn btn-primary btn-block';
        blockedHint.style.display = 'none';
        totalLabel.innerText = 'Total Payable';
    } else {
        // Lipa Mdogo Mdogo chosen
        cardLmm.style.borderColor = '#3b82f6';
        cardLmm.style.background = 'rgba(59, 130, 246, 0.08)';
        cardFull.style.borderColor = 'var(--border)';
        cardFull.style.background = 'var(--bg-secondary)';

        lmmVerificationBox.style.display = 'block';
        fullPayBanner.style.display = 'none';
        totalLabel.innerText = 'Product Value';

        if (isLmmVerified) {
            // Already verified
            deliverySection.style.display = 'block';
            deliverySection.style.opacity = '1';
            deliverySection.style.pointerEvents = 'auto';
            btnSubmit.disabled = false;
            btnSubmit.innerText = '📱 Proceed with Lipa Mdogo Mdogo Purchase →';
            btnSubmit.className = 'btn btn-success btn-block';
            blockedHint.style.display = 'none';
        } else {
            // Need verification first: lock delivery section and submit button
            deliverySection.style.opacity = '0.5';
            deliverySection.style.pointerEvents = 'none';
            btnSubmit.disabled = true;
            btnSubmit.innerText = '📱 Verification Required';
            btnSubmit.className = 'btn btn-secondary btn-block';
            blockedHint.style.display = 'block';
        }
    }
}

function useDemoPhone(phone) {
    document.getElementById('lmm_check_phone').value = phone;
    verifyCustomerPhone();
}

async function verifyCustomerPhone() {
    const phone = document.getElementById('lmm_check_phone').value.trim();
    const loading = document.getElementById('lmm-loading');
    const promptAgent = document.getElementById('lmm-prompt-agent');
    const verifiedBox = document.getElementById('lmm-verified-box');
    const deliverySection = document.getElementById('delivery-details-section');
    const btnSubmit = document.getElementById('btn-submit-order');
    const blockedHint = document.getElementById('btn-blocked-hint');

    if (!phone) {
        alert('Please enter your phone number to check registration.');
        return;
    }

    loading.style.display = 'block';
    promptAgent.style.display = 'none';
    verifiedBox.style.display = 'none';

    try {
        const response = await fetch(`api/check-customer.php?phone=${encodeURIComponent(phone)}`);
        const res = await response.json();
        loading.style.display = 'none';

        if (res.registered) {
            // Found in DB
            isLmmVerified = true;
            verifiedBox.style.display = 'block';
            promptAgent.style.display = 'none';
            document.getElementById('verified-name').innerText = res.customer.full_name;

            // Pre-fill delivery details with verified customer data
            document.getElementById('customer_phone').value = res.customer.phone_number;
            document.getElementById('customer_name').value = res.customer.full_name;
            if (res.customer.delivery_address) {
                document.getElementById('delivery_address').value = res.customer.delivery_address;
            }
            if (res.customer.city) {
                document.getElementById('city').value = res.customer.city;
            }

            // Unlock delivery section & submit button
            deliverySection.style.opacity = '1';
            deliverySection.style.pointerEvents = 'auto';
            btnSubmit.disabled = false;
            btnSubmit.innerText = '📱 Proceed with Lipa Mdogo Mdogo Purchase →';
            btnSubmit.className = 'btn btn-success btn-block';
            blockedHint.style.display = 'none';
        } else {
            // Not found in DB -> Prompt to reach agent
            isLmmVerified = false;
            verifiedBox.style.display = 'none';
            promptAgent.style.display = 'block';
            document.getElementById('prompt-unregistered-phone').innerText = res.phone || phone;

            // Keep delivery section locked
            deliverySection.style.opacity = '0.5';
            deliverySection.style.pointerEvents = 'none';
            btnSubmit.disabled = true;
            btnSubmit.innerText = '📱 Verification Required';
            btnSubmit.className = 'btn btn-secondary btn-block';
            blockedHint.style.display = 'block';
        }
    } catch (e) {
        loading.style.display = 'none';
        alert('Network error checking phone number.');
    }
}

async function handleCheckout(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-order');

    // If Lipa Mdogo Mdogo is selected, ensure customer is verified
    if (selectedMethod === 'lipa_mdogo' && !isLmmVerified) {
        alert('Please verify your registered phone number first. If not registered, contact a store agent.');
        document.getElementById('lmm_check_phone').focus();
        return;
    }

    btn.disabled = true;
    btn.innerText = selectedMethod === 'lipa_mdogo' ? 'Setting up Installment Plan...' : 'Generating Code...';

    const payload = {
        payment_mode: selectedMethod,
        customer_name: document.getElementById('customer_name').value.trim(),
        customer_phone: (selectedMethod === 'lipa_mdogo') ? document.getElementById('lmm_check_phone').value.trim() : document.getElementById('customer_phone').value.trim(),
        customer_email: document.getElementById('customer_email').value.trim(),
        delivery_address: document.getElementById('delivery_address').value.trim(),
        city: document.getElementById('city').value.trim(),
        notes: document.getElementById('notes').value.trim(),
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
            btn.innerText = selectedMethod === 'lipa_mdogo' ? '📱 Proceed with Lipa Mdogo Mdogo Purchase →' : '⚡ Generate Payment Code';
        }
    } catch (err) {
        console.error(err);
        alert('Network error placing order.');
        btn.disabled = false;
        btn.innerText = selectedMethod === 'lipa_mdogo' ? '📱 Proceed with Lipa Mdogo Mdogo Purchase →' : '⚡ Generate Payment Code';
    }
}

document.addEventListener('DOMContentLoaded', initCheckout);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
