<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once APP_DIR . 'controllers/ApiController.php';

class PaymentController extends ApiController {
    
    protected $pdo;
    
    public function __construct() {
        parent::__construct();
        // Get PDO connection from Database helper
        $this->pdo = $this->db->getConnection();
    }
    
    /**
     * Get all payments with related booking and user information
     * GET /api/payments
     */
    public function index() {
        $this->api->require_method('GET');
        
        try {
            $query = "SELECT p.id, p.booking_id, p.amount, p.payment_date, p.payment_method, p.status,
                             b.booking_reference, b.start_date, b.end_date, b.total_amount as booking_total,
                             u.first_name, u.last_name, u.email,
                             v.brand, v.model, v.plate_number
                      FROM payments p
                      LEFT JOIN bookings b ON p.booking_id = b.id
                      LEFT JOIN users u ON b.user_id = u.id
                      LEFT JOIN vehicles v ON b.vehicle_id = v.id
                      WHERE p.deleted_at IS NULL
                      ORDER BY p.payment_date DESC, p.id DESC";
            
            $stmt = $this->pdo->query($query);
            $payments = $stmt->fetchAll();
            
            $this->api->respond([
                'payments' => $payments,
                'total' => count($payments)
            ]);
            
        } catch (Exception $e) {
            $this->api->respond_error('Error fetching payments: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get payment statistics
     * GET /api/payments/stats
     */
    public function stats() {
        $this->api->require_method('GET');
        
        try {
            // Get payment statistics
            $statsQuery = "SELECT 
                              SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                              COUNT(CASE WHEN status = 'completed' THEN 1 END) as paid_count,
                              COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                              COUNT(CASE WHEN status = 'pending' AND payment_date < CURDATE() THEN 1 END) as overdue_count
                           FROM payments 
                           WHERE deleted_at IS NULL";
            
            $stmt = $this->pdo->query($statsQuery);
            $stats = $stmt->fetch();
            
            // If no payments exist, return zeros
            if (!$stats || $stats['total_revenue'] === null) {
                $stats = [
                    'total_revenue' => '0.00',
                    'paid_count' => 0,
                    'pending_count' => 0,
                    'overdue_count' => 0
                ];
            }
            
            $this->api->respond($stats);
            
        } catch (Exception $e) {
            $this->api->respond_error('Error fetching payment statistics: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get a specific payment
     * GET /api/payments/{id}
     */
    public function show($id) {
        $this->api->require_method('GET');
        
        try {
            $query = "SELECT p.*, b.booking_reference, b.start_date, b.end_date,
                             u.first_name, u.last_name, u.email,
                             v.brand, v.model, v.plate_number
                      FROM payments p
                      LEFT JOIN bookings b ON p.booking_id = b.id
                      LEFT JOIN users u ON b.user_id = u.id
                      LEFT JOIN vehicles v ON b.vehicle_id = v.id
                      WHERE p.id = ? AND p.deleted_at IS NULL";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id]);
            $payment = $stmt->fetch();
            
            if (!$payment) {
                $this->api->respond_error('Payment not found', 404);
                return;
            }
            
            $this->api->respond($payment);
            
        } catch (Exception $e) {
            $this->api->respond_error('Error fetching payment: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Create a new payment
     * POST /api/payments
     */
    public function create() {
        $this->api->require_method('POST');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required = ['booking_id', 'amount', 'payment_method'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    $this->api->respond_error($field . ' is required', 400);
                    return;
                }
            }
            
            // Set default values
            $payment_date = $input['payment_date'] ?? date('Y-m-d');
            $status = $input['status'] ?? 'pending';
            
            $stmt = $this->pdo->prepare("INSERT INTO payments (booking_id, amount, payment_date, payment_method, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['booking_id'],
                $input['amount'],
                $payment_date,
                $input['payment_method'],
                $status
            ]);
            
            $paymentId = $this->pdo->lastInsertId();
            
            // Return the created payment
            $this->show($paymentId);
            
        } catch (Exception $e) {
            $this->api->respond_error('Error creating payment: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update payment status
     * PUT /api/payments/{id}
     */
    public function update($id) {
        $this->api->require_method('PUT');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Check if payment exists
            $stmt = $this->pdo->prepare("SELECT id FROM payments WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                $this->api->respond_error('Payment not found', 404);
                return;
            }
            
            $updates = [];
            $params = [];
            
            if (isset($input['status'])) {
                $updates[] = "status = ?";
                $params[] = $input['status'];
            }
            
            if (isset($input['amount'])) {
                $updates[] = "amount = ?";
                $params[] = $input['amount'];
            }
            
            if (isset($input['payment_method'])) {
                $updates[] = "payment_method = ?";
                $params[] = $input['payment_method'];
            }
            
            if (isset($input['payment_date'])) {
                $updates[] = "payment_date = ?";
                $params[] = $input['payment_date'];
            }
            
            if (empty($updates)) {
                $this->api->respond_error('No valid fields to update', 400);
                return;
            }
            
            $params[] = $id;
            $query = "UPDATE payments SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            // Return updated payment
            $this->show($id);
            
        } catch (Exception $e) {
            $this->api->respond_error('Error updating payment: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete a payment (soft delete)
     * DELETE /api/payments/{id}
     */
    public function delete($id) {
        $this->api->require_method('DELETE');
        
        try {
            $stmt = $this->pdo->prepare("UPDATE payments SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                $this->api->respond_error('Payment not found', 404);
                return;
            }
            
            $this->api->respond(['message' => 'Payment deleted successfully']);
            
        } catch (Exception $e) {
            $this->api->respond_error('Error deleting payment: ' . $e->getMessage(), 500);
        }
    }
}
?>