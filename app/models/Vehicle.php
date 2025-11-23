<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Vehicle extends Model {
    protected $table = 'vehicles';
    protected $primary_key = 'id';
    protected $soft_delete = true; // Enable soft deletes
    protected $fillable = ['brand', 'model', 'year', 'plate_number', 'daily_rate', 'status'];

    
    /**
     * Get vehicle by ID
     */
    public function getVehicleById($id) {
        return $this->find($id);
    }
    
    /**
     * Create new vehicle
     */
    public function createVehicle($data) {
        // Validate required fields
        $required = ['brand', 'model', 'year', 'plate_number', 'daily_rate'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Validate year
        if (!is_numeric($data['year']) || $data['year'] < 1900 || $data['year'] > date('Y') + 1) {
            throw new Exception("Invalid year");
        }
        
        // Validate daily rate
        if (!is_numeric($data['daily_rate']) || $data['daily_rate'] <= 0) {
            throw new Exception("Daily rate must be a positive number");
        }
        
        // Check if plate number already exists
        $stmt = $this->db->raw("SELECT id FROM vehicles WHERE plate_number = ? AND deleted_at IS NULL", [$data['plate_number']]);
        if ($stmt->fetch()) {
            throw new Exception("Plate number already exists");
        }
        
        // Set default status
        $data['status'] = $data['status'] ?? 'available';
        
        // insert
        return $this->insert($data);
    }
    
    /**
     * Update vehicle
     */
    public function updateVehicle($id, $data) {
        // Check if vehicle exists
        $vehicle = $this->getVehicleById($id);
        if (!$vehicle) {
            throw new Exception("Vehicle not found");
        }
        
        // Validate plate number if provided
        if (!empty($data['plate_number'])) {
            $stmt = $this->db->raw("SELECT id FROM vehicles WHERE plate_number = ? AND id != ? AND deleted_at IS NULL", [$data['plate_number'], $id]);
            if ($stmt->fetch()) {
                throw new Exception("Plate number already exists");
            }
        }
        
        // Validate year if provided
        if (isset($data['year'])) {
            if (!is_numeric($data['year']) || $data['year'] < 1900 || $data['year'] > date('Y') + 1) {
                throw new Exception("Invalid year");
            }
        }
        
        // Validate daily rate if provided
        if (isset($data['daily_rate'])) {
            if (!is_numeric($data['daily_rate']) || $data['daily_rate'] <= 0) {
                throw new Exception("Daily rate must be a positive number");
            }
        }
        
        // Update
        return $this->update($id, $data);
    }
    
    /**
     * Delete vehicle using soft delete
     */
    public function deleteVehicle($id) {
        // Check if vehicle exists
        $vehicle = $this->getVehicleById($id);
        if (!$vehicle) {
            throw new Exception("Vehicle not found");
        }
        
        // Check if vehicle has active bookings
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM bookings WHERE vehicle_id = ? AND status IN ('pending', 'confirmed') AND deleted_at IS NULL", [$id]);
        $result = $stmt->fetch();
        if ($result['count'] > 0) {
            throw new Exception("Cannot delete vehicle with active bookings");
        }
        
        // soft delete
        return $this->soft_delete($id);
    }

    /**
     * Get all vehicles with optional filtering
     */
    public function getAllVehicles($filters = []) {
        $query = "SELECT * FROM vehicles WHERE deleted_at IS NULL";
        $params = [];
        
        // Apply status filter
        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        // Apply search filter
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (brand LIKE ? OR model LIKE ? OR plate_number LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $query .= " ORDER BY brand, model";
        
        $stmt = $this->db->raw($query, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get vehicle statistics
     */
    public function getVehicleStats() {
        $stats = [];
        
        // Total vehicles
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM vehicles WHERE deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['total_vehicles'] = $result['count'];
        
        // Available vehicles
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM vehicles WHERE status = 'available' AND deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['available_vehicles'] = $result['count'];
        
        // Rented vehicles
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM vehicles WHERE status = 'rented' AND deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['rented_vehicles'] = $result['count'];
        
        // Under maintenance
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM vehicles WHERE status = 'maintenance' AND deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['maintenance_vehicles'] = $result['count'];
        
        return $stats;
    }
}

?>
