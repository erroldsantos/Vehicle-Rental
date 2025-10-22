<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Booking extends Model {
    
    protected $table = 'bookings';
    protected $primaryKey = 'id';
    
    /**
     * Get all bookings with user and vehicle details
     */
    public function getAllBookings($filters = []) {
        $query = "SELECT b.id, b.booking_reference, b.start_date, b.end_date, 
                         b.total_amount, b.status, b.notes, b.created_at,
                         u.first_name, u.last_name, u.email,
                         v.brand, v.model, v.plate_number, v.daily_rate
                  FROM bookings b
                  LEFT JOIN users u ON b.user_id = u.id
                  LEFT JOIN vehicles v ON b.vehicle_id = v.id
                  WHERE b.deleted_at IS NULL";
        $params = [];
        
        if (!empty($filters['status'])) {
            $query .= " AND b.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['user_id'])) {
            $query .= " AND b.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['vehicle_id'])) {
            $query .= " AND b.vehicle_id = ?";
            $params[] = $filters['vehicle_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $query .= " AND b.start_date >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $query .= " AND b.end_date <= ?";
            $params[] = $filters['end_date'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (b.booking_reference LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR v.brand LIKE ? OR v.model LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search, $search]);
        }
        
        $query .= " ORDER BY b.created_at DESC";
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Get booking by ID with user and vehicle details
     */
    public function getBookingById($id) {
        $query = "SELECT b.*, 
                         u.first_name, u.last_name, u.email, u.phone,
                         v.brand, v.model, v.plate_number, v.daily_rate
                  FROM bookings b
                  LEFT JOIN users u ON b.user_id = u.id
                  LEFT JOIN vehicles v ON b.vehicle_id = v.id
                  WHERE b.id = ? AND b.deleted_at IS NULL";
        $result = $this->db->query($query, [$id]);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Check vehicle availability for given dates
     */
    public function checkVehicleAvailability($vehicle_id, $start_date, $end_date, $exclude_booking_id = null) {
        $query = "SELECT COUNT(*) as count FROM bookings 
                  WHERE vehicle_id = ? 
                  AND status IN ('pending', 'confirmed') 
                  AND deleted_at IS NULL
                  AND (
                      (start_date <= ? AND end_date >= ?) OR
                      (start_date <= ? AND end_date >= ?) OR
                      (start_date >= ? AND end_date <= ?)
                  )";
        $params = [$vehicle_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date];
        
        if ($exclude_booking_id) {
            $query .= " AND id != ?";
            $params[] = $exclude_booking_id;
        }
        
        $result = $this->db->query($query, $params);
        return $result[0]['count'] == 0;
    }
    
    /**
     * Get available vehicles for given date range
     */
    public function getAvailableVehicles($start_date, $end_date) {
        $query = "SELECT v.* FROM vehicles v
                  WHERE v.status = 'available' 
                  AND v.deleted_at IS NULL
                  AND v.id NOT IN (
                      SELECT DISTINCT b.vehicle_id FROM bookings b
                      WHERE b.status IN ('pending', 'confirmed')
                      AND b.deleted_at IS NULL
                      AND (
                          (b.start_date <= ? AND b.end_date >= ?) OR
                          (b.start_date <= ? AND b.end_date >= ?) OR
                          (b.start_date >= ? AND b.end_date <= ?)
                      )
                  )
                  ORDER BY v.daily_rate ASC";
        
        return $this->db->query($query, [$start_date, $start_date, $end_date, $end_date, $start_date, $end_date]);
    }
    
    /**
     * Generate unique booking reference
     */
    public function generateBookingReference() {
        do {
            $reference = 'BK-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $result = $this->db->query("SELECT id FROM bookings WHERE booking_reference = ?", [$reference]);
        } while (!empty($result));
        
        return $reference;
    }
    
    /**
     * Calculate total amount based on daily rate and duration
     */
    public function calculateTotalAmount($vehicle_id, $start_date, $end_date) {
        // Get vehicle daily rate
        $result = $this->db->query("SELECT daily_rate FROM vehicles WHERE id = ?", [$vehicle_id]);
        if (empty($result)) {
            throw new Exception("Vehicle not found");
        }
        
        $daily_rate = $result[0]['daily_rate'];
        
        // Calculate duration in days
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $duration = $end->diff($start)->days + 1; // +1 to include both start and end dates
        
        return $daily_rate * $duration;
    }
    
    /**
     * Create new booking
     */
    public function createBooking($data) {
        // Validate required fields
        $required = ['user_id', 'vehicle_id', 'start_date', 'end_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Validate dates
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        
        if ($start_date >= $end_date) {
            throw new Exception("End date must be after start date");
        }
        
        if ($start_date < date('Y-m-d')) {
            throw new Exception("Start date cannot be in the past");
        }
        
        // Check vehicle availability
        if (!$this->checkVehicleAvailability($data['vehicle_id'], $start_date, $end_date)) {
            throw new Exception("Vehicle is not available for the selected dates");
        }
        
        // Generate booking reference
        $booking_reference = $this->generateBookingReference();
        
        // Calculate total amount
        $total_amount = $this->calculateTotalAmount($data['vehicle_id'], $start_date, $end_date);
        
        // Set defaults
        $status = $data['status'] ?? 'pending';
        $notes = $data['notes'] ?? null;
        
        $query = "INSERT INTO bookings (booking_reference, user_id, vehicle_id, start_date, end_date, total_amount, status, notes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $booking_reference,
            $data['user_id'],
            $data['vehicle_id'],
            $start_date,
            $end_date,
            $total_amount,
            $status,
            $notes
        ];
        
        $this->db->query($query, $params);
        
        // Get the created booking ID
        $booking_id = $this->db->lastInsertId();
        
        // Update vehicle status if booking is confirmed
        if ($status === 'confirmed') {
            $this->updateVehicleStatus($data['vehicle_id'], $start_date, $end_date);
        }
        
        return $booking_id;
    }
    
    /**
     * Update booking
     */
    public function updateBooking($id, $data) {
        // Check if booking exists
        $booking = $this->getBookingById($id);
        if (!$booking) {
            throw new Exception("Booking not found");
        }
        
        // Validate dates if provided
        if (isset($data['start_date']) && isset($data['end_date'])) {
            if ($data['start_date'] >= $data['end_date']) {
                throw new Exception("End date must be after start date");
            }
            
            if ($data['start_date'] < date('Y-m-d')) {
                throw new Exception("Start date cannot be in the past");
            }
            
            // Check vehicle availability (excluding current booking)
            $vehicle_id = $data['vehicle_id'] ?? $booking['vehicle_id'];
            if (!$this->checkVehicleAvailability($vehicle_id, $data['start_date'], $data['end_date'], $id)) {
                throw new Exception("Vehicle is not available for the selected dates");
            }
        }
        
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['user_id', 'vehicle_id', 'start_date', 'end_date', 'status', 'notes'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        // Recalculate total amount if dates or vehicle changed
        if (isset($data['start_date']) || isset($data['end_date']) || isset($data['vehicle_id'])) {
            $vehicle_id = $data['vehicle_id'] ?? $booking['vehicle_id'];
            $start_date = $data['start_date'] ?? $booking['start_date'];
            $end_date = $data['end_date'] ?? $booking['end_date'];
            
            $total_amount = $this->calculateTotalAmount($vehicle_id, $start_date, $end_date);
            $updateFields[] = "total_amount = ?";
            $params[] = $total_amount;
        }
        
        if (empty($updateFields)) {
            throw new Exception("No valid fields to update");
        }
        
        $params[] = $id;
        $query = "UPDATE bookings SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        $this->db->query($query, $params);
        
        // Update vehicle status if status changed
        if (isset($data['status'])) {
            $vehicle_id = $data['vehicle_id'] ?? $booking['vehicle_id'];
            $start_date = $data['start_date'] ?? $booking['start_date'];
            $end_date = $data['end_date'] ?? $booking['end_date'];
            $this->updateVehicleStatus($vehicle_id, $start_date, $end_date);
        }
        
        return true;
    }
    
    /**
     * Cancel booking (soft delete)
     */
    public function cancelBooking($id) {
        // Check if booking exists
        $booking = $this->getBookingById($id);
        if (!$booking) {
            throw new Exception("Booking not found");
        }
        
        // Update booking status to cancelled
        $query = "UPDATE bookings SET status = 'cancelled' WHERE id = ?";
        $this->db->query($query, [$id]);
        
        // Update vehicle status
        $this->updateVehicleStatus($booking['vehicle_id'], $booking['start_date'], $booking['end_date']);
        
        return true;
    }
    
    /**
     * Delete booking (soft delete)
     */
    public function deleteBooking($id) {
        // Check if booking exists
        $booking = $this->getBookingById($id);
        if (!$booking) {
            throw new Exception("Booking not found");
        }
        
        $query = "UPDATE bookings SET deleted_at = NOW() WHERE id = ?";
        $this->db->query($query, [$id]);
        
        // Update vehicle status
        $this->updateVehicleStatus($booking['vehicle_id'], $booking['start_date'], $booking['end_date']);
        
        return true;
    }
    
    /**
     * Update vehicle status based on active bookings
     */
    private function updateVehicleStatus($vehicle_id, $start_date, $end_date) {
        // Check if vehicle has any active bookings
        $query = "SELECT COUNT(*) as count FROM bookings 
                  WHERE vehicle_id = ? 
                  AND status = 'confirmed'
                  AND deleted_at IS NULL
                  AND start_date <= CURDATE() 
                  AND end_date >= CURDATE()";
        
        $result = $this->db->query($query, [$vehicle_id]);
        $active_bookings = $result[0]['count'];
        
        // Update vehicle status
        $new_status = $active_bookings > 0 ? 'rented' : 'available';
        $this->db->query("UPDATE vehicles SET status = ? WHERE id = ?", [$new_status, $vehicle_id]);
    }
    
    /**
     * Get booking statistics
     */
    public function getBookingStats() {
        $stats = [];
        
        // Total bookings
        $result = $this->db->query("SELECT COUNT(*) as count FROM bookings WHERE deleted_at IS NULL");
        $stats['total_bookings'] = $result[0]['count'];
        
        // Bookings by status
        $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
        foreach ($statuses as $status) {
            $result = $this->db->query("SELECT COUNT(*) as count FROM bookings WHERE status = ? AND deleted_at IS NULL", [$status]);
            $stats[$status . '_bookings'] = $result[0]['count'];
        }
        
        // Revenue
        $result = $this->db->query("SELECT SUM(total_amount) as revenue FROM bookings WHERE status IN ('confirmed', 'completed') AND deleted_at IS NULL");
        $stats['total_revenue'] = $result[0]['revenue'] ?? 0;
        
        // This month's bookings
        $result = $this->db->query("SELECT COUNT(*) as count FROM bookings WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND deleted_at IS NULL");
        $stats['monthly_bookings'] = $result[0]['count'];
        
        return $stats;
    }
}
?>