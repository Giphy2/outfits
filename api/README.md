# OUTFIT API Directory

This directory contains the REST API endpoints powering checkout, customer eligibility verification, OTP authentication, order tracking, payment simulations, and payment gateway webhooks.

For complete, detailed specifications, request/response schemas, and cURL examples, see the full documentation:
👉 **[Full API Documentation](../docs/api.md)**

## Quick Endpoint Map

| File | Method | Description |
| :--- | :--- | :--- |
| `check-customer.php` | `GET` / `POST` | Verifies whether a phone number is registered in DB for Lipa Mdogo Mdogo |
| `otp.php` | `POST` | Requests (`action=send`) or verifies (`action=verify`) 6-digit OTP code |
| `create-order.php` | `POST` | Creates an order (`OFT-XXXXX`) or Lipa Mdogo Mdogo plan (`LMM-XXXXX`) |
| `track-order.php` | `GET` | Returns order details, payment reconciliation, and fulfillment timeline |
| `simulate-payment.php` | `POST` | Simulates full M-Pesa / Bank payment against an order code |
| `simulate-installment.php` | `POST` | Posts an installment payment towards a Lipa Mdogo Mdogo agreement |
| `payment-callback.php` | `POST` | Universal gateway webhook for Safaricom Daraja C2B/STK Push & Bank IPN |
| `../admin/update-status.php` | `POST` | Admin-only fulfillment milestone update API |

