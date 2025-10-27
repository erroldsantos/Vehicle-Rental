<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/ApiController.php';

class BookingsController extends ApiController {
    
    /**
     * Get all bookings with filtering
     * GET /api/bookings
     */
    public function index() {
        $this->api->require_method('GET');
        
        try {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'user_id' => $_GET['user_id'] ?? null,
                'vehicle_id' => $_GET['vehicle_id'] ?? null,
                'start_date' => $_GET['start_date'] ?? null,
                'end_date' => $_GET['end_date'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            $query = "SELECT b.id, b.booking_reference, b.start_date, b.end_date, 
                             b.total_amount, b.status, b.notes, b.created_at, b.user_id, b.vehicle_id,
                             u.first_name, u.last_name, u.email,
                             v.brand, v.model, v.plate_number, v.daily_rate, v.image as vehicle_image
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
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $bookings = $stmt->fetchAll();
            
            // Get statistics
            $stats = $this->getBookingStats();
            
            $this->api->respond([
                'bookings' => $bookings,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Get single booking
     * GET /api/bookings/{id}
     */
    public function show($id) {
        $this->api->require_method('GET');
        
        try {
            if (empty($id)) {
                $this->api->respond_error('Booking ID is required', 400);
                return;
            }
            
            $stmt = $this->pdo->prepare("SELECT b.*, 
                                                u.first_name, u.last_name, u.email, u.phone,
                                                v.brand, v.model, v.plate_number, v.daily_rate
                                         FROM bookings b
                                         LEFT JOIN users u ON b.user_id = u.id
                                         LEFT JOIN vehicles v ON b.vehicle_id = v.id
                                         WHERE b.id = ? AND b.deleted_at IS NULL");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                $this->api->respond_error('Booking not found', 404);
                return;
            }
            
            $this->api->respond($booking);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Create new booking
     * POST /api/bookings
     */
    public function create() {
        $this->api->require_method('POST');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->api->respond_error('Invalid JSON data', 400);
                return;
            }
            
            // Validate required fields
            $required = ['user_id', 'vehicle_id', 'start_date', 'end_date'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    $this->api->respond_error("Field '$field' is required", 400);
                    return;
                }
            }
            
            // Validate dates
            $start_date = $input['start_date'];
            $end_date = $input['end_date'];
            
            if ($start_date >= $end_date) {
                $this->api->respond_error('End date must be after start date', 400);
                return;
            }
            
            if ($start_date < date('Y-m-d')) {
                $this->api->respond_error('Start date cannot be in the past', 400);
                return;
            }
            
            // Check if user exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$input['user_id']]);
            if (!$stmt->fetch()) {
                $this->api->respond_error('User not found', 400);
                return;
            }
            
            // Check if vehicle exists and is available
            $stmt = $this->pdo->prepare("SELECT id, daily_rate FROM vehicles WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$input['vehicle_id']]);
            $vehicle = $stmt->fetch();
            if (!$vehicle) {
                $this->api->respond_error('Vehicle not found', 400);
                return;
            }
            
            // Check vehicle availability
            if (!$this->checkVehicleAvailability($input['vehicle_id'], $start_date, $end_date)) {
                $this->api->respond_error('Vehicle is not available for the selected dates', 400);
                return;
            }
            
            // Generate booking reference
            $booking_reference = $this->generateBookingReference();
            
            // Calculate total amount
            $total_amount = $this->calculateTotalAmount($input['vehicle_id'], $start_date, $end_date);
            
            // Set defaults
            $status = $input['status'] ?? 'pending';
            $notes = $input['notes'] ?? null;
            
            // Validate status
            if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
                $this->api->respond_error('Invalid status', 400);
                return;
            }
            
            // Insert booking
            $stmt = $this->pdo->prepare("INSERT INTO bookings (booking_reference, user_id, vehicle_id, start_date, end_date, total_amount, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $booking_reference,
                $input['user_id'],
                $input['vehicle_id'],
                $start_date,
                $end_date,
                $total_amount,
                $status,
                $notes
            ]);
            
            $booking_id = $this->pdo->lastInsertId();
            
            // Update vehicle status if booking is confirmed
            if ($status === 'confirmed') {
                $this->updateVehicleStatus($input['vehicle_id']);
            }
            
            // Fetch and return the created booking
            $stmt = $this->pdo->prepare("SELECT b.*, 
                                                u.first_name, u.last_name, u.email,
                                                v.brand, v.model, v.plate_number
                                         FROM bookings b
                                         LEFT JOIN users u ON b.user_id = u.id
                                         LEFT JOIN vehicles v ON b.vehicle_id = v.id
                                         WHERE b.id = ?");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();
            
            $this->api->respond($booking, 201);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Update booking
     * PUT /api/bookings/{id}
     */
    public function update($id) {
        $this->api->require_method('PUT');
        
        try {
            if (empty($id)) {
                $this->api->respond_error('Booking ID is required', 400);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->api->respond_error('Invalid JSON data', 400);
                return;
            }
            
            // Check if booking exists
            $stmt = $this->pdo->prepare("SELECT * FROM bookings WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();
            if (!$booking) {
                $this->api->respond_error('Booking not found', 404);
                return;
            }
            
            // Validate dates if provided
            if (isset($input['start_date']) && isset($input['end_date'])) {
                if ($input['start_date'] >= $input['end_date']) {
                    $this->api->respond_error('End date must be after start date', 400);
                    return;
                }
                
                if ($input['start_date'] < date('Y-m-d')) {
                    $this->api->respond_error('Start date cannot be in the past', 400);
                    return;
                }
                
                // Check vehicle availability (excluding current booking)
                $vehicle_id = $input['vehicle_id'] ?? $booking['vehicle_id'];
                if (!$this->checkVehicleAvailability($vehicle_id, $input['start_date'], $input['end_date'], $id)) {
                    $this->api->respond_error('Vehicle is not available for the selected dates', 400);
                    return;
                }
            }
            
            // Validate status if provided
            if (isset($input['status']) && !in_array($input['status'], ['pending', 'confirmed', 'completed', 'cancelled'])) {
                $this->api->respond_error('Invalid status', 400);
                return;
            }
            
            $updateFields = [];
            $params = [];
            
            $allowedFields = ['user_id', 'vehicle_id', 'start_date', 'end_date', 'status', 'notes'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            // Recalculate total amount if dates or vehicle changed
            if (isset($input['start_date']) || isset($input['end_date']) || isset($input['vehicle_id'])) {
                $vehicle_id = $input['vehicle_id'] ?? $booking['vehicle_id'];
                $start_date = $input['start_date'] ?? $booking['start_date'];
                $end_date = $input['end_date'] ?? $booking['end_date'];
                
                $total_amount = $this->calculateTotalAmount($vehicle_id, $start_date, $end_date);
                $updateFields[] = "total_amount = ?";
                $params[] = $total_amount;
            }
            
            if (empty($updateFields)) {
                $this->api->respond_error('No valid fields to update', 400);
                return;
            }
            
            $params[] = $id;
            $query = "UPDATE bookings SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            // Update vehicle status
            $vehicle_id = $input['vehicle_id'] ?? $booking['vehicle_id'];
            $this->updateVehicleStatus($vehicle_id);
            
            // Fetch and return the updated booking
            $stmt = $this->pdo->prepare("SELECT b.*, 
                                                u.first_name, u.last_name, u.email,
                                                v.brand, v.model, v.plate_number
                                         FROM bookings b
                                         LEFT JOIN users u ON b.user_id = u.id
                                         LEFT JOIN vehicles v ON b.vehicle_id = v.id
                                         WHERE b.id = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();
            
            $this->api->respond($booking);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Cancel booking
     * PUT /api/bookings/{id}/cancel
     */
    public function cancel($id) {
        $this->api->require_method('PUT');
        
        try {
            if (empty($id)) {
                $this->api->respond_error('Booking ID is required', 400);
                return;
            }
            
            // Check if booking exists
            $stmt = $this->pdo->prepare("SELECT * FROM bookings WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();
            if (!$booking) {
                $this->api->respond_error('Booking not found', 404);
                return;
            }
            
            // Update booking status to cancelled
            $stmt = $this->pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$id]);
            
            // Update vehicle status
            $this->updateVehicleStatus($booking['vehicle_id']);
            
            $this->api->respond(['message' => 'Booking cancelled successfully']);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Delete booking (soft delete)
     * DELETE /api/bookings/{id}
     */
    public function delete($id) {
        $this->api->require_method('DELETE');
        
        try {
            if (empty($id)) {
                $this->api->respond_error('Booking ID is required', 400);
                return;
            }
            
            // Check if booking exists
            $stmt = $this->pdo->prepare("SELECT * FROM bookings WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();
            if (!$booking) {
                $this->api->respond_error('Booking not found', 404);
                return;
            }
            
            // Soft delete
            $stmt = $this->pdo->prepare("UPDATE bookings SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            
            // Update vehicle status
            $this->updateVehicleStatus($booking['vehicle_id']);
            
            $this->api->respond(['message' => 'Booking deleted successfully']);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Get available vehicles for date range
     * GET /api/bookings/available-vehicles?start_date=X&end_date=Y
     */
    public function availableVehicles() {
        $this->api->require_method('GET');
        
        try {
            $start_date = $_GET['start_date'] ?? null;
            $end_date = $_GET['end_date'] ?? null;
            
            if (!$start_date || !$end_date) {
                $this->api->respond_error('start_date and end_date are required', 400);
                return;
            }
            
            if ($start_date >= $end_date) {
                $this->api->respond_error('End date must be after start date', 400);
                return;
            }
            
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
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$start_date, $start_date, $end_date, $end_date, $start_date, $end_date]);
            $vehicles = $stmt->fetchAll();
            
            $this->api->respond(['vehicles' => $vehicles]);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Get all users for booking form
     * GET /api/bookings/users
     */
    public function users() {
        $this->api->require_method('GET');
        
        try {
            $stmt = $this->pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE deleted_at IS NULL ORDER BY first_name, last_name");
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            $this->api->respond(['users' => $users]);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Check vehicle availability
     */
    private function checkVehicleAvailability($vehicle_id, $start_date, $end_date, $exclude_booking_id = null) {
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
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['count'] == 0;
    }
    
    /**
     * Generate unique booking reference
     */
    private function generateBookingReference() {
        do {
            $reference = 'BK-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $stmt = $this->pdo->prepare("SELECT id FROM bookings WHERE booking_reference = ?");
            $stmt->execute([$reference]);
            $exists = $stmt->fetch();
        } while ($exists);
        
        return $reference;
    }
    
    /**
     * Calculate total amount based on daily rate and duration
     */
    private function calculateTotalAmount($vehicle_id, $start_date, $end_date) {
        // Get vehicle daily rate
        $stmt = $this->pdo->prepare("SELECT daily_rate FROM vehicles WHERE id = ?");
        $stmt->execute([$vehicle_id]);
        $vehicle = $stmt->fetch();
        
        if (!$vehicle) {
            throw new Exception("Vehicle not found");
        }
        
        $daily_rate = $vehicle['daily_rate'];
        
        // Calculate duration in days
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $duration = $end->diff($start)->days + 1; // +1 to include both start and end dates
        
        return $daily_rate * $duration;
    }
    
    /**
     * Update vehicle status based on active bookings
     */
    private function updateVehicleStatus($vehicle_id) {
        // Check if vehicle has any active confirmed bookings
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM bookings 
                                     WHERE vehicle_id = ? 
                                     AND status = 'confirmed'
                                     AND deleted_at IS NULL
                                     AND start_date <= CURDATE() 
                                     AND end_date >= CURDATE()");
        $stmt->execute([$vehicle_id]);
        $result = $stmt->fetch();
        $active_bookings = $result['count'];
        
        // Update vehicle status
        $new_status = $active_bookings > 0 ? 'rented' : 'available';
        $stmt = $this->pdo->prepare("UPDATE vehicles SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $vehicle_id]);
    }
    
    /**
     * Get booking statistics
     */
    private function getBookingStats() {
        $stats = [];
        
        // Total bookings
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM bookings WHERE deleted_at IS NULL");
        $stats['total_bookings'] = $stmt->fetchColumn();
        
        // Bookings by status
        $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
        foreach ($statuses as $status) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM bookings WHERE status = ? AND deleted_at IS NULL");
            $stmt->execute([$status]);
            $stats[$status . '_bookings'] = $stmt->fetchColumn();
        }
        
        // Revenue
        $stmt = $this->pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM bookings WHERE status IN ('confirmed', 'completed') AND deleted_at IS NULL");
        $stats['total_revenue'] = $stmt->fetchColumn();
        
        // This month's bookings
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM bookings WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND deleted_at IS NULL");
        $stats['monthly_bookings'] = $stmt->fetchColumn();
        
        return $stats;
    }
}
?>