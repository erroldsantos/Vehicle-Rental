<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Payment Model - ORM based
 */
class Payment extends Model {
    protected $table = 'payments';
    protected $primary_key = 'id';
    protected $soft_delete = true;
    protected $fillable = ['booking_id', 'amount', 'payment_date', 'payment_method', 'payment_type', 'status'];

    /**
     * Get all payments with related booking, user, and vehicle information
     */
    public function getAllPayments($filters = []) {
        $query = "SELECT p.id, p.booking_id, p.amount, p.payment_date, p.payment_method, p.payment_type, p.status,
                         b.booking_reference, b.start_date, b.end_date, b.total_amount as booking_total,
                         u.first_name, u.last_name, u.email,
                         CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                         v.brand, v.model, v.plate_number,
                         CONCAT(v.brand, ' ', v.model, ' - ', v.plate_number) as vehicle_display
                  FROM payments p
                  LEFT JOIN bookings b ON p.booking_id = b.id
                  LEFT JOIN users u ON b.user_id = u.id
                  LEFT JOIN vehicles v ON b.vehicle_id = v.id
                  WHERE p.deleted_at IS NULL";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $query .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['booking_id'])) {
            $query .= " AND p.booking_id = ?";
            $params[] = $filters['booking_id'];
        }
        
        if (!empty($filters['payment_method'])) {
            $query .= " AND p.payment_method = ?";
            $params[] = $filters['payment_method'];
        }
        
        $query .= " ORDER BY p.payment_date DESC, p.id DESC";
        
        $stmt = $this->db->raw($query, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get payment by ID with related information
     */
    public function getPaymentById($id) {
        $query = "SELECT p.*, 
                         b.booking_reference, b.start_date, b.end_date, b.total_amount as booking_total,
                         u.first_name, u.last_name, u.email,
                         CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                         v.brand, v.model, v.plate_number,
                         CONCAT(v.brand, ' ', v.model, ' - ', v.plate_number) as vehicle_display
                  FROM payments p
                  LEFT JOIN bookings b ON p.booking_id = b.id
                  LEFT JOIN users u ON b.user_id = u.id
                  LEFT JOIN vehicles v ON b.vehicle_id = v.id
                  WHERE p.id = ? AND p.deleted_at IS NULL";
        
        $stmt = $this->db->raw($query, [$id]);
        $result = $stmt->fetchAll();
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Create new payment using ORM
     */
    public function createPayment($data) {
        // Validate required fields
        $required = ['booking_id', 'amount', 'payment_method'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }

        // Validate amount
        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new Exception("Amount must be a positive number");
        }

        // Validate booking exists
        $stmt = $this->db->raw("SELECT id FROM bookings WHERE id = ? AND deleted_at IS NULL", [$data['booking_id']]);
        $booking = $stmt->fetchAll();
        if (empty($booking)) {
            throw new Exception("Booking not found", 404);
        }

        // Set defaults
        $data['payment_date'] = $data['payment_date'] ?? date('Y-m-d');
        $data['status'] = $data['status'] ?? 'pending';

        // Use ORM insert
        $this->insert($data);
        
        // Get last inserted ID
        $stmt = $this->db->raw("SELECT LAST_INSERT_ID() as id");
        $result = $stmt->fetch();
        $id = $result['id'];
        
        // Return created payment with details
        return $this->getPaymentById($id);
    }

    /**
     * Update payment using ORM
     */
    public function updatePayment($id, $data) {
        // Check if payment exists
        $payment = $this->getPaymentById($id);
        if (!$payment) {
            throw new Exception("Payment not found", 404);
        }

        // Validate amount if provided
        if (isset($data['amount']) && (!is_numeric($data['amount']) || $data['amount'] <= 0)) {
            throw new Exception("Amount must be a positive number");
        }

        // Use ORM update
        $this->update($id, $data);
        
        // Return updated payment
        return $this->getPaymentById($id);
    }

    /**
     * Delete payment using ORM soft delete
     */
    public function deletePayment($id) {
        // Check if payment exists
        $payment = $this->getPaymentById($id);
        if (!$payment) {
            throw new Exception("Payment not found", 404);
        }

        // Use ORM soft delete
        return $this->soft_delete($id);
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStats() {
        $query = "SELECT 
                      SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                      SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_revenue,
                      COUNT(CASE WHEN status = 'completed' THEN 1 END) as paid_count,
                      COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                      COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count,
                      COUNT(CASE WHEN status = 'pending' AND payment_date < CURDATE() THEN 1 END) as overdue_count,
                      COUNT(*) as total_payments
                   FROM payments 
                   WHERE deleted_at IS NULL";
        
        $stmt = $this->db->raw($query);
        $stats = $stmt->fetch();
        
        // Ensure all values are set
        if (!$stats || $stats['total_revenue'] === null) {
            return [
                'total_revenue' => '0.00',
                'pending_revenue' => '0.00',
                'paid_count' => 0,
                'pending_count' => 0,
                'failed_count' => 0,
                'overdue_count' => 0,
                'total_payments' => 0
            ];
        }
        
        return $stats;
    }

    /**
     * Get payments by booking ID
     */
    public function getPaymentsByBooking($booking_id) {
        $query = "SELECT * FROM payments WHERE booking_id = ? AND deleted_at IS NULL ORDER BY payment_date DESC";
        $stmt = $this->db->raw($query, [$booking_id]);
        return $stmt->fetchAll();
    }

    /**
     * Mark payment as completed
     */
    public function completePayment($id) {
        return $this->updatePayment($id, ['status' => 'completed']);
    }

    /**
     * Mark payment as failed
     */
    public function failPayment($id) {
        return $this->updatePayment($id, ['status' => 'failed']);
    }
    
    /**
     * Get bookings that need payment
     * Returns bookings with downpayment or pending damage repairs
     */
    public function getBookingsNeedingPayment() {
        $query = "SELECT DISTINCT b.id, b.booking_reference, 
                         CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                         b.total_amount,
                         COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END), 0) as paid_amount,
                         COALESCE(SUM(CASE WHEN p.status = 'pending' THEN p.amount ELSE 0 END), 0) as pending_amount,
                         'downpayment' as reason
                  FROM bookings b
                  LEFT JOIN users u ON b.user_id = u.id
                  LEFT JOIN payments p ON b.id = p.booking_id
                  WHERE b.deleted_at IS NULL 
                  AND b.status IN ('confirmed', 'active', 'ongoing', 'returned')
                  GROUP BY b.id, b.booking_reference, u.first_name, u.last_name, b.total_amount
                  HAVING paid_amount < b.total_amount AND (paid_amount + pending_amount) < b.total_amount
                  
                  UNION
                  
                  SELECT DISTINCT b.id, b.booking_reference,
                         CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                         m.cost as total_amount,
                         0 as paid_amount,
                         0 as pending_amount,
                         'damage' as reason
                  FROM bookings b
                  LEFT JOIN users u ON b.user_id = u.id
                  INNER JOIN maintenance m ON b.id = m.booking_id
                  WHERE b.deleted_at IS NULL
                  AND m.deleted_at IS NULL
                  AND m.status = 'pending'
                  
                  ORDER BY booking_reference";
        
        $stmt = $this->db->raw($query);
        return $stmt->fetchAll();
    }
}
?>
