<?php
require_once __DIR__ . '/auth.php';

// If already logged in, redirect to dashboard
if (isAdminAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$returnUrl = $_GET['return'] ?? 'dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Credentials valid: start session
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];

            $target = !empty($_POST['return']) ? $_POST['return'] : 'dashboard.php';
            // Prevent open redirect
            if (strpos($target, 'http://') !== false || strpos($target, 'https://') !== false) {
                $target = 'dashboard.php';
            }
            header('Location: ' . $target);
            exit;
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at 50% 20%, rgba(225, 29, 72, 0.12), transparent 70%), var(--bg-primary);
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 36px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="display: inline-flex; align-items: center; gap: 8px; font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 8px;">
            <span><?= APP_NAME ?></span>
            <span class="logo-badge" style="background: #e11d48;">ADMIN</span>
        </div>
        <h2 style="font-size: 1.4rem; font-weight: 700;">Restricted Access</h2>
        <p style="color: var(--text-secondary); font-size: 0.88rem; margin-top: 4px;">
            Please authenticate to access the administration portal.
        </p>
    </div>

    <?php if (!empty($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 12px 16px; border-radius: 8px; font-size: 0.88rem; margin-bottom: 20px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl) ?>">

        <div class="form-group">
            <label class="form-label" for="username">Admin Username</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="admin" required autofocus>
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 1rem; background: #e11d48; border-color: #e11d48;">
            🔐 Log In to Dashboard
        </button>
    </form>

    <!-- Demo testing hint -->
    <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border); text-align: center;">
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px;">Development Demo Credentials:</p>
        <button type="button" class="btn btn-secondary" style="font-size: 0.78rem; padding: 4px 10px;" onclick="fillDemoCredentials()">
            💡 Fill Demo: admin / admin123
        </button>
    </div>

    <div style="text-align: center; margin-top: 18px;">
        <a href="../index.php" style="font-size: 0.85rem; color: var(--text-secondary); text-decoration: underline;">
            ← Return to Customer Storefront
        </a>
    </div>
</div>

<script>
function fillDemoCredentials() {
    document.getElementById('username').value = 'admin';
    document.getElementById('password').value = 'admin123';
}
</script>

</body>
</html>

