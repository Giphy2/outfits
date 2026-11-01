<?php
require_once __DIR__ . '/config.php';

function getDatabaseConnection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dbHost = DB_HOST;
    $dbPort = DB_PORT;
    $dbName = DB_NAME;
    $dbUser = DB_USER;
    $dbPass = DB_PASS;

    // Try MySQL first
    try {
        $serverDsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 2,
        ];

        $tempPdo = new PDO($serverDsn, $dbUser, $dbPass, $options);
        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        $pdo->setAttribute(PDO::ATTR_DRIVER_NAME, 'mysql');

        initDatabaseTables($pdo, 'mysql');
        return $pdo;
    } catch (PDOException $e) {
        // Fallback to SQLite so the prototype always works without stopping if MySQL isn't running
        $sqliteFile = __DIR__ . '/../database/outfit.sqlite';
        try {
            $pdo = new PDO("sqlite:" . $sqliteFile, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec("PRAGMA foreign_keys = ON;");
            initDatabaseTables($pdo, 'sqlite');
            return $pdo;
        } catch (Exception $sqle) {
            die("Database connection error: " . $e->getMessage() . " / SQLite fallback: " . $sqle->getMessage());
        }
    }
}

function initDatabaseTables(PDO $pdo, string $driver) {
    if ($driver === 'sqlite') {
        // SQLite schema
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                category TEXT NOT NULL DEFAULT 'Apparel',
                description TEXT,
                price NUMERIC NOT NULL,
                image_url TEXT DEFAULT 'assets/images/placeholder.jpg',
                stock_quantity INTEGER NOT NULL DEFAULT 50,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_code TEXT NOT NULL UNIQUE,
                customer_name TEXT NOT NULL,
                customer_phone TEXT NOT NULL,
                customer_email TEXT,
                delivery_address TEXT NOT NULL,
                city TEXT DEFAULT 'Nairobi',
                subtotal NUMERIC NOT NULL,
                shipping_fee NUMERIC DEFAULT 0.00,
                total_amount NUMERIC NOT NULL,
                amount_paid NUMERIC DEFAULT 0.00,
                payment_status TEXT DEFAULT 'pending',
                fulfillment_status TEXT DEFAULT 'placed',
                expires_at DATETIME NOT NULL,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NULL,
                product_name TEXT NOT NULL,
                price NUMERIC NOT NULL,
                quantity INTEGER NOT NULL DEFAULT 1,
                subtotal NUMERIC NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                payment_method TEXT NOT NULL DEFAULT 'mpesa_paybill',
                payment_reference TEXT NULL,
                phone_number TEXT NULL,
                amount NUMERIC NOT NULL,
                status TEXT DEFAULT 'completed',
                raw_payload TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS order_status_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                status_type TEXT NOT NULL,
                status_value TEXT NOT NULL,
                comment TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                full_name TEXT NOT NULL,
                email TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            -- Model 2: Lipa Mdogo Mdogo Tables
            CREATE TABLE IF NOT EXISTS customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                phone_number TEXT NOT NULL UNIQUE,
                full_name TEXT NOT NULL,
                id_number TEXT NULL,
                email TEXT NULL,
                delivery_address TEXT NULL,
                city TEXT DEFAULT 'Nairobi',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS installment_accounts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                account_number TEXT NOT NULL UNIQUE,
                customer_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                total_amount NUMERIC NOT NULL,
                deposit_required NUMERIC DEFAULT 0.00,
                deposit_paid INTEGER DEFAULT 0,
                amount_paid NUMERIC DEFAULT 0.00,
                balance_remaining NUMERIC NOT NULL,
                status TEXT DEFAULT 'active',
                delivery_status TEXT DEFAULT 'locked',
                target_completion_date DATE NULL,
                notes TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id)
            );

            CREATE TABLE IF NOT EXISTS installment_payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                installment_account_id INTEGER NOT NULL,
                payment_method TEXT DEFAULT 'mpesa_paybill',
                payment_reference TEXT NULL,
                amount NUMERIC NOT NULL,
                phone_number TEXT NULL,
                notes TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (installment_account_id) REFERENCES installment_accounts(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS otp_verifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                phone_number TEXT NOT NULL,
                otp_code TEXT NOT NULL,
                expires_at DATETIME NOT NULL,
                is_used INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed products if table is empty
        $count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        if ($count == 0) {
            $stmt = $pdo->prepare("INSERT INTO products (name, slug, category, description, price, image_url, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $sampleProducts = [
                ['Classic Urban Hoodie', 'classic-urban-hoodie', 'Hoodies', 'Heavyweight fleece hoodie in midnight black with ribbed cuffs and kangaroo pocket.', 3200.00, 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=600&auto=format&fit=crop&q=80', 25],
                ['Relaxed Fit Denim Jacket', 'relaxed-fit-denim-jacket', 'Jackets', 'Vintage wash durable denim jacket with copper hardware and reinforced stitching.', 4500.00, 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=600&auto=format&fit=crop&q=80', 15],
                ['Minimalist Cotton Tee', 'minimalist-cotton-tee', 'T-Shirts', '100% organic ring-spun combed cotton t-shirt in crisp chalk white.', 1500.00, 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=600&auto=format&fit=crop&q=80', 40],
                ['Utility Cargo Joggers', 'utility-cargo-joggers', 'Trousers', 'Tapered cargo pants featuring multiple utility pockets, elastic waistband and ankle cuffs.', 2800.00, 'https://images.unsplash.com/photo-1517445312882-bc9910d016b7?w=600&auto=format&fit=crop&q=80', 20],
                ['Streetwear Snapback Cap', 'streetwear-snapback-cap', 'Accessories', 'Premium structured 6-panel cap with embroidered logo and adjustable strap.', 1200.00, 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=600&auto=format&fit=crop&q=80', 30],
                ['Canvas Low-Top Sneakers', 'canvas-low-top-sneakers', 'Footwear', 'Everyday vulcanized rubber canvas sneakers with cushioned insole.', 3800.00, 'https://images.unsplash.com/photo-1525966226535-7c5efc0ef148?w=600&auto=format&fit=crop&q=80', 18]
            ];
            foreach ($sampleProducts as $p) {
                $stmt->execute($p);
            }
        }

        // Seed admin if empty
        $adminCount = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
        if ($adminCount == 0) {
            $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, full_name, email) VALUES (?, ?, ?, ?)");
            $stmt->execute(['admin', password_hash('admin123', PASSWORD_BCRYPT), 'Store Administrator', 'admin@outfit.local']);
        }

        // Seed sample Lipa Mdogo Mdogo customer and installment account if empty
        $customerCount = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        if ($customerCount == 0) {
            $custStmt = $pdo->prepare("INSERT INTO customers (phone_number, full_name, id_number, email, delivery_address, city) VALUES (?, ?, ?, ?, ?, ?)");
            $custStmt->execute(['0712345678', 'John Kamau', '34567890', 'john.kamau@example.com', 'Kilimani, Wood Avenue', 'Nairobi']);
            $customerId = $pdo->lastInsertId();

            $instStmt = $pdo->prepare("INSERT INTO installment_accounts (account_number, customer_id, product_id, total_amount, deposit_required, deposit_paid, amount_paid, balance_remaining, status, delivery_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $instStmt->execute([
                'LMM-84920',
                $customerId,
                2, // Relaxed Fit Denim Jacket
                4500.00,
                1000.00,
                1,
                2500.00,
                2000.00,
                'active',
                'locked',
                'Registered by Admin with 500/week payment schedule'
            ]);
            $accountId = $pdo->lastInsertId();

            // Seed sample installment payments
            $payStmt = $pdo->prepare("INSERT INTO installment_payments (installment_account_id, payment_method, payment_reference, amount, phone_number, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $payStmt->execute([$accountId, 'mpesa_paybill', 'MP-DEP10001', 1000.00, '0712345678', 'Initial Deposit']);
            $payStmt->execute([$accountId, 'mpesa_paybill', 'MP-INS20002', 1500.00, '0712345678', 'Week 1 Installment']);
        }
    } else {
        // MySQL schema
        $schemaSql = file_get_contents(__DIR__ . '/../database/schema.sql');
        if ($schemaSql) {
            $pdo->exec($schemaSql);
        }
    }
}
