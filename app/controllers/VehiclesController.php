<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once APP_DIR . 'controllers/ApiController.php';

class VehiclesController extends ApiController {

    // GET /vehicles
    public function index() {
        $this->api->require_method('GET');

        try {
            $vehicles = $this->db->query(
                "SELECT * FROM vehicles WHERE deleted_at IS NULL"
            );
            
            $this->success([
                'vehicles' => $vehicles,
                'total' => count($vehicles)
            ]);
            
        } catch (Exception $e) {
            $this->handleDbError($e, 'Failed to fetch vehicles');
        }
    }

    // GET /vehicles/{id}
    public function show($id) {
        $this->api->require_method('GET');
        
        try {
            $vehicle = $this->db->queryOne(
                "SELECT * FROM vehicles WHERE id = ? AND deleted_at IS NULL",
                [(int)$id]
            );
            
            if (!$vehicle) {
                return $this->error('Vehicle not found', 404);
            }
            
            $this->success($vehicle);
            
        } catch (Exception $e) {
            $this->handleDbError($e, 'Failed to fetch vehicle');
        }
    }

    // POST /vehicles
    public function create() {
        $this->api->require_method('POST');
        $input = $this->api->body();

        // Validate required fields
        if (!$this->validateRequired($input, ['brand','model','year','plate_number','daily_rate'])) {
            return;
        }

        try {
            // Check for duplicate plate
            $existing = $this->db->queryOne(
                "SELECT id FROM vehicles WHERE plate_number = ? AND deleted_at IS NULL",
                [$input['plate_number']]
            );
            
            if ($existing) {
                return $this->error('Vehicle with this plate number already exists', 409);
            }
            
            // Insert vehicle
            $this->db->execute(
                "INSERT INTO vehicles (brand, model, year, plate_number, daily_rate, status) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $input['brand'],
                    $input['model'],
                    (int)$input['year'],
                    $input['plate_number'],
                    $input['daily_rate'],
                    $input['status'] ?? 'available'
                ]
            );
            
            $id = $this->db->lastInsertId();
            
            // Fetch the created record
            $created = $this->db->queryOne("SELECT * FROM vehicles WHERE id = ?", [$id]);
            
            $this->api->respond($created, 201);
            
        } catch (Exception $e) {
            $this->handleDbError($e, 'Failed to create vehicle');
        }
    }

    // PUT /vehicles/{id}
    public function update($id) {
        $this->api->require_method('PUT');
        $input = $this->api->body();

        try {
            // Check if vehicle exists
            $vehicle = $this->db->queryOne(
                "SELECT * FROM vehicles WHERE id = ? AND deleted_at IS NULL",
                [(int)$id]
            );
            
            if (!$vehicle) {
                return $this->error('Vehicle not found', 404);
            }

            // Build update query dynamically
            $updates = [];
            $values = [];
            $fields = ['brand','model','year','plate_number','daily_rate','status'];
            
            foreach ($fields as $field) {
                if (isset($input[$field])) {
                    $updates[] = "$field = ?";
                    $values[] = $input[$field];
                }
            }
            
            if (empty($updates)) {
                return $this->error('No fields to update', 400);
            }
            
            $values[] = (int)$id; // for WHERE clause
            
            $this->db->execute(
                "UPDATE vehicles SET " . implode(', ', $updates) . " WHERE id = ?",
                $values
            );
            
            // Fetch updated record
            $updated = $this->db->queryOne("SELECT * FROM vehicles WHERE id = ?", [(int)$id]);
            
            $this->success($updated, 'Vehicle updated successfully');
            
        } catch (Exception $e) {
            $this->handleDbError($e, 'Failed to update vehicle');
        }
    }

    // DELETE /vehicles/{id}
    public function delete($id) {
        $this->api->require_method('DELETE');

        try {
            // Check if vehicle exists
            $vehicle = $this->db->queryOne(
                "SELECT id FROM vehicles WHERE id = ? AND deleted_at IS NULL",
                [(int)$id]
            );
            
            if (!$vehicle) {
                return $this->error('Vehicle not found', 404);
            }
            
            // Soft delete
            $this->db->execute(
                "UPDATE vehicles SET deleted_at = NOW() WHERE id = ?",
                [(int)$id]
            );
            
            $this->api->respond(['message' => 'Vehicle deleted successfully']);
            
        } catch (Exception $e) {
            $this->handleDbError($e, 'Failed to delete vehicle');
        }
    }
}

?>
