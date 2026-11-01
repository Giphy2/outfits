# OUTFIT E-Commerce & Lipa Mdogo Mdogo — API Documentation

This document provides complete, production-grade REST API endpoint documentation for the **OUTFIT** platform, covering both:
1. **Model 1: Pay In Full** (Instant Order Codes: `OFT-XXXXX`)
2. **Model 2: Lipa Mdogo Mdogo** (Installment Accounts: `LMM-XXXXX`)

---

## 1. Overview & Base URLs

| Environment | Base URL |
| :--- | :--- |
| **Local Development (Built-in Server)** | `http://localhost:8000/api` |
| **Local Development (XAMPP / Apache)** | `http://localhost/outfit/api` |
| **Production Server** | `https://yourdomain.com/api` |

### Default Headers
Unless otherwise noted, all requests accepting a JSON payload must include:
```http
Content-Type: application/json
Accept: application/json
```

---

## 2. Authentication & Authorization Models

| Auth Scheme | Header / Session | Used By | Description |
| :--- | :--- | :--- | :--- |
| **Public / Guest** | *None* | `create-order`, `check-customer`, `otp`, `track-order` | Open to storefront shoppers. |
| **Customer OTP Session** | Cookie (`PHPSESSID`) with `$_SESSION['lmm_customer_id']` | `simulate-installment`, Customer Dashboard | Established via `/api/otp.php?action=verify`. |
| **Admin Session** | Cookie (`PHPSESSID`) with `$_SESSION['admin_logged_in']` | `/admin/update-status.php`, Admin Dashboards | Established via `/admin/login.php`. |
| **Webhook Secret** | `X-Webhook-Secret: <TOKEN>` or `?secret=<TOKEN>` | `/api/payment-callback.php` | Shared secret with Safaricom Daraja / Bank Gateway. |

---

## 3. Endpoints Quick Index

