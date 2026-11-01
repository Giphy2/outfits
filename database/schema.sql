-- =========================================================
-- Outfit Store - Code-Based Checkout & Order Tracking Database
-- Compatible with MySQL (XAMPP / MariaDB) and SQLite
-- =========================================================

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    category VARCHAR(60) NOT NULL DEFAULT 'Apparel',
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255) DEFAULT 'assets/images/placeholder.jpg',
    stock_quantity INT NOT NULL DEFAULT 50,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(120) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL,
    customer_email VARCHAR(120),
    delivery_address TEXT NOT NULL,
    city VARCHAR(60) DEFAULT 'Nairobi',
    subtotal DECIMAL(10,2) NOT NULL,
    shipping_fee DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('pending', 'paid', 'partially_paid', 'failed', 'expired', 'refunded') DEFAULT 'pending',
    fulfillment_status ENUM('placed', 'confirmed', 'processing', 'dispatched', 'delivered', 'cancelled') DEFAULT 'placed',
    expires_at DATETIME NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_code (order_code),
    INDEX idx_customer_phone (customer_phone),
    INDEX idx_payment_status (payment_status),
    INDEX idx_fulfillment_status (fulfillment_status)
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NULL,
    product_name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'mpesa_paybill',
    payment_reference VARCHAR(100) NULL,
    phone_number VARCHAR(30) NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    raw_payload TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_pay_reference (payment_reference)
);

CREATE TABLE IF NOT EXISTS order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status_type ENUM('payment', 'fulfillment') NOT NULL,
    status_value VARCHAR(50) NOT NULL,
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Initial Products
INSERT INTO products (name, slug, category, description, price, image_url, stock_quantity) VALUES
('Classic Urban Hoodie', 'classic-urban-hoodie', 'Hoodies', 'Heavyweight fleece hoodie in midnight black with ribbed cuffs and kangaroo pocket.', 3200.00, 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=600&auto=format&fit=crop&q=80', 25),
('Relaxed Fit Denim Jacket', 'relaxed-fit-denim-jacket', 'Jackets', 'Vintage wash durable denim jacket with copper hardware and reinforced stitching.', 4500.00, 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=600&auto=format&fit=crop&q=80', 15),
('Minimalist Cotton Tee', 'minimalist-cotton-tee', 'T-Shirts', '100% organic ring-spun combed cotton t-shirt in crisp chalk white.', 1500.00, 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=600&auto=format&fit=crop&q=80', 40),
('Utility Cargo Joggers', 'utility-cargo-joggers', 'Trousers', 'Tapered cargo pants featuring multiple utility pockets, elastic waistband and ankle cuffs.', 2800.00, 'https://images.unsplash.com/photo-1517445312882-bc9910d016b7?w=600&auto=format&fit=crop&q=80', 20),
('Streetwear Snapback Cap', 'streetwear-snapback-cap', 'Accessories', 'Premium structured 6-panel cap with embroidered logo and adjustable strap.', 1200.00, 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=600&auto=format&fit=crop&q=80', 30),
('Canvas Low-Top Sneakers', 'canvas-low-top-sneakers', 'Footwear', 'Everyday vulcanized rubber canvas sneakers with cushioned insole.', 3800.00, 'https://images.unsplash.com/photo-1525966226535-7c5efc0ef148?w=600&auto=format&fit=crop&q=80', 18)
ON DUPLICATE KEY UPDATE name=name;

-- Seed Default Admin User (username: admin, password: admin123)
-- Password hash generated with password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO admins (username, password_hash, full_name, email) VALUES
('admin', '$2y$10$wB4b7v3Xb2JgPZ2m6L/JRejVbW/kLwQxOQ9s3w0H0y8a4A9R1vJre', 'Store Administrator', 'admin@outfit.local')
ON DUPLICATE KEY UPDATE username=username;

