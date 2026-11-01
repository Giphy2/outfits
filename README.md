# OUTFIT - Code-Based Checkout & Order Tracking System

An e-commerce architecture designed for cash/mobile-money dominant markets (e.g., M-PESA Paybill, Till, USSD, and Bank Transfers). Instead of entering payment cards directly on the website:
1. **Add to Cart & Checkout:** The customer inputs delivery details.
2. **Order Code Generation:** The system reserves inventory and generates a unique, human-friendly payment reference code (e.g., `OFT-7492K`).
3. **External Payment Channel:** The customer pays via their phone (e.g., M-PESA Paybill `522522`, Account: `OFT-7492K`).
4. **Instant Verification:** The backend receives confirmation (via Webhook / Payment Simulator), marks the order as paid, and transitions fulfillment.
5. **Real-time Order Tracking:** The customer tracks their parcel anytime using their unique code.

---

## Project Structure

```
outfit/
├── index.php                  # Product catalogue & shopping storefront
├── cart.php                   # Shopping cart management
├── checkout.php               # Customer details & code generation trigger
├── order-confirmation.php     # Order code display, payment instructions & live simulator
├── track-order.php            # Real-time customer tracking page with visual timeline
│
├── admin/
│   ├── header.php             # Admin navbar
│   ├── footer.php             # Admin footer
│   ├── dashboard.php          # Financial metrics, revenue counter & recent orders
│   ├── orders.php             # Order filter, inspector, & status changer
│   └── update-status.php      # Fulfillment update API
│
├── api/
│   ├── create-order.php       # Generates unique order code & saves items
│   ├── simulate-payment.php   # Development simulator for payment callbacks
│   ├── track-order.php        # JSON status endpoint for live polling
│   └── payment-callback.php   # Real webhook endpoint for Safaricom Daraja / Banks
│
├── config/
│   ├── config.php             # Paybill number, currency, expiry window settings
│   └── database.php           # PDO connection (MySQL with auto SQLite fallback)
│
├── database/
│   ├── schema.sql             # Full MySQL relational schema & seed data
│   └── outfit.sqlite          # Auto-initialized local DB for instant setup
│
├── includes/
│   ├── header.php             # Storefront header with live cart count
│   ├── footer.php             # Storefront footer
│   └── functions.php          # Code generator, currency formatter, payment reconciliation
│
└── assets/
    ├── css/style.css          # Modern dark-mode responsive theme
    └── js/app.js              # Cart state, notifications & clipboard helpers
```

---

## How to Run Locally

You can run the application with PHP's built-in server:

```powershell
# In the project root (e:\projects\outfit)
C:\xampp\php\php.exe -S localhost:8000
```

Then open your browser:
* **Storefront:** [http://localhost:8000](http://localhost:8000)
* **Track an Order:** [http://localhost:8000/track-order.php](http://localhost:8000/track-order.php)
* **Admin Dashboard:** [http://localhost:8000/admin/dashboard.php](http://localhost:8000/admin/dashboard.php)

---

## Key Features Built

1. **Unambiguous Order Codes**: Generated using non-confusable alphanumeric characters (no `0` vs `O`, no `1` vs `I`).
2. **Order Expiry Window**: 2-hour payment window to prevent indefinite stock locking.
3. **Built-in Payment Sandbox**: `order-confirmation.php` includes test buttons to simulate full payment, partial payment, and overpayment.
4. **Live Polling**: Confirmation page auto-detects payments in the background without page reload.
5. **Full Audit Trail**: Every status change and payment attempt is logged in `order_status_history`.

