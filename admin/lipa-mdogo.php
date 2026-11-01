<?php
define('ADMIN_PAGE_TITLE', 'Lipa Mdogo Mdogo Management');
require_once __DIR__ . '/header.php';

$pdo = getDatabaseConnection();
$message = '';
$messageType = 'success';

// Handle Customer Registration POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_register_customer'])) {
    $phone = normalizePhoneNumber($_POST['phone_number'] ?? '');
    $name = sanitize($_POST['full_name'] ?? '');
    $idNum = sanitize($_POST['id_number'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $address = sanitize($_POST['delivery_address'] ?? '');

    if ($phone && $name) {
        try {
            $stmt = $pdo->prepare("INSERT INTO customers (phone_number, full_name, id_number, email, delivery_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$phone, $name, $idNum, $email, $address]);
            $message = "Customer {$name} ({$phone}) registered successfully!";
        } catch (Exception $e) {
            $message = "Error registering customer: " . $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = "Customer name and phone number are required.";
        $messageType = 'danger';
    }
}

// Handle Installment Account Creation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_create_account'])) {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);
    $depositRequired = (float)($_POST['deposit_required'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');

    if ($customerId && $productId) {
        $pStmt = $pdo->prepare("SELECT price, name FROM products WHERE id = ?");
        $pStmt->execute([$productId]);
        $prod = $pStmt->fetch();

        if ($prod) {
            $totalAmount = (float)$prod['price'];
            $accNumber = generateLMMAccountNumber();

            // Insert account
            $instStmt = $pdo->prepare("
                INSERT INTO installment_accounts (
                    account_number, customer_id, product_id, total_amount, 
                    deposit_required, deposit_paid, amount_paid, balance_remaining, 
                    status, delivery_status, notes
                ) VALUES (
                    ?, ?, ?, ?, 
                    ?, 0, 0.00, ?, 
                    'active', 'locked', ?
                )
            ");
            $instStmt->execute([
                $accNumber, $customerId, $productId, $totalAmount,
                $depositRequired, $totalAmount, $notes
            ]);

            $message = "Lipa Mdogo Mdogo Account {$accNumber} created for {$prod['name']}!";
        }
    }
}

// Handle Manual Payment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_record_payment'])) {
    $accNum = strtoupper(trim($_POST['account_number'] ?? ''));
    $amount = (float)($_POST['amount'] ?? 0);
    $ref = sanitize($_POST['payment_reference'] ?? '');
    $notes = sanitize($_POST['payment_notes'] ?? 'Manual admin credit');

    $res = recordInstallmentPayment($accNum, $amount, $ref, 'cash_office', $notes);
    if ($res['success']) {
        $message = "Payment of " . formatMoney($amount) . " credited to account {$accNum}!";
    } else {
        $message = "Payment failed: " . $res['message'];
        $messageType = 'danger';
    }
}

// Handle Status & Delivery Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_delivery'])) {
    $accId = (int)($_POST['account_id'] ?? 0);
    $newDelivery = sanitize($_POST['delivery_status'] ?? 'locked');
    $stmt = $pdo->prepare("UPDATE installment_accounts SET delivery_status = ? WHERE id = ?");
    $stmt->execute([$newDelivery, $accId]);
    $message = "Delivery status updated to: " . strtoupper($newDelivery);
}

// Load summary metrics
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn() ?: 0;
$totalLmmAccounts = $pdo->query("SELECT COUNT(*) FROM installment_accounts")->fetchColumn() ?: 0;
$totalLmmPaid = $pdo->query("SELECT SUM(amount_paid) FROM installment_accounts")->fetchColumn() ?: 0.00;
$totalLmmRemaining = $pdo->query("SELECT SUM(balance_remaining) FROM installment_accounts WHERE status = 'active'")->fetchColumn() ?: 0.00;

// Load customers and products for dropdowns
$customersList = $pdo->query("SELECT id, full_name, phone_number FROM customers ORDER BY full_name ASC")->fetchAll();
$productsList = $pdo->query("SELECT id, name, price FROM products WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// Load all installment accounts
$stmt = $pdo->query("
    SELECT a.*, c.full_name AS customer_name, c.phone_number AS customer_phone, p.name AS product_name
    FROM installment_accounts a
    JOIN customers c ON a.customer_id = c.id
    JOIN products p ON a.product_id = p.id
    ORDER BY a.created_at DESC
");
$allAccounts = $stmt->fetchAll();

// View specific account if requested
$inspectAcc = null;
if (!empty($_GET['view'])) {
    $inspectAcc = getInstallmentAccountDetails($_GET['view']);
}
?>

<div class="container" style="padding-top: 36px; padding-bottom: 60px;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 800;">Lipa Mdogo Mdogo Management</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">Manage installment registrations, customer accounts, and collections.</p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="#register-customer-section" class="btn btn-primary" style="font-size: 0.88rem;">
                + Register Customer
            </a>
            <a href="#create-plan-section" class="btn btn-secondary" style="font-size: 0.88rem;">
                + New Installment Plan
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div style="padding: 14px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; background: <?= $messageType === 'danger' ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.15)' ?>; border: 1px solid <?= $messageType === 'danger' ? '#ef4444' : '#10b981' ?>; color: <?= $messageType === 'danger' ? '#fca5a5' : '#6ee7b7' ?>;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Summary Metrics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px;">
        <div class="stat-card">
            <span style="color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase;">Installment Revenue</span>
            <div class="stat-val" style="color: #10b981;"><?= formatMoney($totalLmmPaid) ?></div>
        </div>
        <div class="stat-card">
            <span style="color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase;">Outstanding Balances</span>
            <div class="stat-val" style="color: #f59e0b;"><?= formatMoney($totalLmmRemaining) ?></div>
        </div>
        <div class="stat-card">
            <span style="color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase;">Active Plans</span>
            <div class="stat-val"><?= number_format($totalLmmAccounts) ?></div>
        </div>
        <div class="stat-card">
            <span style="color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase;">Registered Customers</span>
            <div class="stat-val" style="color: #60a5fa;"><?= number_format($totalCustomers) ?></div>
        </div>
    </div>

    <?php if ($inspectAcc): ?>
        <!-- Account Inspector -->
        <div style="background: var(--bg-card); border: 2px solid #3b82f6; border-radius: var(--radius); padding: 28px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 20px;">
                <div>
                    <span style="font-size: 0.82rem; color: var(--text-secondary);">INSPECTING LIPA MDOGO MDOGO ACCOUNT</span>
                    <h2 style="font-family: monospace; color: #60a5fa; font-size: 1.6rem;"><?= htmlspecialchars($inspectAcc['account_number']) ?></h2>
                </div>
                <div>
                    <a href="lipa-mdogo.php" class="btn btn-secondary" style="font-size: 0.85rem;">✕ Close Inspector</a>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
                <div>
                    <h4>Agreement Details</h4>
                    <p style="margin-top: 6px;"><strong>Customer:</strong> <?= htmlspecialchars($inspectAcc['customer_name']) ?> (Phone: <?= htmlspecialchars($inspectAcc['customer_phone']) ?>, ID: <?= htmlspecialchars($inspectAcc['id_number'] ?: 'N/A') ?>)</p>
                    <p><strong>Product:</strong> <?= htmlspecialchars($inspectAcc['product_name']) ?> (Total: <?= formatMoney($inspectAcc['total_amount']) ?>)</p>
                    <p><strong>Deposit Required:</strong> <?= formatMoney($inspectAcc['deposit_required']) ?> (<?= $inspectAcc['deposit_paid'] ? '✓ Paid' : '⏳ Pending' ?>)</p>
                    <p><strong>Amount Paid:</strong> <strong style="color: #10b981;"><?= formatMoney($inspectAcc['amount_paid']) ?></strong> | <strong>Remaining:</strong> <strong style="color: #f59e0b;"><?= formatMoney($inspectAcc['balance_remaining']) ?></strong></p>
                    <p><strong>Repayment Progress:</strong> <?= $inspectAcc['progress_percent'] ?>%</p>

                    <h4 style="margin-top: 20px; margin-bottom: 10px;">Payment History (<?= count($inspectAcc['payments']) ?>)</h4>
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
                            <tbody>
                                <?php if (empty($inspectAcc['payments'])): ?>
                                    <tr><td colspan="5" style="text-align: center; color: var(--text-secondary);">No payments recorded yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($inspectAcc['payments'] as $p): ?>
                                        <tr>
                                            <td><?= date('M d, Y H:i', strtotime($p['created_at'])) ?></td>
                                            <td style="font-family: monospace; font-weight: 600; color: #60a5fa;"><?= htmlspecialchars($p['payment_reference']) ?></td>
                                            <td><span class="badge badge-primary"><?= strtoupper($p['payment_method']) ?></span></td>
                                            <td style="font-weight: 700; color: #10b981;"><?= formatMoney($p['amount']) ?></td>
                                            <td><?= htmlspecialchars($p['notes']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Admin Action Panels -->
                <div>
                    <!-- Record Offline Payment -->
                    <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h4 style="margin-bottom: 12px; font-size: 0.95rem;">Record Offline / Cash Payment</h4>
                        <form method="POST">
                            <input type="hidden" name="action_record_payment" value="1">
                            <input type="hidden" name="account_number" value="<?= htmlspecialchars($inspectAcc['account_number']) ?>">
                            <div class="form-group">
                                <label class="form-label">Amount (KES)</label>
                                <input type="number" step="0.01" name="amount" class="form-control" placeholder="500.00" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Receipt / Reference</label>
                                <input type="text" name="payment_reference" class="form-control" placeholder="CASH-001 or M-PESA Code">
                            </div>
                            <button type="submit" class="btn btn-success btn-block" style="padding: 10px;">
                                Credit Installment
                            </button>
                        </form>
                    </div>

                    <!-- Update Product Release -->
                    <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; padding: 20px;">
                        <h4 style="margin-bottom: 12px; font-size: 0.95rem;">Release / Delivery Status</h4>
                        <form method="POST">
                            <input type="hidden" name="action_update_delivery" value="1">
                            <input type="hidden" name="account_id" value="<?= $inspectAcc['id'] ?>">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="delivery_status" class="form-control">
                                    <option value="locked" <?= $inspectAcc['delivery_status'] === 'locked' ? 'selected' : '' ?>>Locked (Pending Repayment)</option>
                                    <option value="ready_for_collection" <?= $inspectAcc['delivery_status'] === 'ready_for_collection' ? 'selected' : '' ?>>Ready for Collection</option>
                                    <option value="delivered" <?= $inspectAcc['delivery_status'] === 'delivered' ? 'selected' : '' ?>>Collected / Delivered</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block" style="padding: 10px;">
                                Update Release Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Table of Installment Accounts -->
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 1.25rem;">Lipa Mdogo Mdogo Agreements</h2>
            <span style="color: var(--text-muted); font-size: 0.85rem;"><?= count($allAccounts) ?> account(s) registered</span>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Account No</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Delivery</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allAccounts)): ?>
                        <tr><td colspan="10" style="text-align: center; padding: 30px; color: var(--text-secondary);">No Lipa Mdogo Mdogo accounts created yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($allAccounts as $a): 
                            $pct = ($a['total_amount'] > 0) ? min(100, round(($a['amount_paid'] / $a['total_amount']) * 100)) : 0;
                        ?>
                            <tr>
                                <td>
                                    <strong style="font-family: monospace; color: #60a5fa;"><?= htmlspecialchars($a['account_number']) ?></strong>
                                </td>
                                <td>
                                    <div><strong><?= htmlspecialchars($a['customer_name']) ?></strong></div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($a['customer_phone']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($a['product_name']) ?></td>
                                <td style="font-weight: 600;"><?= formatMoney($a['total_amount']) ?></td>
                                <td style="font-weight: 600; color: #10b981;"><?= formatMoney($a['amount_paid']) ?></td>
                                <td style="font-weight: 600; color: #f59e0b;"><?= formatMoney($a['balance_remaining']) ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <div style="width: 50px; height: 8px; background: #1e293b; border-radius: 4px; overflow: hidden;">
                                            <div style="width: <?= $pct ?>%; height: 100%; background: <?= $pct >= 100 ? '#10b981' : '#3b82f6' ?>;"></div>
                                        </div>
                                        <span style="font-size: 0.75rem; font-weight: 600;"><?= $pct ?>%</span>
                                    </div>
                                </td>
                                <td><?= getInstallmentStatusBadge($a['status']) ?></td>
                                <td><?= getDeliveryStatusBadge($a['delivery_status']) ?></td>
                                <td>
                                    <a href="lipa-mdogo.php?view=<?= urlencode($a['account_number']) ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.8rem;">
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

    <!-- Management Forms Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <!-- Register Customer Form -->
        <div id="register-customer-section" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px;">
            <h3 style="font-size: 1.2rem; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                👤 1. Register Customer Phone Number
            </h3>
            <form method="POST">
                <input type="hidden" name="action_register_customer" value="1">
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="tel" name="phone_number" class="form-control" placeholder="0712345678" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="e.g. Mary Achieng" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label class="form-label">National ID Number</label>
                        <input type="text" name="id_number" class="form-control" placeholder="e.g. 29384910">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email (Optional)</label>
                        <input type="email" name="email" class="form-control" placeholder="mary@example.com">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Delivery Address</label>
                    <input type="text" name="delivery_address" class="form-control" placeholder="e.g. Eastleigh 1st Avenue">
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    Register Customer Profile
                </button>
            </form>
        </div>

        <!-- Create Installment Agreement Form -->
        <div id="create-plan-section" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px;">
            <h3 style="font-size: 1.2rem; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                📝 2. Create Installment Agreement
            </h3>
            <form method="POST">
                <input type="hidden" name="action_create_account" value="1">
                <div class="form-group">
                    <label class="form-label">Select Customer *</label>
                    <select name="customer_id" class="form-control" required>
                        <option value="">-- Choose registered customer --</option>
                        <?php foreach ($customersList as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['phone_number']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Select Product *</label>
                    <select name="product_id" class="form-control" required>
                        <option value="">-- Choose product --</option>
                        <?php foreach ($productsList as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> — <?= formatMoney($p['price']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Initial Deposit Required (KES)</label>
                    <input type="number" step="0.01" name="deposit_required" class="form-control" placeholder="1000.00" value="1000.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Terms / Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. 500 KES weekly installment agreement">
                </div>
                <button type="submit" class="btn btn-success btn-block">
                    Generate Installment Account
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

