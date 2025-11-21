# PayMongo Integration Guide

This guide shows how to integrate PayMongo payment gateway with your Vehicle Rental System using LavaLust framework.

## Installation

1. **Install PayMongo PHP package** (Already done!)
   ```bash
   composer require paymongo/paymongo-php
   ```

2. **Configure your API keys**

   Copy `.env.example` to `.env` and update with your PayMongo keys:
   ```env
   PAYMONGO_SECRET_KEY=sk_test_your_secret_key_here
   PAYMONGO_PUBLIC_KEY=pk_test_your_public_key_here
   PAYMONGO_WEBHOOK_SECRET=whsec_your_webhook_secret_here
   ```

   Get your keys from: https://dashboard.paymongo.com/developers

3. **Update configuration**

   Edit `app/config/paymongo.php` and set your preferences:
   - Payment methods to accept
   - Success/Failed URLs
   - Webhook events to listen to

## Usage (LavaLust Way)

### 1. Load the Library

In your controller:

```php
class BookingsController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Load PayMongo library (LavaLust way)
        $this->call->library('paymongo');
    }
}
```

### 2. Create Payment Intent (for Card Payments)

```php
public function create_payment()
{
    $paymentIntent = $this->paymongo->createPaymentIntent([
        'amount' => 5000, // PHP 5,000
        'description' => 'Vehicle Rental - Booking #123',
        'metadata' => [
            'booking_id' => 123,
            'customer_id' => $this->session->userdata('user_id')
        ]
    ]);
    
    if ($paymentIntent) {
        // Return client_key to frontend
        $clientKey = $paymentIntent->client_key;
        $intentId = $paymentIntent->id;
    }
}
```

### 3. Create Source Payment (GCash, GrabPay, PayMaya)

```php
public function create_gcash_payment()
{
    $source = $this->paymongo->createSource([
        'type' => 'gcash', // or 'grab_pay', 'paymaya'
        'amount' => 5000,
        'success_url' => base_url() . 'payment/success/123',
        'failed_url' => base_url() . 'payment/failed/123',
        'billing' => [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '09171234567'
        ]
    ]);
    
    if ($source) {
        // Redirect user to checkout URL
        redirect($source->redirect->checkout_url);
    }
}
```

### 4. Handle Webhooks

Create a webhook endpoint in your controller:

```php
public function webhook()
{
    $payload = @file_get_contents('php://input');
    $signatureHeader = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
    $webhookSecret = config_item('paymongo_webhook_secret');
    
    $event = $this->paymongo->verifyWebhook($payload, $signatureHeader, $webhookSecret);
    
    if ($event) {
        switch ($event->type) {
            case 'payment.paid':
                // Update booking as paid
                $bookingId = $event->resource->metadata['booking_id'];
                $this->updateBookingStatus($bookingId, 'paid');
                break;
                
            case 'payment.failed':
                // Handle failed payment
                break;
                
            case 'source.chargeable':
                // Charge the source (for GCash, GrabPay, etc.)
                $this->paymongo->createPayment([
                    'amount' => $this->paymongo->toPeso($event->resource->amount),
                    'source_id' => $event->resource->id
                ]);
                break;
        }
        
        http_response_code(200);
    } else {
        http_response_code(400);
    }
}
```

## Available Methods

### Payment Intents (for Card Payments)

- `createPaymentIntent($data)` - Create a payment intent
- `attachPaymentIntent($intentId, $paymentMethodId, $clientKey)` - Attach payment method
- `getPaymentIntent($intentId)` - Retrieve payment intent details

### Sources (for GCash, GrabPay, etc.)

- `createSource($data)` - Create a payment source
- `createPayment($data)` - Charge a source

### Payment Methods

- `createPaymentMethod($data)` - Create a payment method (card details)

### Webhooks

- `createWebhook($data)` - Register a webhook
- `verifyWebhook($payload, $signature, $secret)` - Verify webhook signature

### Utilities

- `toCentavos($amount)` - Convert PHP to centavos (5000 -> 500000)
- `toPeso($centavos)` - Convert centavos to PHP (500000 -> 5000)
- `getPublicKey()` - Get public key for frontend

## Frontend Integration

### For Card Payments

1. Use the PayMongo.js library in your Vue.js frontend
2. Pass the `public_key` and `client_key` from backend
3. Collect card details and create payment method
4. Attach payment method to payment intent

Example:
```javascript
// In your Vue component
const paymongo = window.Paymongo(publicKey);

const paymentMethod = await paymongo.createPaymentMethod({
  type: 'card',
  details: {
    card_number: '4343434343434345',
    exp_month: 12,
    exp_year: 2025,
    cvc: '123'
  },
  billing: {
    name: 'Juan Dela Cruz',
    email: 'juan@example.com',
    phone: '09171234567'
  }
});

// Then attach to payment intent via your API
await axios.post('/api/payment/attach', {
  intent_id: intentId,
  payment_method_id: paymentMethod.id
});
```

### For E-Wallet Payments (GCash, GrabPay)

Simply redirect users to the checkout URL returned from `createSource()`.

## Webhook Setup

1. Go to https://dashboard.paymongo.com/developers/webhooks
2. Click "Add Webhook"
3. Enter your webhook URL: `https://yourdomain.com/api/webhook/paymongo`
4. Select events to listen to:
   - payment.paid
   - payment.failed
   - source.chargeable
5. Copy the webhook secret to your `.env` file

## Testing

Use PayMongo's test credentials:

**Test Card Numbers:**
- Success: `4343434343434345`
- 3D Secure: `4571736000000075`
- Insufficient Funds: `4571736000001106`

**Test GCash:**
Use the test environment and follow the mock payment flow.

## Security Notes

1. ✅ **DO NOT** commit `.env` file to Git
2. ✅ **DO** use environment variables for API keys
3. ✅ **DO** verify webhook signatures
4. ✅ **DO** use HTTPS in production
5. ✅ **DO** validate payment amounts on server-side

## Support

- PayMongo Documentation: https://developers.paymongo.com
- PayMongo Dashboard: https://dashboard.paymongo.com
- PayMongo Support: support@paymongo.com

## Example Controller

See `app/controllers/PaymentController_Example.php` for complete implementation examples.
