<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * PayMongo Library for LavaLust
 * 
 * This library integrates PayMongo payment gateway with LavaLust framework
 * 
 * @package Vehicle Rental System
 * @author Your Name
 * @link https://developers.paymongo.com
 */
class Paymongo
{
    protected $_lava;
    protected $client;
    protected $secretKey;
    protected $publicKey;
    protected $logger;

    public function __construct()
    {
        $this->_lava = lava_instance();
        
        // Load PayMongo configuration
        $this->_lava->config->load('paymongo');
        $this->secretKey = config_item('paymongo_secret_key');
        $this->publicKey = config_item('paymongo_public_key');
        
        error_log('PayMongo init - Secret key length: ' . strlen($this->secretKey ?? ''));
        error_log('PayMongo init - Secret key prefix: ' . substr($this->secretKey ?? '', 0, 7));
        
        // Initialize logger
        $this->logger = load_class('logger', 'kernel');
        
        // Verify secret key exists
        if (empty($this->secretKey)) {
            $this->logger->log('error', 'PayMongo', 'Secret key not configured', __FILE__, __LINE__);
            throw new \Exception('PayMongo secret key not configured');
        }
        
        // Initialize PayMongo client
        try {
            $this->client = new \Paymongo\PaymongoClient($this->secretKey);
        } catch (\Exception $e) {
            $this->logger->log('error', 'PayMongo Init', $e->getMessage(), $e->getFile(), $e->getLine());
            throw $e;
        }
    }

