<?php
define('PAGE_TITLE', 'Collection');
require_once __DIR__ . '/includes/header.php';

$pdo = getDatabaseConnection();

// Filters & Search
$selectedCategory = trim($_GET['category'] ?? 'all');
$search = trim($_GET['search'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');

// Build query
$sql = "SELECT * FROM products WHERE is_active = 1";
$params = [];

if ($selectedCategory !== 'all' && !empty($selectedCategory)) {
    $sql .= " AND category = ?";
    $params[] = $selectedCategory;
}

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
}

if ($sort === 'price_asc') {
    $sql .= " ORDER BY price ASC";
} elseif ($sort === 'price_desc') {
    $sql .= " ORDER BY price DESC";
} else {
    $sql .= " ORDER BY id DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get unique categories for filter pills
$catStmt = $pdo->query("SELECT DISTINCT category FROM products WHERE is_active = 1 ORDER BY category ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
?>

<div class="container" style="padding-top: 40px; padding-bottom: 80px;">
    <!-- Collection Header -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;">
        <div>
            <span class="badge badge-primary" style="margin-bottom: 8px;">ALL APPAREL</span>
            <h1 style="font-size: 2.2rem; font-weight: 800;">The Outfit Collection</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                Browse premium urban streetwear. Choose between <strong>Pay in Full</strong> or <strong>Lipa Mdogo Mdogo</strong> installments at checkout.
            </p>
        </div>

        <!-- Search & Filter Form -->
        <form method="GET" action="collection.php" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php if ($selectedCategory !== 'all'): ?>
                <input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategory) ?>">
            <?php endif; ?>

            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search collection..." style="width: 220px; font-size: 0.9rem; padding: 10px 14px;">

            <select name="sort" class="form-control" style="width: 170px; font-size: 0.9rem;" onchange="this.form.submit()">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
            </select>

            <button type="submit" class="btn btn-primary" style="padding: 10px 16px;">Filter</button>
            <?php if (!empty($search) || $selectedCategory !== 'all' || $sort !== 'newest'): ?>
                <a href="collection.php" class="btn btn-secondary" style="padding: 10px 14px;">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Category Filter Pills -->
    <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 14px; margin-bottom: 30px; border-bottom: 1px solid var(--border);">
        <a href="collection.php?category=all<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
           style="padding: 8px 18px; border-radius: 20px; font-size: 0.88rem; font-weight: 600; text-decoration: none; white-space: nowrap; transition: all 0.2s; background: <?= $selectedCategory === 'all' ? 'var(--accent)' : 'var(--bg-card)' ?>; color: <?= $selectedCategory === 'all' ? '#fff' : 'var(--text-secondary)' ?>; border: 1px solid <?= $selectedCategory === 'all' ? 'var(--accent)' : 'var(--border)' ?>;">
            All (<?= count($products) ?>)
        </a>
        <?php foreach ($categories as $cat): 
            $isActive = ($selectedCategory === $cat);
        ?>
            <a href="collection.php?category=<?= urlencode($cat) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               style="padding: 8px 18px; border-radius: 20px; font-size: 0.88rem; font-weight: 600; text-decoration: none; white-space: nowrap; transition: all 0.2s; background: <?= $isActive ? 'var(--accent)' : 'var(--bg-card)' ?>; color: <?= $isActive ? '#fff' : 'var(--text-secondary)' ?>; border: 1px solid <?= $isActive ? 'var(--accent)' : 'var(--border)' ?>;">
                <?= htmlspecialchars($cat) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Product Grid -->
    <?php if (empty($products)): ?>
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 60px 20px; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 12px;">🔍</div>
            <h3 style="font-size: 1.4rem; margin-bottom: 8px;">No Products Found</h3>
            <p style="color: var(--text-secondary); max-width: 480px; margin: 0 auto 20px;">
                We couldn't find any products matching your criteria. Try adjusting your filters or browse all apparel.
            </p>
            <a href="collection.php" class="btn btn-primary">View Full Collection</a>
        </div>
    <?php else: ?>
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
                            <div>
                                <div class="product-price"><?= formatMoney($product['price']) ?></div>
                                <div style="font-size: 0.75rem; color: #60a5fa; margin-top: 2px;">
                                    Lipa Mdogo Mdogo eligible
                                </div>
                            </div>
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
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

