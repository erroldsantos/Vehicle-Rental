<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Maintenance extends Model {
    protected $table = 'maintenance';
    protected $primary_key = 'id';
    protected $soft_delete = true; // Enable soft deletes
    protected $fillable = ['vehicle_id', 'description', 'scheduled_date', 'cost', 'status'];

    /**
     * Get all maintenance records with vehicle details
     */
    public function getAllMaintenance($filters = []) {
        $query = "SELECT m.*, 
                         v.brand, 
                         v.model, 
                         v.year,
                         v.plate_number,
                         CONCAT(v.brand, ' ', v.model, ' (', v.year, ') - ', v.plate_number) as vehicle_display
                  FROM maintenance m
                  LEFT JOIN vehicles v ON m.vehicle_id = v.id
                  WHERE m.deleted_at IS NULL";
        $params = [];
        
        if (!empty($filters['status'])) {
            $query .= " AND m.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['vehicle_id'])) {
            $query .= " AND m.vehicle_id = ?";
            $params[] = $filters['vehicle_id'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (m.description LIKE ? OR v.brand LIKE ? OR v.model LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $query .= " ORDER BY m.scheduled_date DESC";
        
        $stmt = $this->db->raw($query, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get maintenance record by ID with vehicle details
     */
    public function getMaintenanceById($id) {
        $query = "SELECT m.*, 
                         v.brand, 
                         v.model, 
                         v.year,
                         v.plate_number,
                         CONCAT(v.brand, ' ', v.model, ' (', v.year, ') - ', v.plate_number) as vehicle_display
                  FROM maintenance m
                  LEFT JOIN vehicles v ON m.vehicle_id = v.id
                  WHERE m.id = ? AND m.deleted_at IS NULL";
        $stmt = $this->db->raw($query, [$id]);
        $result = $stmt->fetchAll();
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Create new maintenance record using ORM
     */
    public function createMaintenance($data) {
        // Validate required fields
        $required = ['vehicle_id', 'description', 'scheduled_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Validate cost if provided
        if (isset($data['cost']) && (!is_numeric($data['cost']) || $data['cost'] < 0)) {
            throw new Exception("Cost must be a non-negative number");
        }
        
        // Set defaults
        $data['status'] = $data['status'] ?? 'scheduled';
        $data['cost'] = $data['cost'] ?? 0;
        
        // Use ORM insert
        $this->insert($data);
        
        // Get the last inserted ID
        $stmt = $this->db->raw("SELECT LAST_INSERT_ID() as id");
        $result = $stmt->fetch();
        $id = $result['id'];
        
        // Update vehicle status if scheduled
        if ($data['status'] === 'scheduled') {
            $this->db->raw("UPDATE vehicles SET status = 'maintenance' WHERE id = ?", [$data['vehicle_id']]);
        }
        
        // Return the created record with vehicle details
        return $this->getMaintenanceById($id);
    }
    
    /**
     * Update maintenance record using ORM
     */
    public function updateMaintenance($id, $data) {
        // Check if maintenance exists
        $maintenance = $this->getMaintenanceById($id);
        if (!$maintenance) {
            throw new Exception("Maintenance record not found");
        }
        
        // Validate cost if provided
        if (isset($data['cost']) && (!is_numeric($data['cost']) || $data['cost'] < 0)) {
            throw new Exception("Cost must be a non-negative number");
        }
        
        // Use ORM update
        $this->update($id, $data);
        
        // Update vehicle status based on status change
        if (isset($data['status'])) {
            if ($data['status'] === 'completed') {
                $this->db->raw("UPDATE vehicles SET status = 'available' WHERE id = ?", [$maintenance['vehicle_id']]);
            } elseif ($data['status'] === 'scheduled') {
                $this->db->raw("UPDATE vehicles SET status = 'maintenance' WHERE id = ?", [$maintenance['vehicle_id']]);
            }
        }
        
        // Return the updated record with vehicle details
        return $this->getMaintenanceById($id);
    }
    
    /**
     * Complete maintenance record
     */
    public function completeMaintenance($id, $cost = null) {
        // Check if maintenance exists
        $maintenance = $this->getMaintenanceById($id);
        if (!$maintenance) {
            throw new Exception("Maintenance record not found");
        }
        
        $updateData = ['status' => 'completed'];
        
        if ($cost !== null) {
            if (!is_numeric($cost) || $cost < 0) {
                throw new Exception("Cost must be a non-negative number");
            }
            $updateData['cost'] = $cost;
        }
        
        // Use ORM update
        $this->update($id, $updateData);
        
        // Update vehicle status back to available
        $this->db->raw("UPDATE vehicles SET status = 'available' WHERE id = ?", [$maintenance['vehicle_id']]);
        
        // Return the updated record with vehicle details
        return $this->getMaintenanceById($id);
    }
    
    /**
     * Delete maintenance record using ORM soft delete
     */
    public function deleteMaintenance($id) {
        // Check if maintenance exists
        $maintenance = $this->getMaintenanceById($id);
        if (!$maintenance) {
            throw new Exception("Maintenance record not found");
        }
        
        // Use ORM soft delete
        return $this->soft_delete($id);
    }
    
    /**
     * Get maintenance statistics
     */
    public function getMaintenanceStats() {
        $stats = [];
        
        // Total maintenance records
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM maintenance WHERE deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['total_maintenance'] = $result['count'];
        
        // Scheduled maintenance
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM maintenance WHERE status = 'scheduled' AND deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['scheduled_maintenance'] = $result['count'];
        
        // In progress maintenance
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM maintenance WHERE status = 'in_progress' AND deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['inprogress_maintenance'] = $result['count'];
        
        // Completed maintenance
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM maintenance WHERE status = 'completed' AND deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['completed_maintenance'] = $result['count'];
        
        // Total cost
        $stmt = $this->db->raw("SELECT SUM(cost) as total_cost FROM maintenance WHERE deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['total_cost'] = $result['total_cost'] ?? 0;
        
        return $stats;
    }
}

?>