    /**
     * Create a Payment Intent
     * 
     * @param array $data Payment data (amount, description, etc.)
     * @return object|false Payment intent object or false on failure
     */
    public function createPaymentIntent($data)
    {
        try {
            $amount = isset($data['amount']) ? (int)($data['amount'] * 100) : 0; // Convert to centavos
            $description = $data['description'] ?? 'Vehicle Rental Payment';
            $statement_descriptor = $data['statement_descriptor'] ?? 'RENTAL';
            
            $paymentIntent = $this->client->paymentIntents->create([
                'amount' => $amount,
                'currency' => 'PHP',
                'payment_method_allowed' => ['card', 'gcash', 'paymaya', 'grab_pay'],
                'description' => $description,
                'statement_descriptor' => $statement_descriptor,
                'metadata' => $data['metadata'] ?? []
            ]);
            
            return $paymentIntent;
        } catch (\Exception $e) {
            $this->logger->log('error', 'PayMongo', $e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        }
    }

    /**
     * Attach Payment Method to Payment Intent
     * 
     * @param string $intentId Payment Intent ID
     * @param string $paymentMethodId Payment Method ID
     * @param string|null $clientKey Client key for authentication
     * @return object|false Payment intent object or false on failure
     */
    public function attachPaymentIntent($intentId, $paymentMethodId, $clientKey = null)
    {
        try {
            $data = [
                'payment_method' => $paymentMethodId
            ];
            
            if ($clientKey) {
                $data['client_key'] = $clientKey;
            }
            
            $paymentIntent = $this->client->paymentIntents->attach($intentId, $data);
            
            return $paymentIntent;
        } catch (\Exception $e) {
            $this->logger->log('error', 'PayMongo Attach', $e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        }
    }

    /**
     * Create Payment Method
     * 
     * @param array $data Payment method data
     * @return object|false Payment method object or false on failure
     */
    public function createPaymentMethod($data)
    {
        try {
            $paymentMethod = $this->client->paymentMethods->create([
                'type' => $data['type'] ?? 'card',
                'details' => $data['details'] ?? [],
                'billing' => $data['billing'] ?? []
            ]);
            
            return $paymentMethod;
        } catch (\Exception $e) {
            $this->logger->log('error', 'PayMongo Payment Method', $e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        }
    }

    /**
     * Retrieve Payment Intent
     * 
     * @param string $intentId Payment Intent ID
     * @return object|false Payment intent object or false on failure
     */
    public function getPaymentIntent($intentId)
    {
        try {
            $paymentIntent = $this->client->paymentIntents->find($intentId);
            return $paymentIntent;
        } catch (\Exception $e) {
            $this->logger->log('error', 'PayMongo Retrieve', $e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        }
    }

    /**
     * Create a Source (for GCash, GrabPay, etc.)
     * 
     * @param array $data Source data
     * @return object|false Source object or false on failure
     */
    public function createSource($data)
    {
        try {
            $amount = isset($data['amount']) ? (int)($data['amount'] * 100) : 0;
            
            $sourceData = [
                'amount' => $amount,
                'currency' => $data['currency'] ?? 'PHP',
                'type' => $data['type'] ?? 'gcash',
                'redirect' => [
                    'success' => $data['success_url'] ?? (base_url() . 'payment/success'),
                    'failed' => $data['failed_url'] ?? (base_url() . 'payment/failed')
                ]
            ];
            
            // Add billing if provided
            if (isset($data['billing']) && !empty($data['billing'])) {
                $sourceData['billing'] = $data['billing'];
            }
            
            // Add metadata if provided
            if (isset($data['metadata']) && !empty($data['metadata'])) {
                $sourceData['metadata'] = $data['metadata'];
            }
            
            error_log('PayMongo createSource request: ' . json_encode($sourceData));
            
            $source = $this->client->sources->create($sourceData);
            
            error_log('PayMongo createSource response: ' . json_encode($source));
            
            return $source;
        } catch (\Paymongo\Exceptions\BaseException $e) {
            $errors = $e->getError();
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->detail;
            }
            error_log('PayMongo Source Error: ' . implode(', ', $errorMessages));
            error_log('PayMongo Source Trace: ' . $e->getTraceAsString());
            $this->logger->log('error', 'PayMongo Source', implode(', ', $errorMessages), $e->getFile(), $e->getLine());
            return false;
        } catch (\Exception $e) {
            error_log('PayMongo Source Generic Error: ' . $e->getMessage());
            error_log('PayMongo Source Code: ' . $e->getCode());
            error_log('PayMongo Source Trace: ' . $e->getTraceAsString());
            $this->logger->log('error', 'PayMongo Source', $e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        }
    }

    /**
     * Create a Webhook
     * 
     * @param array $data Webhook data
     * @return object|false Webhook object or false on failure
     */
    public function createWebhook($data)
    {
        try {
            $webhook = $this->client->webhooks->create([
                'url' => $data['url'],
                'events' => $data['events'] ?? [
                    'payment.paid',
                    'payment.failed',
                    'source.chargeable'
                ]
            ]);
            
            return $webhook;
        } catch (\Exception $e) {
            $this->logger->log('error', 'PayMongo Webhook', $e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        }
    }

    /**
     * Verify Webhook Signature
     * 
     * @param string $payload Request payload
     * @param string $signatureHeader Signature header
     * @param string $webhookSecret Webhook secret key
     * @return object|false Event object or false on failure
     */
    public function verifyWebhook($payload, $signatureHeader, $webhookSecret)
    {
        try {
            $event = $this->client->webhooks->constructEvent([
                'payload' => $payload,
                'signature_header' => $signatureHeader,
                'webhook_secret_key' => $webhookSecret
            ]);
            
            return $event;
        } catch (\Paymongo\Exceptions\SignatureVerificationException $e) {
            $this->logger->log('error', 'PayMongo Webhook Verification', $e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        } catch (\Exception $e) {
            $this->logger->log('error', 'PayMongo Webhook', $e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        }
    }

    /**
     * Create a Payment (charge a source)
     * 
     * @param array $data Payment data
     * @return object|false Payment object or false on failure
     */
    public function createPayment($data)
    {
        try {
            $amount = isset($data['amount']) ? (int)($data['amount'] * 100) : 0;
            
            $payment = $this->client->payments->create([
                'amount' => $amount,
                'currency' => 'PHP',
                'source' => [
                    'id' => $data['source_id'],
                    'type' => 'source'
                ],
                'description' => $data['description'] ?? 'Vehicle Rental Payment',
                'statement_descriptor' => $data['statement_descriptor'] ?? 'RENTAL'
            ]);
            
            return $payment;
        } catch (\Exception $e) {
            $this->logger->log('error', 'PayMongo Payment', $e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        }
    }

    /**
     * Retrieve Payment
     * 
     * @param string $paymentId Payment ID
     * @return object|false Payment object or false on failure
     */
    public function getPayment($paymentId)
    {
        try {
            $payment = $this->client->payments->find($paymentId);
            return $payment;
        } catch (\Exception $e) {
            $this->logger->log('error', 'PayMongo Retrieve Payment', $e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        }
    }

    /**
     * Get all payments
     * 
     * @return object|false Payments list or false on failure
     */
    public function getAllPayments()
    {
        try {
            $payments = $this->client->payments->all();
            return $payments;
        } catch (\Exception $e) {
            $this->logger->log('error', 'PayMongo List Payments', $e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        }
    }

    /**
     * Get Public Key (for frontend)
     * 
     * @return string Public key
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Format amount to centavos
     * 
     * @param float $amount Amount in peso
     * @return int Amount in centavos
     */
    public function toCentavos($amount)
    {
        return (int)($amount * 100);
    }

    /**
     * Format amount from centavos to peso
     * 
     * @param int $centavos Amount in centavos
     * @return float Amount in peso
     */
    public function toPeso($centavos)
    {
        return (float)($centavos / 100);
    }
}
?>
