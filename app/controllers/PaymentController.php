<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class PaymentController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->call->library('api');
        $this->call->library('paymongo');
        $this->call->model('Payment');
        $this->call->model('Booking');
    }
    
    public function index() {
        $this->api->require_method('GET');
        
        try {
            // Get all GET parameters if any exist
            $getAllParams = !empty($_GET) ? $this->io->get() : [];
            $filters = [
                'status' => isset($getAllParams['status']) ? $getAllParams['status'] : null,
                'booking_id' => isset($getAllParams['booking_id']) ? $getAllParams['booking_id'] : null,
                'payment_method' => isset($getAllParams['payment_method']) ? $getAllParams['payment_method'] : null
            ];
            
            $payments = $this->Payment->getAllPayments($filters);
            $stats = $this->Payment->getPaymentStats();
            
            $this->api->respond([
                'payments' => $payments,
                'stats' => $stats,
                'total' => count($payments)
            ]);
            
        } catch (Exception $e) {
            $this->api->respond_error('Error fetching payments: ' . $e->getMessage(), 500);
        }
    }
    
    public function stats() {
        $this->api->require_method('GET');
        
        try {
            $stats = $this->Payment->getPaymentStats();
            $this->api->respond($stats);
        } catch (Exception $e) {
            $this->api->respond_error('Error fetching payment statistics: ' . $e->getMessage(), 500);
        }
    }
    
    public function show($id) {
        $this->api->require_method('GET');
        
        try {
            $payment = $this->Payment->getPaymentById($id);
            
            if (!$payment) {
                $this->api->respond_error('Payment not found', 404);
                return;
            }
            
            $this->api->respond($payment);
        } catch (Exception $e) {
            $this->api->respond_error('Error fetching payment: ' . $e->getMessage(), 500);
        }
    }
    
    public function create() {
        $this->api->require_method('POST');
        
        try {
            $input = $this->api->body();
            $payment = $this->Payment->createPayment($input);
            $this->api->respond($payment, 201);
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
    
    public function update($id) {
        $this->api->require_method('PUT');
        
        try {
            $input = $this->api->body();
            $payment = $this->Payment->updatePayment($id, $input);
            $this->api->respond($payment);
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
    
    public function delete($id) {
        $this->api->require_method('DELETE');
        
        try {
            $this->Payment->deletePayment($id);
            $this->api->respond(['message' => 'Payment deleted successfully']);
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
    
    /**
     * Create PayMongo payment source for booking
     */
    public function create_booking_payment() {
        $this->api->require_method('POST');
        
        try {
            $input = $this->api->body();
            
            // Log the incoming request
            error_log('Payment Request: ' . json_encode($input));
            
            // Validate required fields
            if (empty($input['booking_id'])) {
                throw new Exception('Booking ID is required');
            }
            
            if (empty($input['payment_type']) || !in_array($input['payment_type'], ['full', 'downpayment'])) {
                throw new Exception('Payment type must be "full" or "downpayment"');
            }
            
            if (empty($input['payment_method']) || !in_array($input['payment_method'], ['gcash', 'paymaya', 'grab_pay', 'card'])) {
                throw new Exception('Invalid payment method');
            }
            
            // Get booking details
            $booking = $this->Booking->getBookingById($input['booking_id']);
            if (!$booking) {
                throw new Exception('Booking not found', 404);
            }
            
            error_log('Booking found: ' . json_encode($booking));
            
            if ($booking['status'] !== 'pending') {
                throw new Exception('Only pending bookings can be paid');
            }
            
            // Calculate payment amount
            $total_amount = floatval($booking['total_amount']);
            $payment_amount = $input['payment_type'] === 'downpayment' 
                ? $total_amount * 0.5  // 50% downpayment
                : $total_amount;        // Full payment
            
            // Create payment record first
            $payment_data = [
                'booking_id' => $input['booking_id'],
                'amount' => $payment_amount,
                'payment_method' => $input['payment_method'],
                'payment_date' => date('Y-m-d'),
                'status' => 'pending'
            ];
            
            $payment_id = $this->Payment->insert($payment_data);
            
            // Create PayMongo source (pass amount directly, not in centavos)
            $source = $this->paymongo->createSource([
                'type' => $input['payment_method'],
                'amount' => $payment_amount,  // Library will convert to centavos
                'currency' => 'PHP',
                'success_url' => base_url() . 'payment/success/' . $input['booking_id'],
                'failed_url' => base_url() . 'payment/failed/' . $input['booking_id'],
                'billing' => [
                    'name' => $booking['first_name'] . ' ' . $booking['last_name'],
                    'email' => $booking['email'],
                    'phone' => $booking['phone'] ?? '09000000000'
                ],
                'metadata' => [
                    'booking_id' => (string)$input['booking_id'],
                    'payment_id' => (string)$payment_id,
                    'payment_type' => $input['payment_type']
                ]
            ]);
            
            error_log('PayMongo Source created: ' . json_encode($source));
            
            if (!$source || $source === false) {
                throw new Exception('Failed to create payment source - PayMongo returned false');
            }
            
            // Get the checkout URL from the source
            $checkout_url = null;
            
            // Try different possible locations for checkout URL
            if (isset($source->redirect) && isset($source->redirect->checkout_url)) {
                $checkout_url = $source->redirect->checkout_url;
            } elseif (isset($source->redirect) && is_array($source->redirect) && isset($source->redirect['checkout_url'])) {
                $checkout_url = $source->redirect['checkout_url'];
            } elseif (is_object($source) && property_exists($source, 'attributes')) {
                if (isset($source->attributes->redirect->checkout_url)) {
                    $checkout_url = $source->attributes->redirect->checkout_url;
                } elseif (isset($source->attributes['redirect']['checkout_url'])) {
                    $checkout_url = $source->attributes['redirect']['checkout_url'];
                }
            }
            
            if (!$checkout_url) {
                error_log('PayMongo Source Response (no checkout URL): ' . print_r($source, true));
                throw new Exception('Payment source created but no checkout URL found in response');
            }
            
            error_log('Checkout URL found: ' . $checkout_url);
            
            $this->api->respond([
                'status' => 'success',
                'data' => [
                    'redirect_url' => $checkout_url,
                    'source_id' => $source->id ?? ($source->attributes->id ?? null),
                    'payment_id' => $payment_id,
                    'amount' => $payment_amount
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Payment Error: ' . $e->getMessage());
            error_log('Payment Error Trace: ' . $e->getTraceAsString());
            $this->api->respond_error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
    
    /**
     * PayMongo Webhook Handler
     */
    public function webhook() {
        try {
            $payload = @file_get_contents('php://input');
            $signature = isset($_SERVER['HTTP_PAYMONGO_SIGNATURE']) ? $_SERVER['HTTP_PAYMONGO_SIGNATURE'] : '';
            
            // Log webhook for debugging
            error_log('=== PayMongo Webhook Received ===');
            error_log('Payload: ' . $payload);
            error_log('Signature: ' . $signature);
            
            // Parse payload as JSON for testing
            $data = json_decode($payload);
            
            if (!$data || !isset($data->data)) {
                error_log('Invalid webhook payload structure');
                http_response_code(400);
                echo json_encode(['error' => 'Invalid payload']);
                return;
            }
            
            $eventType = $data->data->attributes->type ?? null;
            error_log('Event type: ' . $eventType);
            
            // Handle different event types (skip signature verification for now)
            switch ($eventType) {
                case 'source.chargeable':
                    error_log('Handling source.chargeable event');
                    $this->handleSourceChargeable($data);
                    break;
                    
                case 'payment.paid':
                    error_log('Handling payment.paid event');
                    $this->handlePaymentPaid($data);
                    break;
                    
                case 'payment.failed':
                    error_log('Handling payment.failed event');
                    $this->handlePaymentFailed($data);
                    break;
                    
                default:
                    error_log('Unhandled event type: ' . $eventType);
            }
            
            http_response_code(200);
            echo json_encode(['received' => true, 'event' => $eventType]);
            
        } catch (Exception $e) {
            error_log('Webhook Error: ' . $e->getMessage());
            error_log('Webhook Trace: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle source.chargeable event (GCash, GrabPay, PayMaya)
     */
    private function handleSourceChargeable($event) {
        error_log('=== handleSourceChargeable ===');
        error_log('Event data: ' . json_encode($event));
        
        // Access nested data structure correctly
        $source = $event->data->attributes->data->attributes;
        $booking_id = $source->metadata->booking_id ?? null;
        $payment_id = $source->metadata->payment_id ?? null;
        
        error_log('Booking ID: ' . $booking_id);
        error_log('Payment ID: ' . $payment_id);
        
        if (!$booking_id || !$payment_id) {
            error_log('Missing booking_id or payment_id in metadata');
            return;
        }
        
        // Create payment to charge the source
        $sourceId = $event->data->attributes->data->id;
        error_log('Source ID: ' . $sourceId);
        
        $payment = $this->paymongo->createPayment([
            'amount' => $source->amount / 100, // Convert from centavos
            'source_id' => $sourceId,
            'currency' => 'PHP',
            'description' => 'Vehicle Rental Booking #' . $booking_id,
            'metadata' => [
                'booking_id' => $booking_id,
                'payment_id' => $payment_id
            ]
        ]);
        
        error_log('Payment created: ' . json_encode($payment));
        
        if ($payment && isset($payment->attributes) && $payment->attributes->status === 'paid') {
            error_log('Payment is paid, confirming booking');
            $this->confirmBookingAfterPayment($booking_id, $payment_id);
        }
    }
    
    /**
     * Handle payment.paid event
     */
    private function handlePaymentPaid($event) {
        error_log('=== handlePaymentPaid ===');
        error_log('Event data: ' . json_encode($event));
        
        // Access nested data structure correctly
        $payment = $event->data->attributes->data->attributes;
        $booking_id = $payment->metadata->booking_id ?? null;
        $payment_id = $payment->metadata->payment_id ?? null;
        
        error_log('Booking ID from payment.paid: ' . $booking_id);
        error_log('Payment ID from payment.paid: ' . $payment_id);
        
        if ($booking_id && $payment_id) {
            error_log('Confirming booking from payment.paid event');
            $this->confirmBookingAfterPayment($booking_id, $payment_id);
        } else {
            error_log('Missing booking_id or payment_id in payment.paid metadata');
        }
    }
    
    /**
     * Handle payment.failed event
     */
    private function handlePaymentFailed($event) {
        error_log('=== handlePaymentFailed ===');
        $payment = $event->data->attributes->data->attributes;
        $payment_id = $payment->metadata->payment_id ?? null;
        
        error_log('Payment ID from payment.failed: ' . $payment_id);
        
        if ($payment_id) {
            $this->Payment->updatePayment($payment_id, ['status' => 'failed']);
            error_log('Payment #' . $payment_id . ' marked as failed');
        }
    }
    
    /**
     * Confirm booking after successful payment
     */
    private function confirmBookingAfterPayment($booking_id, $payment_id) {
        // Update payment status
        $this->Payment->updatePayment($payment_id, [
            'status' => 'completed',
            'payment_date' => date('Y-m-d')
        ]);
        
        // Automatically confirm the booking
        $this->Booking->updateBooking($booking_id, [
            'status' => 'confirmed'
        ]);
        
        error_log("Booking #{$booking_id} automatically confirmed after payment #{$payment_id}");
    }

    /**
     * Landing page for successful payment redirect
     */
    public function success($booking_id) {
        $frontend_url = rtrim(getenv('FRONTEND_URL') ?: base_url().'frontend/', '/');
        $redirect_url = $frontend_url . '/payment/success/' . urlencode($booking_id);
        header('Location: ' . $redirect_url);
        exit;
    }

    /**
     * Landing page for failed payment redirect
     */
    public function failed($booking_id) {
        $frontend_url = rtrim(getenv('FRONTEND_URL') ?: base_url().'frontend/', '/');
        $redirect_url = $frontend_url . '/payment/failed/' . urlencode($booking_id);
        header('Location: ' . $redirect_url);
        exit;
    }
}
