<?php
define('PAGE_TITLE', 'Lipa Mdogo Mdogo Portal');
require_once __DIR__ . '/includes/header.php';

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['lmm_customer_id'], $_SESSION['lmm_customer_phone'], $_SESSION['lmm_customer_name']);
    header('Location: lipa-mdogo-mdogo.php');
    exit;
}

$isLoggedIn = !empty($_SESSION['lmm_customer_id']);
$customer = null;
$accounts = [];

if ($isLoggedIn) {
    $customer = getCustomerByPhone($_SESSION['lmm_customer_phone']);
    if ($customer) {
        $accounts = getCustomerInstallmentAccounts($customer['id']);
    } else {
        $isLoggedIn = false;
    }
}
?>

<div class="container" style="padding-top: 40px; padding-bottom: 80px;">
    <?php if (!$isLoggedIn): ?>
        <!-- Authentication / Phone Verification Card -->
        <div style="max-width: 540px; margin: 40px auto; background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 36px; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="display: inline-flex; width: 60px; height: 60px; border-radius: 50%; background: rgba(59, 130, 246, 0.15); align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 12px; border: 1px solid #3b82f6;">
                    📱
                </div>
                <h1 style="font-size: 1.8rem; font-weight: 800;">Lipa Mdogo Mdogo</h1>
                <p style="color: var(--text-secondary); font-size: 0.92rem; margin-top: 6px;">
                    Access your installment account, track your payment progress, and unlock your product.
                </p>
                <div style="margin-top: 10px;">
                    <button type="button" class="btn btn-secondary" style="font-size: 0.78rem; padding: 4px 10px;" onclick="useDemoPhone('0712345678')">
                        💡 Click to try demo registered phone: 0712345678
                    </button>
                </div>
            </div>

            <!-- Step 1: Request OTP -->
            <div id="step-phone-wrap">
                <div class="form-group">
                    <label class="form-label" for="phone_input">Registered Phone Number</label>
                    <input type="tel" id="phone_input" class="form-control" placeholder="e.g. 0712345678" style="font-size: 1.1rem; letter-spacing: 1px;">
                    <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 4px;">
                        Must be the phone number registered by your administrator.
                    </small>
                </div>
                <button type="button" id="btn-send-otp" class="btn btn-primary btn-block" style="padding: 12px;" onclick="requestOTP()">
                    Send Verification Code (OTP) →
                </button>
            </div>

            <!-- Simulated SMS Alert Banner -->
            <div id="sms-simulation-box" style="display: none; background: #0f271f; border: 1px solid #10b981; border-radius: 8px; padding: 14px; margin: 18px 0; font-size: 0.88rem; color: #a7f3d0;">
                <div style="font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                    <span>📩</span> SMS Delivered to <span id="sms-phone-target"></span>:
                </div>
                <div style="background: rgba(0,0,0,0.3); padding: 8px 12px; border-radius: 6px; font-family: monospace; font-size: 1rem; color: #fff;">
                    Your Outfit Lipa Mdogo Mdogo verification code is: <strong id="sms-code-val" style="color: #60a5fa; font-size: 1.2rem;"></strong>
                </div>
            </div>

            <!-- Step 2: Enter OTP -->
            <div id="step-otp-wrap" style="display: none;">
                <div class="form-group">
                    <label class="form-label" for="otp_input">Enter 6-Digit Verification Code</label>
                    <input type="text" id="otp_input" class="form-control" maxlength="6" placeholder="123456" style="font-size: 1.4rem; letter-spacing: 8px; text-align: center; font-family: monospace;">
                </div>
                <button type="button" id="btn-verify-otp" class="btn btn-success btn-block" style="padding: 12px;" onclick="submitOTP()">
                    ✓ Verify & Access Account
                </button>
                <div style="text-align: center; margin-top: 12px;">
                    <a href="javascript:void(0)" onclick="resetOTPForm()" style="font-size: 0.85rem; color: var(--text-secondary); text-decoration: underline;">
                        ← Change Phone Number
                    </a>
                </div>
            </div>

            <div id="auth-error-msg" style="display: none; margin-top: 16px; background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 10px 14px; border-radius: 6px; font-size: 0.85rem;"></div>
        </div>

    <?php else: ?>
        <!-- Authenticated Customer Dashboard -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="badge badge-primary" style="margin-bottom: 6px;">CUSTOMER PORTAL</span>
                <h1 style="font-size: 2rem; font-weight: 800;">Welcome, <?= htmlspecialchars($customer['full_name']) ?></h1>
                <p style="color: var(--text-secondary); font-size: 0.95rem;">
                    Phone: <strong><?= htmlspecialchars($customer['phone_number']) ?></strong> 
                    <?php if (!empty($customer['id_number'])): ?> | ID: <strong><?= htmlspecialchars($customer['id_number']) ?></strong><?php endif; ?>
                </p>
            </div>

            <div>
                <a href="lipa-mdogo-mdogo.php?logout=1" class="btn btn-secondary" style="font-size: 0.88rem;">
                    🚪 Log Out
                </a>
            </div>
        </div>

        <?php if (empty($accounts)): ?>
            <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 40px; text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 12px;">🛍️</div>
                <h3>No Active Installment Plans</h3>
                <p style="color: var(--text-secondary); max-width: 500px; margin: 10px auto 20px;">
                    You do not have any active Lipa Mdogo Mdogo installment plans. Please visit our store or contact an administrator to register an item for installments.
                </p>
                <a href="collection.php" class="btn btn-primary">Browse Collection</a>
            </div>
        <?php else: ?>
            <!-- Render Each Installment Agreement -->
            <?php foreach ($accounts as $acc): 
                $percent = $acc['progress_percent'];
            ?>
                <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 30px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.25);" id="acc-card-<?= $acc['account_number'] ?>">
                    
                    <!-- Card Header -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid var(--border); padding-bottom: 20px; margin-bottom: 24px;">
                        <div>
                            <span style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase;">Installment Agreement</span>
                            <div style="display: flex; align-items: center; gap: 12px; margin-top: 4px;">
                                <h2 style="font-family: monospace; font-size: 1.8rem; color: #60a5fa;"><?= htmlspecialchars($acc['account_number']) ?></h2>
                                <button type="button" class="btn-copy" onclick="copyToClipboard('<?= $acc['account_number'] ?>', this)">
                                    📋 Copy Account No
                                </button>
                            </div>
                        </div>

                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <?= getInstallmentStatusBadge($acc['status']) ?>
                            <?= getDeliveryStatusBadge($acc['delivery_status']) ?>
                        </div>
                    </div>

                    <!-- Main Agreement Body -->
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
                        <!-- Product View -->
                        <div>
                            <div style="border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--border); margin-bottom: 14px; background: #0c121e; height: 260px;">
                                <img src="<?= htmlspecialchars($acc['product_image']) ?>" alt="<?= htmlspecialchars($acc['product_name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <h3 style="font-size: 1.2rem; margin-bottom: 4px;"><?= htmlspecialchars($acc['product_name']) ?></h3>
                            <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 12px;"><?= htmlspecialchars($acc['product_category']) ?></p>
                            <div style="font-size: 1.3rem; font-weight: 800; color: #fff;">
                                Total: <?= formatMoney($acc['total_amount']) ?>
                            </div>
                        </div>

                        <!-- Progress & Payment Simulator -->
                        <div>
                            <!-- Visual Progress Bar -->
                            <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 20px; margin-bottom: 24px;">
                                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px;">
                                    <span style="font-size: 0.95rem; font-weight: 700; color: #fff;">Repayment Progress</span>
                                    <span id="prog-text-<?= $acc['account_number'] ?>" style="font-size: 1.25rem; font-weight: 800; color: <?= $percent >= 100 ? '#10b981' : '#60a5fa' ?>;">
                                        <?= $percent ?>% Completed
                                    </span>
                                </div>

                                <div style="width: 100%; height: 16px; background: #1e293b; border-radius: 10px; overflow: hidden; border: 1px solid #334155;">
                                    <div id="prog-bar-<?= $acc['account_number'] ?>" style="width: <?= $percent ?>%; height: 100%; background: <?= $percent >= 100 ? '#10b981' : 'linear-gradient(90deg, #3b82f6, #60a5fa)' ?>; transition: width 0.4s ease;"></div>
                                </div>

                                <!-- Financial Grid -->
                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 18px; text-align: center;">
                                    <div style="background: rgba(255,255,255,0.03); padding: 12px; border-radius: 8px;">
                                        <span style="color: var(--text-secondary); font-size: 0.78rem; text-transform: uppercase;">Deposit Req.</span>
                                        <div style="font-weight: 700; margin-top: 4px;"><?= formatMoney($acc['deposit_required']) ?></div>
                                        <span style="font-size: 0.75rem; color: <?= $acc['deposit_paid'] ? '#10b981' : '#f59e0b' ?>;">
                                            <?= $acc['deposit_paid'] ? '✓ Paid' : '⏳ Pending' ?>
                                        </span>
                                    </div>
                                    <div style="background: rgba(255,255,255,0.03); padding: 12px; border-radius: 8px;">
                                        <span style="color: var(--text-secondary); font-size: 0.78rem; text-transform: uppercase;">Amount Paid</span>
                                        <div id="paid-val-<?= $acc['account_number'] ?>" style="font-weight: 700; color: #10b981; margin-top: 4px; font-size: 1.1rem;">
                                            <?= formatMoney($acc['amount_paid']) ?>
                                        </div>
                                    </div>
                                    <div style="background: rgba(255,255,255,0.03); padding: 12px; border-radius: 8px;">
                                        <span style="color: var(--text-secondary); font-size: 0.78rem; text-transform: uppercase;">Remaining</span>
                                        <div id="bal-val-<?= $acc['account_number'] ?>" style="font-weight: 700; color: #f59e0b; margin-top: 4px; font-size: 1.1rem;">
                                            <?= formatMoney($acc['balance_remaining']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Installment Payment & Simulator -->
                            <div style="background: #111a2e; border: 1px dashed #3b82f6; border-radius: var(--radius-sm); padding: 20px; margin-bottom: 24px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <h4 style="font-size: 1.05rem; color: #60a5fa; display: flex; align-items: center; gap: 8px;">
                                        <span>💳</span> Pay Next Installment
                                    </h4>
                                    <span class="badge badge-primary">TEST SIMULATOR</span>
                                </div>
                                <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 12px;">
                                    To pay via M-PESA, use Paybill <strong><?= PAYMENT_BUSINESS_NUMBER ?></strong> with Account: <strong style="color: #60a5fa;"><?= $acc['account_number'] ?></strong>.
                                    Or simulate an installment below:
                                </p>

                                <?php if ($acc['balance_remaining'] > 0): ?>
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                        <button type="button" class="btn btn-secondary" style="font-size: 0.85rem;" onclick="simulateInstallment('<?= $acc['account_number'] ?>', 250)">
                                            + KES 250
                                        </button>
                                        <button type="button" class="btn btn-secondary" style="font-size: 0.85rem;" onclick="simulateInstallment('<?= $acc['account_number'] ?>', 500)">
                                            + KES 500
                                        </button>
                                        <button type="button" class="btn btn-secondary" style="font-size: 0.85rem;" onclick="simulateInstallment('<?= $acc['account_number'] ?>', 1000)">
                                            + KES 1,000
                                        </button>
                                        <button type="button" class="btn btn-success" style="font-size: 0.85rem;" onclick="simulateInstallment('<?= $acc['account_number'] ?>', <?= $acc['balance_remaining'] ?>)">
                                            ✓ Pay Balance (<?= formatMoney($acc['balance_remaining']) ?>)
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div style="background: rgba(16,185,129,0.15); border: 1px solid #10b981; color: #6ee7b7; padding: 12px; border-radius: 6px; font-size: 0.9rem;">
                                        🎉 <strong>Congratulations!</strong> This product is fully paid. It is now marked as <strong>READY FOR COLLECTION</strong>.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Payment Statement History -->
                            <div>
                                <h4 style="font-size: 1rem; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>Installment Payment Statement</span>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);"><?= count($acc['payments']) ?> payment(s)</span>
                                </h4>
                                <div class="table-wrap">
                                    <table class="data-table" style="font-size: 0.85rem;">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Reference</th>
                                                <th>Channel</th>
                                                <th>Amount</th>
                                                <th>Note</th>
                                            </tr>
                                        </thead>
                                        <tbody id="pay-table-<?= $acc['account_number'] ?>">
                                            <?php foreach ($acc['payments'] as $p): ?>
                                                <tr>
                                                    <td><?= date('M d, Y H:i', strtotime($p['created_at'])) ?></td>
                                                    <td style="font-family: monospace; font-weight: 600; color: #60a5fa;"><?= htmlspecialchars($p['payment_reference']) ?></td>
                                                    <td><span class="badge badge-primary"><?= strtoupper($p['payment_method']) ?></span></td>
                                                    <td style="font-weight: 700; color: #10b981;"><?= formatMoney($p['amount']) ?></td>
                                                    <td style="color: var(--text-secondary);"><?= htmlspecialchars($p['notes'] ?: '-') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
let activePhone = '';

function useDemoPhone(phone) {
    document.getElementById('phone_input').value = phone;
}

async function requestOTP() {
    const phone = document.getElementById('phone_input').value.trim();
    const btn = document.getElementById('btn-send-otp');
    const errBox = document.getElementById('auth-error-msg');
    errBox.style.display = 'none';

    if (!phone) {
        alert('Please enter your registered phone number.');
        return;
    }

    btn.disabled = true;
    btn.innerText = 'Sending SMS...';

    try {
        const response = await fetch('api/otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'send', phone: phone })
        });
        const res = await response.json();

        if (res.success) {
            activePhone = phone;
            document.getElementById('step-phone-wrap').style.display = 'none';
            document.getElementById('step-otp-wrap').style.display = 'block';

            // Show demo SMS alert with the code
            const smsBox = document.getElementById('sms-simulation-box');
            smsBox.style.display = 'block';
            document.getElementById('sms-phone-target').innerText = phone;
            document.getElementById('sms-code-val').innerText = res.demo_otp;

            // Auto-fill OTP in input for convenience
            document.getElementById('otp_input').value = res.demo_otp;
        } else {
            errBox.innerText = res.message;
            errBox.style.display = 'block';
            btn.disabled = false;
            btn.innerText = 'Send Verification Code (OTP) →';
        }
    } catch (e) {
        errBox.innerText = 'Network error while requesting code.';
        errBox.style.display = 'block';
        btn.disabled = false;
        btn.innerText = 'Send Verification Code (OTP) →';
    }
}