| Method | Endpoint | Auth | Purpose |
| :--- | :--- | :--- | :--- |
| `GET` / `POST` | [`/api/check-customer.php`](#1-check-customer-eligibility) | Public | Verify customer phone number for Lipa Mdogo Mdogo |
| `POST` | [`/api/otp.php`](#2-otp-send--verify) | Public | Request or verify OTP code for customer portal |
| `POST` | [`/api/create-order.php`](#3-create-order--installment-plan) | Public | Create Pay-In-Full order or Lipa Mdogo Mdogo agreement |
| `GET` | [`/api/track-order.php`](#4-track-order--status) | Public | Poll real-time order status, payments & fulfillment |
| `POST` | [`/api/simulate-payment.php`](#5-simulate-pay-in-full-payment) | Public / Test | Mock payment against an `OFT-XXXXX` order |
| `POST` | [`/api/simulate-installment.php`](#6-simulate-lipa-mdogo-mdogo-installment) | Customer / Admin | Post an installment payment to an `LMM-XXXXX` account |
| `POST` | [`/api/payment-callback.php`](#7-payment-gateway-webhook) | Webhook Secret | Universal M-Pesa Daraja / Bank payment confirmation |
| `POST` | [`/admin/update-status.php`](#8-admin-update-fulfillment-status) | Admin Session | Update order fulfillment milestone & audit log |

---

## 4. Endpoint Specifications

### 1. Check Customer Eligibility
Verifies whether a customer's phone number is already registered in the system by an administrator for Lipa Mdogo Mdogo.

- **URL:** `/api/check-customer.php`
- **Method:** `GET` or `POST`
- **Query Parameters / Form Fields:**
  - `phone` (string, required): Customer phone number in local (`07...`) or international (`2547...` / `+2547...`) format.

#### Example Request (cURL)
```bash
curl -X GET "http://localhost:8000/api/check-customer.php?phone=0712345678"
```

#### Success Response — Registered (200 OK)
```json
{
  "success": true,
  "registered": true,
  "customer": {
    "id": 1,
    "full_name": "Amina Wanjiku",
    "phone_number": "254712345678",
    "id_number": "30124578",
    "delivery_address": "Kenyatta Ave, Suite 402",
    "city": "Nairobi"
  },
  "message": "Customer registered and verified for Lipa Mdogo Mdogo."
}
```

#### Success Response — Not Registered (200 OK)
```json
{
  "success": true,
  "registered": false,
  "phone": "254799887766",
  "message": "Phone number is not yet registered for Lipa Mdogo Mdogo."
}
```

#### Error Response — Missing Phone (200 OK)
```json
{
  "success": false,
  "registered": false,
  "message": "Phone number is required."
}
```

---

### 2. OTP Send & Verify
Handles sending 6-digit OTP verification codes via SMS and authenticating the customer session.

- **URL:** `/api/otp.php`
- **Method:** `POST`
- **Request Body (JSON):**

#### Action A: Request OTP (`send`)
```json
{
  "action": "send",
  "phone": "0712345678"
}
```

#### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Verification code sent via SMS to 254712345678.",
  "demo_otp": "748291",
  "customer_name": "Amina Wanjiku"
}
```
*(Note: `demo_otp` is provided during local testing and prototype development.)*

#### Error Response (200 OK)
```json
{
  "success": false,
  "message": "Phone number not registered. Please contact the store administrator to open a Lipa Mdogo Mdogo installment account."
}
```

#### Action B: Verify OTP (`verify`)
```json
{
  "action": "verify",
  "phone": "0712345678",
  "code": "748291"
}
```

#### Success Response (200 OK)
Sets `$_SESSION['lmm_customer_id']`, `$_SESSION['lmm_customer_phone']`, and `$_SESSION['lmm_customer_name']`.
```json
{
  "success": true,
  "message": "Verification successful! Logging in...",
  "redirect": "lipa-mdogo-mdogo.php"
}
```

#### Error Response (200 OK)
```json
{
  "success": false,
  "message": "Invalid or expired verification code. Please request a new one."
}
```

#### Example Request (cURL)
```bash
# Request OTP
curl -X POST http://localhost:8000/api/otp.php \
  -H "Content-Type: application/json" \
  -d '{"action":"send","phone":"0712345678"}'

# Verify OTP
curl -X POST http://localhost:8000/api/otp.php \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -d '{"action":"verify","phone":"0712345678","code":"748291"}'
```

---

### 3. Create Order / Installment Plan
Places a verified checkout order. Supports both **Pay In Full** (`full`) and **Lipa Mdogo Mdogo** (`lipa_mdogo`).

- **URL:** `/api/create-order.php`
- **Method:** `POST`
- **Request Body (JSON):**

| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `payment_mode` | `string` | Yes | `"full"` or `"lipa_mdogo"` |
| `customer_name`| `string` | Yes | Customer full name |
| `customer_phone`| `string`| Yes | Mobile phone number (e.g. `0712345678`) |
| `customer_email`| `string`| No | Email address |
| `delivery_address`| `string`| Yes | Street address / Building / House number |
| `city` | `string` | No | City / Town (Defaults to `"Nairobi"`) |
| `notes` | `string` | No | Customer order delivery instructions |
| `items` | `array` | Yes | Array of item objects: `[{ "id": 1, "quantity": 1 }]` |

#### Payload Example (Pay In Full)
```json
{
  "payment_mode": "full",
  "customer_name": "Brian Kiprono",
  "customer_phone": "0722000001",
  "customer_email": "brian@example.com",
  "delivery_address": "Kilimani, Rose Avenue, Apt 4B",
  "city": "Nairobi",
  "notes": "Call upon arrival at the gate",
  "items": [
    { "id": 1, "quantity": 1 },
    { "id": 3, "quantity": 2 }
  ]
}
```

#### Success Response — Pay In Full (200 OK)
```json
{
  "success": true,
  "payment_mode": "full",
  "order_code": "OFT-A78B9",
  "total_amount": 16499.00,
  "redirect": "order-confirmation.php?code=OFT-A78B9"
}
```

#### Payload Example (Lipa Mdogo Mdogo)
```json
{
  "payment_mode": "lipa_mdogo",
  "customer_name": "Amina Wanjiku",
  "customer_phone": "0712345678",
  "customer_email": "amina@example.com",
  "delivery_address": "Kenyatta Ave, Suite 402",
  "city": "Nairobi",
  "items": [
    { "id": 1, "quantity": 1 }
  ]
}
```

#### Success Response — Lipa Mdogo Mdogo (200 OK)
```json
{
  "success": true,
  "payment_mode": "lipa_mdogo",
  "account_number": "LMM-9481",
  "deposit_required": 1700.00,
  "total_amount": 8499.00,
  "message": "Lipa Mdogo Mdogo account created successfully!",
  "redirect": "lipa-mdogo-mdogo.php?new_account=LMM-9481"
}
```

#### Error Response — Unregistered for Lipa Mdogo (200 OK)
```json
{
  "success": false,
  "registered": false,
  "phone": "254799887766",
  "message": "The phone number (254799887766) is not registered for Lipa Mdogo Mdogo. Please contact a store agent for registration before proceeding."
}
```

#### Example Request (cURL)
```bash
curl -X POST http://localhost:8000/api/create-order.php \
  -H "Content-Type: application/json" \
  -d '{
    "payment_mode": "full",
    "customer_name": "Brian Kiprono",
    "customer_phone": "0722000001",
    "delivery_address": "Kilimani, Rose Ave",
    "city": "Nairobi",
    "items": [{"id": 1, "quantity": 1}]
  }'
```

---

### 4. Track Order & Status
Polls current payment, fulfillment, item breakdown, and chronological timeline events for any order code.

- **URL:** `/api/track-order.php`
- **Method:** `GET`
- **Query Parameters:**
  - `code` (string, required): Order code (e.g. `OFT-A78B9`).

#### Success Response (200 OK)
```json
{
  "success": true,
  "order": {
    "order_code": "OFT-A78B9",
    "customer_name": "Brian Kiprono",
    "customer_phone": "254722000001",
    "delivery_address": "Kilimani, Rose Ave",
    "city": "Nairobi",
    "total_amount": 8499.00,
    "amount_paid": 8499.00,
    "payment_status": "paid",
    "fulfillment_status": "processing",
    "payment_status_badge": "<span class=\"badge bg-success\">Paid</span>",
    "fulfillment_status_badge": "<span class=\"badge bg-info\">Processing</span>",
    "expires_at": "2026-11-01 22:30:00",
    "created_at": "2026-11-01 20:30:00",
    "items": [
      {
        "id": 12,
        "product_name": "Premium Cotton Oxford Shirt",
        "price": "8499.00",
        "quantity": 1,
        "subtotal": "8499.00"
      }
    ],
    "payments": [
      {
        "id": 8,
        "amount": "8499.00",
        "payment_method": "mpesa_paybill",
        "payment_reference": "MP-9812A7BK",
        "created_at": "2026-11-01 20:32:15"
      }
    ],
    "history": [
      {
        "id": 15,
        "status_type": "fulfillment",
        "status_value": "placed",
        "comment": "Order created. Payment reference generated: OFT-A78B9",
        "created_at": "2026-11-01 20:30:00"
      },
      {
        "id": 16,
        "status_type": "payment",
        "status_value": "paid",
        "comment": "Payment of KES 8,499.00 received via mpesa_paybill (Ref: MP-9812A7BK)",
        "created_at": "2026-11-01 20:32:15"
      }
    ]
  }
}
```

#### Example Request (cURL)
```bash
curl -X GET "http://localhost:8000/api/track-order.php?code=OFT-A78B9"
```

---

### 5. Simulate Pay-in-Full Payment
Simulates payment receipt for testing the Pay-In-Full order lifecycle. Automatically marks order `paid` and creates audit records.

- **URL:** `/api/simulate-payment.php`
- **Method:** `POST`
- **Request Body (JSON):**

```json
{
  "order_code": "OFT-A78B9",
  "amount": 8499.00,
  "payment_method": "mpesa_paybill",
  "phone": "0722000001"
}
```

#### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Payment simulated & verified successfully!",
  "reference": "MP-872GA901",
  "order": {
    "success": true,
    "payment_id": 9,
    "order_id": 4,
    "total_paid": 8499.00,
    "status": "paid"
  }
}
```

#### Example Request (cURL)
```bash
curl -X POST http://localhost:8000/api/simulate-payment.php \
  -H "Content-Type: application/json" \
  -d '{"order_code":"OFT-A78B9","amount":8499.00,"payment_method":"mpesa_paybill","phone":"0722000001"}'
```

---

### 6. Simulate Lipa Mdogo Mdogo Installment
Credits an installment payment towards a customer's `LMM-XXXX` agreement. Automatically:
- Deducts from `balance_remaining`
- Adds to `amount_paid`
- Updates `deposit_paid = 1` if threshold reached
- Unlocks delivery status (`locked` -> `unlocked`) when balance reaches `0.00`

- **URL:** `/api/simulate-installment.php`
- **Method:** `POST`
- **Security:** Requires active customer session (verifying account ownership) OR active admin session.
- **Request Body (JSON):**

```json
{
  "account_number": "LMM-9481",
  "amount": 2000.00,
  "notes": "Weekly partial installment top-up"
}
```

#### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Installment of KES 2,000.00 credited to LMM-9481!",
  "reference": "MP-LMM54B1A0",
  "account": {
    "id": 2,
    "account_number": "LMM-9481",
    "total_amount": "8499.00",
    "deposit_required": "1700.00",
    "deposit_paid": 1,
    "amount_paid": "3700.00",
    "balance_remaining": "4799.00",
    "status": "active",
    "delivery_status": "locked"
  }
}
```

#### Error Response — Unauthorized (403 Forbidden)
```json
{
  "success": false,
  "message": "Authentication required: Please verify your phone number via OTP or log in as admin to process installments."
}
```

#### Example Request (cURL)
```bash
# Executed using stored authenticated session cookie
curl -X POST http://localhost:8000/api/simulate-installment.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{"account_number":"LMM-9481","amount":2000.00,"notes":"Weekly payment"}'
```

---

### 7. Payment Gateway Webhook
Universal webhook listener supporting:
1. **Safaricom M-PESA Daraja C2B Confirmation**
2. **Safaricom M-PESA STK Push Callbacks**
3. **Generic Bank / Payment Aggregator IPN**

Automatically identifies whether incoming payment belongs to **Pay In Full** (`OFT-XXXXX`) or **Lipa Mdogo Mdogo** (`LMM-XXXXX`).

- **URL:** `/api/payment-callback.php`
- **Method:** `POST`
- **Security:** Evaluates `X-Webhook-Secret` header or `?secret=` query parameter against `WEBHOOK_SECRET_KEY` (configured in `config/config.php`).

#### Payload Format A: Safaricom M-PESA C2B Confirmation
```json
{
  "TransactionType": "Pay Bill",
  "TransID": "QHJ93K81P",
  "TransTime": "20261101201530",
  "TransAmount": "1700.00",
  "BusinessShortCode": "600000",
  "BillRefNumber": "LMM-9481",
  "InvoiceNumber": "",
  "OrgAccountBalance": "150000.00",
  "ThirdPartyTransID": "",
  "MSISDN": "254712345678",
  "FirstName": "Amina"
}
```

#### Payload Format B: Safaricom M-PESA STK Push Callback
```json
{
  "Body": {
    "stkCallback": {
      "MerchantRequestID": "29115-34620561-1",
      "CheckoutRequestID": "ws_CO_01112026201530",
      "ResultCode": 0,
      "ResultDesc": "The service request is processed successfully.",
      "CallbackMetadata": {
        "Item": [
          { "Name": "Amount", "Value": 8499.00 },
          { "Name": "MpesaReceiptNumber", "Value": "QHJ93K81P" },
          { "Name": "TransactionDate", "Value": 20261101201530 },
          { "Name": "PhoneNumber", "Value": 254712345678 }
        ]
      }
    }
  }
}
```

#### Payload Format C: Generic Custom Gateway
```json
{
  "order_code": "OFT-A78B9",
  "amount": 8499.00,
  "reference": "BANK-TXN-90214",
  "phone": "254712345678",
  "method": "bank_transfer"
}
```

#### Success Response (200 OK)
Standard Daraja response acknowledged:
```json
{
  "ResultCode": 0,
  "ResultDesc": "Installment credited successfully"
}
```

#### Forbidden Response (403 Forbidden)
```json
{
  "ResultCode": 1,
  "ResultDesc": "Forbidden: Invalid webhook secret token"
}
```

#### Example Request (cURL)
```bash
curl -X POST "http://localhost:8000/api/payment-callback.php?secret=outfit_secure_webhook_key_2026" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Secret: outfit_secure_webhook_key_2026" \
  -d '{
    "TransactionType": "Pay Bill",
    "TransID": "QHJ93K81P",
    "TransAmount": "1700.00",
    "BillRefNumber": "LMM-9481",
    "MSISDN": "254712345678"
  }'
```

---

### 8. Admin Update Fulfillment Status
Updates the fulfillment pipeline status of an order and appends an audit event to the order status history.

- **URL:** `/admin/update-status.php`
- **Method:** `POST`
- **Security:** Requires active administrator session (`$_SESSION['admin_logged_in'] = true`).
- **Request Body (JSON):**

| Field | Type | Allowed Values | Description |
| :--- | :--- | :--- | :--- |
| `order_id` | `integer` | `> 0` | The primary key ID of the order |
| `fulfillment_status`| `string` | `placed`, `confirmed`, `processing`, `dispatched`, `delivered`, `cancelled` | Target milestone |
| `comment` | `string` | Optional string | Reason or logistics tracking note |

#### Example Payload
```json
{
  "order_id": 4,
  "fulfillment_status": "dispatched",
  "comment": "Dispatched via Rider RiderExpress - Waybill #NBO-882"
}
```

#### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Fulfillment status updated to DISPATCHED"
}
```

#### Error Response — Unauthorized (401 Unauthorized)
```json
{
  "success": false,
  "message": "Unauthorized: Administrator authentication required."
}
```

#### Example Request (cURL)
```bash
curl -X POST http://localhost:8000/admin/update-status.php \
  -H "Content-Type: application/json" \
  -b admin_cookies.txt \
  -d '{
    "order_id": 4,
    "fulfillment_status": "dispatched",
    "comment": "Assigned to driver John"
  }'
```

---

## 5. Testing with Postman & cURL

You can import this collection into Postman or test via the built-in browser UI simulators:

1. **Pay-In-Full Simulation:**
   - Open `order-confirmation.php?code=OFT-XXXXX`
   - Click the green **"Simulate M-Pesa Payment"** button.
2. **Lipa Mdogo Mdogo Simulation:**
   - Log into the customer portal at `lipa-mdogo-mdogo.php` (or use admin console `admin/lipa-mdogo.php`)
   - Enter an installment amount (e.g. `1,000`) and submit.
3. **Webhook Inspection Log:**
   - Inspect incoming raw payloads in real-time inside `storage/webhook.log`.

