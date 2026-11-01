<?php
define('ADMIN_PAGE_TITLE', 'Products & Inventory');
require_once __DIR__ . '/header.php';

$pdo = getDatabaseConnection();
$message = '';
$messageType = 'success';

// Handle Add Product POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_product'])) {
    $name = sanitize($_POST['name'] ?? '');
    $category = sanitize($_POST['category'] ?? 'Apparel');
    $price = (float)($_POST['price'] ?? 0);
    $stock = max(0, (int)($_POST['stock_quantity'] ?? 10));
    $description = sanitize($_POST['description'] ?? '');
    $imageUrl = sanitize($_POST['image_url'] ?? 'assets/images/placeholder.jpg');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Generate unique slug
    $slugBase = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $slug = $slugBase ?: 'item-' . time();

    // Ensure slug is unique
    $checkSlug = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ?");
    $checkSlug->execute([$slug]);
    if ($checkSlug->fetchColumn() > 0) {
        $slug .= '-' . rand(100, 999);
    }

    if (!empty($name) && $price > 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products (name, slug, category, description, price, image_url, stock_quantity, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $slug, $category, $description, $price, $imageUrl, $stock, $isActive]);
            $message = "Product '{$name}' added to inventory successfully!";
        } catch (Exception $e) {
            $message = "Error adding product: " . $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = "Product name and a valid price (> 0) are required.";
        $messageType = 'danger';
    }
}

// Handle Toggle Active Status
if (isset($_GET['toggle_id'])) {
    $toggleId = (int)$_GET['toggle_id'];
    $stmt = $pdo->prepare("UPDATE products SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?");
    $stmt->execute([$toggleId]);
    header('Location: products.php');
    exit;
}

// Fetch all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();

$categories = ['Hoodies', 'Jackets', 'T-Shirts', 'Trousers', 'Footwear', 'Accessories', 'Streetwear'];
?>

<div class="container" style="padding-top: 36px; padding-bottom: 70px;">
    
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <span class="badge badge-success">CATALOGUE</span>
                <span style="font-size: 0.85rem; color: var(--text-muted);">Admin Portal</span>
            </div>
            <h1 style="font-size: 1.8rem; font-weight: 800;">Product Catalogue & Inventory</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                Add new products to the storefront, update pricing, and manage stock quantities.
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="#add-product-form" class="btn btn-primary" style="font-size: 0.88rem;">
                + Add New Product
            </a>
            <a href="../collection.php" target="_blank" class="btn btn-secondary" style="font-size: 0.88rem;">
                ↗ View Live Collection
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div style="padding: 14px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; background: <?= $messageType === 'danger' ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.15)' ?>; border: 1px solid <?= $messageType === 'danger' ? '#ef4444' : '#10b981' ?>; color: <?= $messageType === 'danger' ? '#fca5a5' : '#6ee7b7' ?>;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Add Product Form Card -->
    <div id="add-product-form" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px; margin-bottom: 36px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
        <h3 style="font-size: 1.25rem; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            <span>📦</span> Add New Product to Store
        </h3>

        <form method="POST" action="products.php">
            <input type="hidden" name="action_add_product" value="1">

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label" for="prod_name">Product Name *</label>
                    <input type="text" id="prod_name" name="name" class="form-control" placeholder="e.g. Heavyweight Fleece Cargo Hoodie" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="prod_category">Category *</label>
                    <select id="prod_category" name="category" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>"><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label" for="prod_price">Retail Price (KES) *</label>
                    <input type="number" step="0.01" id="prod_price" name="price" class="form-control" placeholder="3500.00" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="prod_stock">Stock Quantity *</label>
                    <input type="number" id="prod_stock" name="stock_quantity" class="form-control" value="25" min="0" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="prod_image">Image URL</label>
                    <input type="text" id="prod_image" name="image_url" class="form-control" value="https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=600&auto=format&fit=crop&q=80" placeholder="https://... or assets/images/filename.jpg">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="prod_desc">Product Description</label>
                <textarea id="prod_desc" name="description" class="form-control" rows="2" placeholder="e.g. Custom tailored fit, 400 GSM heavyweight French terry cotton with ribbed cuffs."></textarea>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem;">
                    <input type="checkbox" name="is_active" value="1" checked>
                    <span>Publish immediately to customer collection</span>
                </label>

                <button type="submit" class="btn btn-primary" style="padding: 12px 28px;">
                    ✓ Save & Publish Product
                </button>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 1.25rem;">Current Inventory (<?= count($products) ?> items)</h2>
            <span style="font-size: 0.85rem; color: var(--text-muted);">Stored in MySQL / SQLite</span>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Details</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                No products found. Add your first item using the form above!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td style="width: 60px;">
                                    <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border);">
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                                    <div style="font-size: 0.78rem; color: var(--text-muted); font-family: monospace;">slug: <?= htmlspecialchars($p['slug']) ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-primary"><?= htmlspecialchars($p['category']) ?></span>
                                </td>
                                <td style="font-weight: 700; color: #fff;">
                                    <?= formatMoney($p['price']) ?>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: <?= $p['stock_quantity'] > 5 ? '#10b981' : '#f59e0b' ?>;">
                                        <?= (int)$p['stock_quantity'] ?> in stock
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['is_active']): ?>
                                        <span class="badge badge-success">ACTIVE</span>
                                    <?php else: ?>
                                        <span class="badge badge-muted">HIDDEN</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="products.php?toggle_id=<?= $p['id'] ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.8rem;">
                                        <?= $p['is_active'] ? 'Hide' : 'Show' ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>