async function submitOTP() {
    const code = document.getElementById('otp_input').value.trim();
    const btn = document.getElementById('btn-verify-otp');
    const errBox = document.getElementById('auth-error-msg');
    errBox.style.display = 'none';

    if (!code) {
        alert('Please enter the 6-digit code.');
        return;
    }

    btn.disabled = true;
    btn.innerText = 'Verifying...';

    try {
        const response = await fetch('api/otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'verify', phone: activePhone, code: code })
        });
        const res = await response.json();

        if (res.success) {
            window.location.reload();
        } else {
            errBox.innerText = res.message;
            errBox.style.display = 'block';
            btn.disabled = false;
            btn.innerText = '✓ Verify & Access Account';
        }
    } catch (e) {
        errBox.innerText = 'Network error while verifying code.';
        errBox.style.display = 'block';
        btn.disabled = false;
        btn.innerText = '✓ Verify & Access Account';
    }
}

function resetOTPForm() {
    document.getElementById('step-phone-wrap').style.display = 'block';
    document.getElementById('step-otp-wrap').style.display = 'none';
    document.getElementById('sms-simulation-box').style.display = 'none';
    document.getElementById('btn-send-otp').disabled = false;
    document.getElementById('btn-send-otp').innerText = 'Send Verification Code (OTP) →';
}

async function simulateInstallment(accountNumber, amount) {
    try {
        const response = await fetch('api/simulate-installment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                account_number: accountNumber,
                amount: amount,
                notes: 'Self-service installment payment'
            })
        });
        const res = await response.json();

        if (res.success) {
            showToast(res.message, 'success');
            // Refresh view
            setTimeout(() => window.location.reload(), 1000);
        } else {
            alert('Simulation error: ' + res.message);
        }
    } catch (e) {
        alert('Network error simulating installment.');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

