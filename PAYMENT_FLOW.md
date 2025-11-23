# Automatic Booking Confirmation via Payment

## Overview
The system now automatically confirms bookings when customers complete payment. Admin confirmation is no longer required - payment triggers automatic confirmation.

## Payment Flow

### 1. **Booking Creation**
- Customer creates a booking through the system
- Booking status: **PENDING**
- Booking is saved but not confirmed

### 2. **Payment Initiation**
- Customer clicks "Pay Now" button on pending booking
- System presents payment options:
  - **Full Payment** (100% of total amount)
  - **Downpayment** (30% of total amount)
- Customer selects payment method:
  - GCash
  - PayMaya
  - GrabPay

### 3. **Payment Processing**
- System creates PayMongo payment source
- Customer is redirected to PayMongo checkout page
- Customer completes payment through chosen method

### 4. **Automatic Confirmation**
- PayMongo webhook sends `source.chargeable` event
- System charges the payment source
- On successful payment (`payment.paid` event):
  - Payment record status → **COMPLETED**
  - Booking status → **CONFIRMED** ✅ (automatic)
- Customer receives confirmation

### 5. **Booking Lifecycle** (Post-Payment)
```
PENDING → (payment) → CONFIRMED → (start date) → ACTIVE → (pickup) → ONGOING → (return) → RETURNED → (admin) → COMPLETED
```

## Technical Implementation

### API Endpoints

**Create Payment for Booking**
```
POST /api/payments/booking
```
Request:
```json
{
  "booking_id": 123,
  "payment_type": "full",  // or "downpayment"
  "payment_method": "gcash"  // or "paymaya", "grab_pay"
}
```
Response:
```json
{
  "checkout_url": "https://pm.link/...",
  "source_id": "src_...",
  "payment_id": 456,
  "amount": 5000.00
}
```

**PayMongo Webhook** (automatic)
```
POST /api/webhook/paymongo
```
Headers:
```
PayMongo-Signature: <webhook_signature>
```

### Database Tables

**payments**
- `id` - Payment ID
- `booking_id` - Related booking
- `amount` - Payment amount
- `payment_method` - gcash, paymaya, grab_pay
- `status` - pending, completed, failed
- `payment_date` - Date of payment

**bookings**
- `status` - pending → **confirmed** (auto-updated on payment)

## Webhook Setup

1. Go to [PayMongo Dashboard](https://dashboard.paymongo.com/developers/webhooks)
2. Create new webhook
3. URL: `https://yourdomain.com/api/webhook/paymongo`
4. Select events:
   - ✅ `source.chargeable`
   - ✅ `payment.paid`
   - ✅ `payment.failed`
5. Copy webhook secret to `.env`:
   ```
   PAYMONGO_WEBHOOK_SECRET=whsec_xxx
   ```

## Payment Types

### Full Payment
- Amount: 100% of booking total
- Customer pays entire rental amount upfront
- Booking confirmed immediately upon payment

### Downpayment (30%)
- Amount: 30% of booking total
- Customer pays partial amount to secure booking
- Remaining 70% can be paid later or on pickup
- Booking confirmed immediately upon downpayment

## Admin Dashboard Changes

### Removed Features
- ❌ Manual "Confirm Booking" button
- ❌ Admin approval workflow for bookings

### New Features
- ✅ "Pay Now" button on pending bookings
- ✅ Automatic status updates via webhook
- ✅ Payment history tracking

## Customer Experience

1. **Browse Vehicles** → Select dates and vehicle
2. **Create Booking** → Booking created as PENDING
3. **Pay Now** → Choose payment type and method
4. **Complete Payment** → Redirected to PayMongo
5. **Confirmation** → Automatic! Booking status → CONFIRMED
6. **Start Date Arrives** → Status auto-updates to ACTIVE
7. **Pickup Vehicle** → Admin marks as ONGOING
8. **Return Vehicle** → Admin marks as RETURNED
9. **Final Processing** → Admin marks as COMPLETED

## Testing

### Test Mode (Sandbox)
Use PayMongo test credentials:

**Test Payment Flow:**
```javascript
// Frontend - initiate payment
const response = await axios.post('/api/payments/booking', {
  booking_id: 1,
  payment_type: 'downpayment',
  payment_method: 'gcash'
})

// Open checkout URL
window.open(response.checkout_url)

// PayMongo handles payment → webhook triggers
// Booking automatically confirmed ✅
```

**Verify Webhook:**
```bash
# Check logs for webhook events
tail -f /var/log/apache2/error.log | grep PayMongo
```

## Error Handling

### Payment Failed
- Payment status → FAILED
- Booking remains PENDING
- Customer can retry payment

### Webhook Issues
- System logs all webhook events
- Manual fallback: Admin can still edit booking status directly

## Security

- ✅ Webhook signature verification
- ✅ HTTPS required for webhooks
- ✅ API keys in environment variables
- ✅ Payment amount validation server-side

## Support

For payment issues:
- Check PayMongo Dashboard for transaction logs
- Review webhook delivery attempts
- Verify payment status in database
- Contact PayMongo support if needed

## Migration Notes

Existing confirmed bookings are not affected. The new flow only applies to:
- New bookings created after implementation
- Pending bookings that haven't been manually confirmed yet
