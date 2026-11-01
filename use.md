# OUTFIT Store - Dual Payment & Checkout System Guide

This system supports two purchasing models directly from the checkout page:
1. **Pay in Full (Code-Based Checkout)**
2. **Lipa Mdogo Mdogo (Agent-Verified Installment Plan)**

---

## 1. Checkout Workflow Architecture

```
[Customer adds items to Cart]
              ↓
[Checkout Page: checkout.php]
              ↓
STEP 1: SELECT PAYMENT METHOD FIRST
 ┌──────────────────────────────────────────────┴──────────────────────────────────────────────┐
 │                                                                                            │
 ▼ Option A: Pay In Full                                                                      ▼ Option B: Lipa Mdogo Mdogo
[Select "Pay in Full" (Default)]                                             [Select "Lipa Mdogo Mdogo" Option]
              ↓                                                                               ↓
Customer enters delivery details directly                                    System asks for Phone Number:
(Name, Phone, Address, City)                                                 • Customer inputs phone number
              ↓                                                                               ↓
[Click "Generate Payment Code"]                                              System checks Registration in Database:
              ↓                                                               api/check-customer.php?phone=07XXXXXXXX
Backend reserves items & generates unique                                                     │
Order Code (e.g. OFT-8429K) with 2-hour window                                ┌───────────────┴───────────────┐
              ↓                                                               ▼ CASE 1: Registered in DB       ▼ CASE 2: NOT in DB
[Order Confirmation Page: order-confirmation.php]                             • "✓ Verified in Database!"     • "⚠️ Not Found in Database"
• Displays Order Code with 1-click copy                                       • Displays Customer Name        • PROMPTS TO CONTACT AGENT:
• Shows M-PESA Paybill 522522 & Account No                                    • Shows 20% Deposit & Balance     - Phone/WhatsApp: +254 712 345 678
• Interactive Payment Simulator (Full/Partial)                                • Unlocks Delivery Details        - Store Customer Service Desk
              ↓                                                               • Unlocks "Proceed" button      • Purchase BLOCKED until added
[Payment Confirmed] (Simulator / M-PESA Webhook)                                              ↓                 by an agent or admin
• Order marked as PAID                                                       [Click "Proceed with Purchase"]  • Or switch to Pay in Full
• Fulfillment advances to CONFIRMED                                                           ↓
              ↓                                                              Backend creates Agreement (e.g. LMM-84920)
[Live Tracking Page: track-order.php]                                         with 20% deposit & remaining installments
• Customer tracks parcel status anytime using                                                 ↓
  the Order Code (e.g. OFT-8429K)                                            [Customer Portal: lipa-mdogo-mdogo.php]
                                                                             • Customer logs in using Phone + SMS OTP
                                                                             • Live repayment progress bar (% paid)
                                                                             • Detailed installment payment statement
                                                                             • Product marked LOCKED until 100% paid
                                                                             • Product becomes READY FOR COLLECTION
```

---

## 2. Starting the Local Server

Open your terminal in the workspace and run:

```powershell
C:\xampp\php\php.exe -S localhost:8000
```

---

## 3. Testing Option A: Pay in Full

