<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * ------------------------------------------------------------------
 * PayMongo Configuration
 * ------------------------------------------------------------------
 * 
 * Configure your PayMongo API keys here
 * Get your keys from: https://dashboard.paymongo.com/developers
 * 
 * IMPORTANT: Never commit your live/production keys to version control
 * Use environment variables in production
 */

/*
|--------------------------------------------------------------------------
| PayMongo Secret Key
|--------------------------------------------------------------------------
|
| Your PayMongo Secret Key (starts with sk_)
| Use sk_test_ for testing and sk_live_ for production
|
*/
$config['paymongo_secret_key'] = getenv('PAYMONGO_SECRET_KEY') ?: 'sk_test_your_secret_key_here';

/*
|--------------------------------------------------------------------------
| PayMongo Public Key
|--------------------------------------------------------------------------
|
| Your PayMongo Public Key (starts with pk_)
| Use pk_test_ for testing and pk_live_ for production
|
*/
$config['paymongo_public_key'] = getenv('PAYMONGO_PUBLIC_KEY') ?: 'pk_test_your_public_key_here';

/*
|--------------------------------------------------------------------------
| PayMongo Webhook Secret
|--------------------------------------------------------------------------
|
| Your PayMongo Webhook Secret Key (starts with whsec_)
| Used to verify webhook signatures
|
*/
$config['paymongo_webhook_secret'] = getenv('PAYMONGO_WEBHOOK_SECRET') ?: '';

/*
|--------------------------------------------------------------------------
| Payment Method Options
|--------------------------------------------------------------------------
|
| Available payment methods: card, gcash, paymaya, grab_pay, billease
|
*/
$config['paymongo_payment_methods'] = [
    'card',
    'gcash',
    'paymaya',
    'grab_pay'
];

/*
|--------------------------------------------------------------------------
| Webhook Events
|--------------------------------------------------------------------------
|
| Events to listen for in webhooks
|
*/
$config['paymongo_webhook_events'] = [
    'payment.paid',
    'payment.failed',
    'source.chargeable',
    'payment_intent.succeeded',
    'payment_intent.payment_failed'
];

/*
|--------------------------------------------------------------------------
| Return URLs
|--------------------------------------------------------------------------
|
| URLs for successful and failed payments
|
*/
$config['paymongo_success_url'] = 'https://vehicle-rental-production-6cce.up.railway.app/payment/success';
$config['paymongo_failed_url'] = 'https://vehicle-rental-production-6cce.up.railway.app/payment/failed';
$config['paymongo_webhook_url'] = 'https://vehicle-rental-production-6cce.up.railway.app/api/webhook/paymongo';

/*
|--------------------------------------------------------------------------
| Currency
|--------------------------------------------------------------------------
|
| Default currency (PayMongo currently only supports PHP)
|
*/
$config['paymongo_currency'] = 'PHP';

/*
|--------------------------------------------------------------------------
| Statement Descriptor
|--------------------------------------------------------------------------
|
| This will appear on your customer's credit card statement
| Maximum 22 characters
|
*/
$config['paymongo_statement_descriptor'] = 'VEHICLE RENTAL';

?>
