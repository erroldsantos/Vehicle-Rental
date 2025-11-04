<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class PaymentController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->call->library('api');
        $this->call->model('Payment');
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
}
