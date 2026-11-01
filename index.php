<?php
define('PAGE_TITLE', 'Catalog');
require_once __DIR__ . '/includes/header.php';

$pdo = getDatabaseConnection();
$stmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY id DESC");
$products = $stmt->fetchAll();
?>

<div class="hero">
    <div class="container">
        <h1>Urban Streetwear, Effortless Checkout</h1>
        <p>Shop top-grade streetwear with code-based payment. No online cards needed—checkout to get your unique order code, pay via M-PESA or Bank, and track your parcel in real-time.</p>
        
        <div class="hero-features">
            <div class="hero-badge">
                <span>🛒</span> Add to Cart
            </div>
            <div class="hero-badge">
                <span>⚡</span> Instant Order Code
            </div>
            <div class="hero-badge">
                <span>📱</span> Pay via M-PESA / Bank
            </div>
            <div class="hero-badge">
                <span>📍</span> Live Parcel Tracking
            </div>
        </div>
    </div>
</div>

<main class="products-section container">
    <div class="section-head">
        <div>
            <h2>Featured Collection</h2>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">Curated premium apparel and essentials</p>
        </div>
        <div>
            <a href="track-order.php" class="btn btn-secondary">
                <span>🔍 Track an Existing Order</span>
            </a>
        </div>
    </div>

    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="product-image-wrap">
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image" loading="lazy">
                    <span class="product-tag"><?= htmlspecialchars($product['category']) ?></span>
                </div>
                <div class="product-info">
                    <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                    <p class="product-desc"><?= htmlspecialchars($product['description']) ?></p>
                    
                    <div class="product-bottom">
                        <div class="product-price"><?= formatMoney($product['price']) ?></div>
                        <button type="button" class="btn btn-primary" onclick='Cart.addItem(<?= json_encode([
                            'id' => $product['id'],
                            'name' => $product['name'],
                            'price' => $product['price'],
                            'image_url' => $product['image_url']
                        ]) ?>)'>
                            + Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

