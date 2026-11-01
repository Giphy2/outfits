[Customer adds items to Cart]
              ↓
[Checkout: Enter Name, Phone, Address]
              ↓
[Click "Generate Payment Code"]
              ↓
Backend reserves items & generates unique code (e.g. OFT-8429K)
              ↓
[Order Confirmation Page]
• Shows unique code with 1-click copy button
• Shows M-PESA Paybill / Bank instructions
• Features interactive Development Payment Simulator
              ↓
[Payment Verified] (via Simulator or Webhook)
• Backend marks order as PAID
• Fulfillment advances to CONFIRMED
• Confirmation page auto-detects payment without reload
              ↓
[Customer Tracking Page: track-order.php]
• Customer enters code anytime to inspect order progress and delivery timeline

C:\xampp\php\php.exe -S localhost:8000

Add an item to your cart from index.php.
Go to checkout and click "Generate Payment Code".
On the confirmation page, test the "Simulate Full Payment" button to watch the backend automatically verify and update the status in real time!