1. Open **[http://localhost:8000/collection.php](http://localhost:8000/collection.php)** and click **"+ Add to Cart"** on an item.
2. Go to Checkout (**[http://localhost:8000/checkout.php](http://localhost:8000/checkout.php)**).
3. **Step 1 (Select Payment Method):** **"Pay in Full"** is selected by default.
4. **Step 2:** Fill in your delivery details (Name, Phone, Address).
5. Click **"⚡ Generate Payment Code"**.
6. On the confirmation page:
   * View your unique code (e.g., `OFT-XXXXX`).
   * Click **"Simulate Full Payment"** to test real-time backend verification.
7. Click **"Open Full Tracking Page"** to inspect the live dispatch timeline.

---

## 4. Testing Option B: Lipa Mdogo Mdogo (Agent-Verified Installments)

### A. Testing an Unregistered Phone Number (Agent Prompt)
1. On Checkout, select **"Lipa Mdogo Mdogo"** in Step 1.
2. The phone verification box will appear.
3. Enter an unregistered phone number (e.g., `0799000111`) or click the **"Test unregistered phone"** pill.
4. Click **"Verify Number →"**.
5. The system checks the database and displays:
   * **`⚠️ Phone Number Not Found in Database`**.
   * Prompts: **"Please Contact an Agent for Registration"** (Store Agent Phone / WhatsApp: `+254 712 345 678`).
   * The purchase and delivery details remain locked until registered by an agent.

### B. Registering the Customer via Admin (Agent Action)
1. Open the Admin Agent Desk in a new tab: **[http://localhost:8000/admin/lipa-mdogo.php#register-customer-section](http://localhost:8000/admin/lipa-mdogo.php#register-customer-section)**.
2. Enter the customer's phone number (`0799000111`), Full Name, and National ID.
3. Click **"Register Customer Profile"**.

### C. Proceeding with the Purchase Once Added
1. Return to Checkout (**[http://localhost:8000/checkout.php](http://localhost:8000/checkout.php)**).
2. Enter the now-registered phone number (`0799000111` or demo `0712345678`).
3. Click **"Verify Number →"**.
4. The system confirms:
   * **`✓ Registration Verified in Database!`** with the customer's name.
   * Displays the 20% deposit breakdown.
   * Auto-fills and unlocks the delivery details form.
5. Click **"📱 Proceed with Lipa Mdogo Mdogo Purchase →"**.
6. You will be redirected directly to your **Lipa Mdogo Mdogo Customer Portal** (**[http://localhost:8000/lipa-mdogo-mdogo.php](http://localhost:8000/lipa-mdogo-mdogo.php)**.
7. Test the repayment buttons (`+ KES 500` or `Pay Balance`) to watch the repayment progress bar animate to 100% and release the product for collection!

---

## 5. Admin Dashboard & Database Table Explorer

Open **[http://localhost:8000/admin/dashboard.php](http://localhost:8000/admin/dashboard.php)**:
* **Function Cards**: Direct links to manage Pay-in-Full Orders, Lipa Mdogo Mdogo plans, customer registrations, and payments.
* **Database Table Explorer**: Click any tab at the bottom to inspect records live:
  * 🛒 `orders`
  * 📱 `installment_accounts`
  * 👤 `customers`
  * 📦 `products`
  * 💳 `payments`
  * 💰 `installment_payments`
  * 📜 `order_status_history`
  * 🔑 `otp_verifications`


---

## 6. Route Authentication & Security Guide

The application enforces a 3-tier security model:

| Route Type | Routes | Authentication Mechanism | Unauthenticated Behavior |
| :--- | :--- | :--- | :--- |
| **Admin Portal** | `admin/dashboard.php`<br>`admin/orders.php`<br>`admin/lipa-mdogo.php`<br>`admin/update-status.php` | Password-verified Admin Session (`admins` DB table + `password_verify`) | Redirects to `admin/login.php` with return URL; APIs return `401 Unauthorized` |
| **Customer Portal** | `lipa-mdogo-mdogo.php`<br>`api/simulate-installment.php` | SMS One-Time Password (OTP) session (`otp_verifications` table) | Shows OTP login gate; APIs return `403 Forbidden` if caller does not own the agreement |
| **Order Tracking** | `track-order.php` | Order Code lookup + Phone number PII verification | General order timeline is public; Customer name, phone, and address are **masked** until owner verifies phone |
| **Public Store** | `index.php`<br>`cart.php`<br>`checkout.php`<br>`order-confirmation.php` | Open access (Public customer browsing & cart) | Full public access |
| **Webhooks** | `api/payment-callback.php` | Shared secret token (`WEBHOOK_SECRET_KEY`) / IPN verification | Rejects unauthorized webhook payloads with `403 Forbidden` |

### Default Admin Credentials
* **Login URL:** [http://localhost:8000/admin/login.php](http://localhost:8000/admin/login.php)
* **Username:** `admin`
* **Password:** `admin123`
* **Logout URL:** [http://localhost:8000/admin/logout.php](http://localhost:8000/admin/logout.php)


---

## 7. Procedures to Add Products to the Store

Products can be added either through the **Admin Web Portal** or directly on the **Database end (XAMPP / phpMyAdmin / MySQL)**.

### Procedure 1: Adding Products via Admin Web Interface
1. Log in to the Admin Portal: **[http://localhost:8000/admin/products.php](http://localhost:8000/admin/products.php)**.
2. Under **"Add New Product to Store"**, fill in:
   * **Product Name:** e.g., `Heavyweight Graphic Hoodie`
   * **Category:** Choose from dropdown (`Hoodies`, `Jackets`, `T-Shirts`, `Trousers`, `Footwear`, `Accessories`).
   * **Retail Price (KES):** e.g., `3500.00`
   * **Stock Quantity:** e.g., `20`
   * **Image URL:** Direct web link (e.g. Unsplash URL) or local file path (`assets/images/your-photo.jpg`).
   * **Product Description:** Details on material, sizing, and features.
   * **Publish Status:** Check *"Publish immediately to customer collection"*.
3. Click **"✓ Save & Publish Product"**.
4. The product is immediately live on the **[Collection Page](http://localhost:8000/collection.php)** and eligible for both **Pay in Full** and **Lipa Mdogo Mdogo** installments.

---

### Procedure 2: Adding Products on the Database End (XAMPP / phpMyAdmin / MySQL)

#### A. Using phpMyAdmin (XAMPP Browser UI)
1. Open XAMPP Control Panel and ensure **Apache** and **MySQL** are running.
2. Open your browser and go to: **[http://localhost/phpmyadmin](http://localhost/phpmyadmin)**.
3. In the left sidebar, select the database: **`outfit_db`**.
4. Click on the **`products`** table.
5. Click on the **"Insert"** (or **"SQL"**) tab at the top.
6. Enter product values or run the SQL query below.

#### B. Using MySQL Command Line or phpMyAdmin SQL Tab
Run this SQL query:

```sql
INSERT INTO products (
    name, 
    slug, 
    category, 
    description, 
    price, 
    image_url, 
    stock_quantity, 
    is_active
) VALUES (
    'Acid Wash Denim Overshirt',
    'acid-wash-denim-overshirt',
    'Jackets',
    '100% rigid cotton overshirt with distressed acid wash and chest utility pockets.',
    4200.00,
    'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=600&auto=format&fit=crop&q=80',
    20,
    1
);
```

#### Field Explanations for the `products` Table:
* **`name`** (`VARCHAR(150)`): The title of the product displayed to shoppers.
* **`slug`** (`VARCHAR(160)` UNIQUE): URL-safe lowercase identifier with hyphens (e.g. `acid-wash-denim-overshirt`).
* **`category`** (`VARCHAR(60)`): Category group (e.g. `Hoodies`, `Jackets`, `T-Shirts`, `Trousers`, `Footwear`, `Accessories`).
* **`description`** (`TEXT`): Material, fit, and specs.
* **`price`** (`DECIMAL(10,2)`): Retail price in Kenya Shillings (KES).
* **`image_url`** (`VARCHAR(255)`): Image path or URL.
* **`stock_quantity`** (`INT`): Units in stock.
* **`is_active`** (`TINYINT(1)`): `1` to show in customer collection, `0` to hide.
