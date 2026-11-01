# OUTFIT - Code-Based Checkout & Lipa Mdogo Mdogo Platform

An e-commerce architecture tailored for emerging markets with two distinct business models:

---

## The Two Business Models

### Model 1: Pay In Full (Order Code Checkout)
* **Purchase Unit:** Identified by a unique **Order Code** (e.g., `OFT-8429K`).
* **Flow:**
  1. Customer browses catalogue and adds items to cart.
  2. At checkout, enters delivery details and clicks "Generate Payment Code".
  3. System reserves inventory and generates code with a 2-hour payment window.
  4. Customer pays full amount via M-PESA Paybill `522522` (Account: `OFT-8429K`) or Bank.
  5. Backend verifies payment instantly, transitions status to `PAID`, and advances fulfillment to `CONFIRMED`.
  6. Customer tracks their order anytime on `track-order.php` using the order code.

### Model 2: Lipa Mdogo Mdogo (Installment Accounts)
* **Account Unit:** Identified by the customer's **Phone Number** and an agreement **Account Number** (e.g., `LMM-84920`).
* **Flow:**
  1. Administrator registers customer's phone number and creates an installment agreement for an eligible product.
  2. Customer pays in small amounts over time (e.g., daily/weekly via M-PESA Paybill `522522`, Account: `LMM-84920`).
  3. Customer visits `lipa-mdogo-mdogo.php`, inputs their phone number, and receives a secure **One-Time Password (OTP)**.
  4. Upon OTP verification, customer accesses their personal dashboard:
     * Visual progress bar (% paid vs remaining balance).
     * Complete payment statement of every installment made.
     * Real-time release status (locked vs **Ready for Collection / Delivery** when 100% paid).
     * Integrated installment payment simulator for easy testing.
  5. Administrator oversees agreements, views outstanding balances, credits cash payments, and manages product release in `admin/lipa-mdogo.php`.

---

## Project Structure

```
outfit/
├── index.php                  # Product catalogue highlighting both business models
├── cart.php                   # Shopping cart page
├── checkout.php               # Delivery details & order code generation
├── order-confirmation.php     # Pay-in-full confirmation, code display & payment simulator
├── track-order.php            # Visual order tracking page (Model 1)
├── lipa-mdogo-mdogo.php       # OTP-verified customer installment portal (Model 2)
│
├── admin/
│   ├── dashboard.php          # Overview of revenue & metrics for both models
│   ├── orders.php             # Full orders management (Model 1)
│   ├── lipa-mdogo.php         # Customer registrations & installment accounts (Model 2)
│   ├── update-status.php      # Order fulfillment update API
│   ├── header.php & footer.php
│
├── api/
│   ├── create-order.php       # Generates order code & reserves inventory
│   ├── simulate-payment.php   # Simulates pay-in-full order payments
│   ├── track-order.php        # JSON status polling endpoint for orders
│   ├── otp.php                # Sends & verifies 6-digit customer OTPs
│   ├── simulate-installment.php # Simulates Lipa Mdogo Mdogo installments
│   ├── payment-callback.php   # Universal M-PESA Daraja & webhook receiver
│   └── README.md              # API documentation summary
│
├── docs/
│   └── api.md                 # Complete REST API endpoint reference
│
├── config/
│   ├── config.php             # Paybill number, currency, expiry window settings
│   └── database.php           # PDO connection (MySQL with auto SQLite fallback)
│
├── database/
│   ├── schema.sql             # Full MySQL relational schema & seed data
│   └── outfit.sqlite          # Auto-initialized local database
│
├── includes/
│   ├── functions.php          # Business logic for orders, installments, and OTPs
│   ├── header.php & footer.php
│
└── assets/
    ├── css/style.css          # Modern dark-theme responsive design
    └── js/app.js              # Cart state, notifications & clipboard helpers
```

---

## How to Run & Test Locally

Start the local PHP server:

```powershell
C:\xampp\php\php.exe -S localhost:8000
```

### Try Model 1: Pay In Full
1. Open [http://localhost:8000](http://localhost:8000)
2. Add an item to cart and proceed to checkout.
3. Click **"Generate Payment Code"** to receive an `OFT-XXXXX` code.
4. Test the **"Simulate Full Payment"** button to watch automatic confirmation.
5. Track the order with its code at [http://localhost:8000/track-order.php](http://localhost:8000/track-order.php).

### Try Model 2: Lipa Mdogo Mdogo
1. Open [http://localhost:8000/lipa-mdogo-mdogo.php](http://localhost:8000/lipa-mdogo-mdogo.php).
2. Enter the pre-seeded demo phone number: **`0712345678`**.
3. Click **"Send Verification Code (OTP)"**; view the simulated SMS code and submit.
4. View the dashboard:
   * **Denim Jacket agreement** (`LMM-84920`).
   * Visual progress bar & financial breakdown.
   * Click the simulator buttons (e.g. `+ KES 500` or `Pay Balance`) to watch the progress update live!
5. Open [http://localhost:8000/admin/lipa-mdogo.php](http://localhost:8000/admin/lipa-mdogo.php) to register new customers and create new installment plans.

---

## API Documentation

Full endpoint specifications, parameters, response schemas, and cURL examples are documented in:
- **[API Endpoint Reference](docs/api.md)**
- **[API Directory Quickstart](api/README.md)**
