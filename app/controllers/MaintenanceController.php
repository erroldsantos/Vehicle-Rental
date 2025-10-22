<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class MaintenanceController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->call->library('api');
    }

    // GET /maintenance - List all maintenance records with vehicle info
    public function index() {
        $this->api->require_method('GET');

        try {
            $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Join with vehicles table to get vehicle info
            $sql = "SELECT 
                        m.*, 
                        v.brand, 
                        v.model, 
                        v.year, 
                        v.plate_number,
                        CONCAT(v.brand, ' ', v.model, ' (', v.year, ') - ', v.plate_number) as vehicle_display
                    FROM maintenance m 
                    LEFT JOIN vehicles v ON m.vehicle_id = v.id 
                    WHERE m.deleted_at IS NULL 
                    ORDER BY m.scheduled_date ASC";
            
            $stmt = $pdo->query($sql);
            $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->api->respond([
                'data' => $maintenance,
                'total' => count($maintenance)
            ]);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }

    // GET /maintenance/{id}
    public function show($id) {
        $this->api->require_method('GET');
        
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = "SELECT 
                        m.*, 
                        v.brand, 
                        v.model, 
                        v.year, 
                        v.plate_number,
                        CONCAT(v.brand, ' ', v.model, ' (', v.year, ') - ', v.plate_number) as vehicle_display
                    FROM maintenance m 
                    LEFT JOIN vehicles v ON m.vehicle_id = v.id 
                    WHERE m.id = ? AND m.deleted_at IS NULL";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([(int)$id]);
            $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$maintenance) {
                $this->api->respond_error('Maintenance record not found', 404);
                return;
            }
            
            $this->api->respond($maintenance);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }

    // GET /maintenance/vehicles - Get list of vehicles for dropdown
    public function vehicles() {
        $this->api->require_method('GET');
        
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = "SELECT 
                        id, 
                        brand, 
                        model, 
                        year, 
                        plate_number,
                        status,
                        CONCAT(brand, ' ', model, ' (', year, ') - ', plate_number, ' [', UPPER(status), ']') as display_name
                    FROM vehicles 
                    WHERE deleted_at IS NULL AND status != 'rented'
                    ORDER BY brand, model";
            
            $stmt = $pdo->query($sql);
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->api->respond($vehicles);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }

    // POST /maintenance
    public function create() {
        $this->api->require_method('POST');

        $input = $this->api->body();

        // Basic validation
        $required = ['vehicle_id', 'description', 'scheduled_date'];
        foreach ($required as $r) {
            if (empty($input[$r])) {
                $this->api->respond_error($r . ' is required', 400);
                return;
            }
        }

        try {
            $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Verify vehicle exists
            $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([(int)$input['vehicle_id']]);
            if (!$stmt->fetch()) {
                $this->api->respond_error('Vehicle not found', 404);
                return;
            }
            
            $sql = "INSERT INTO maintenance (vehicle_id, description, scheduled_date, cost, status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                (int)$input['vehicle_id'],
                $input['description'],
                $input['scheduled_date'],
                isset($input['cost']) ? (float)$input['cost'] : 0.00,
                $input['status'] ?? 'scheduled'
            ]);
            
            $id = $pdo->lastInsertId();
            
            // Update vehicle status to maintenance if maintenance is scheduled
            if (($input['status'] ?? 'scheduled') === 'scheduled') {
                $updateVehicle = $pdo->prepare("UPDATE vehicles SET status = 'maintenance' WHERE id = ?");
                $updateVehicle->execute([(int)$input['vehicle_id']]);
            }
            
            // Fetch the created record with vehicle info
            $sql = "SELECT 
                        m.*, 
                        v.brand, 
                        v.model, 
                        v.year, 
                        v.plate_number,
                        CONCAT(v.brand, ' ', v.model, ' (', v.year, ') - ', v.plate_number) as vehicle_display
                    FROM maintenance m 
                    LEFT JOIN vehicles v ON m.vehicle_id = v.id 
                    WHERE m.id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $created = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->api->respond($created, 201);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }

    // PUT /maintenance/{id}
    public function update($id) {
        $this->api->require_method('PUT');

        $input = $this->api->body();

        try {
            $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if maintenance record exists
            $stmt = $pdo->prepare("SELECT * FROM maintenance WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([(int)$id]);
            $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$maintenance) {
                $this->api->respond_error('Maintenance record not found', 404);
                return;
            }

            // Build update query dynamically
            $updates = [];
            $values = [];
            $fields = ['vehicle_id', 'description', 'scheduled_date', 'cost', 'status'];
            
            foreach ($fields as $field) {
                if (isset($input[$field])) {
                    $updates[] = "$field = ?";
                    $values[] = $input[$field];
                }
            }
            
            if (empty($updates)) {
                $this->api->respond_error('No fields to update', 400);
                return;
            }
            
            $values[] = (int)$id; // for WHERE clause
            
            $sql = "UPDATE maintenance SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            
            // Update vehicle status based on maintenance status change
            if (isset($input['status'])) {
                if ($input['status'] === 'completed') {
                    // Set vehicle status to available when maintenance is completed
                    $updateVehicle = $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?");
                    $updateVehicle->execute([(int)$maintenance['vehicle_id']]);
                } elseif ($input['status'] === 'scheduled') {
                    // Set vehicle status to maintenance when maintenance is scheduled
                    $updateVehicle = $pdo->prepare("UPDATE vehicles SET status = 'maintenance' WHERE id = ?");
                    $updateVehicle->execute([(int)$maintenance['vehicle_id']]);
                }
            }
            
            // Fetch updated record with vehicle info
            $sql = "SELECT 
                        m.*, 
                        v.brand, 
                        v.model, 
                        v.year, 
                        v.plate_number,
                        CONCAT(v.brand, ' ', v.model, ' (', v.year, ') - ', v.plate_number) as vehicle_display
                    FROM maintenance m 
                    LEFT JOIN vehicles v ON m.vehicle_id = v.id 
                    WHERE m.id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([(int)$id]);
            $updated = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->api->respond($updated);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }

    // DELETE /maintenance/{id}
    public function delete($id) {
        $this->api->require_method('DELETE');

        try {
            $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if maintenance record exists and get vehicle_id
            $stmt = $pdo->prepare("SELECT vehicle_id, status FROM maintenance WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([(int)$id]);
            $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$maintenance) {
                $this->api->respond_error('Maintenance record not found', 404);
                return;
            }
            
            // Soft delete
            $stmt = $pdo->prepare("UPDATE maintenance SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([(int)$id]);
            
            // If the deleted maintenance was scheduled, set vehicle back to available
            if ($maintenance['status'] === 'scheduled') {
                $updateVehicle = $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?");
                $updateVehicle->execute([(int)$maintenance['vehicle_id']]);
            }
            
            $this->api->respond(['message' => 'Maintenance record deleted']);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }

    // PUT /maintenance/{id}/complete - Mark maintenance as completed
    public function complete($id) {
        $this->api->require_method('PUT');

        $input = $this->api->body();

        try {
            $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if maintenance record exists
            $stmt = $pdo->prepare("SELECT * FROM maintenance WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([(int)$id]);
            $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$maintenance) {
                $this->api->respond_error('Maintenance record not found', 404);
                return;
            }

            // Update status to completed and optionally update cost
            $cost = isset($input['cost']) ? (float)$input['cost'] : $maintenance['cost'];
            
            $stmt = $pdo->prepare("UPDATE maintenance SET status = 'completed', cost = ? WHERE id = ?");
            $stmt->execute([$cost, (int)$id]);
            
            // Update vehicle status back to available when maintenance is completed
            $updateVehicle = $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?");
            $updateVehicle->execute([(int)$maintenance['vehicle_id']]);
            
            // Fetch updated record
            $sql = "SELECT 
                        m.*, 
                        v.brand, 
                        v.model, 
                        v.year, 
                        v.plate_number,
                        CONCAT(v.brand, ' ', v.model, ' (', v.year, ') - ', v.plate_number) as vehicle_display
                    FROM maintenance m 
                    LEFT JOIN vehicles v ON m.vehicle_id = v.id 
                    WHERE m.id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([(int)$id]);
            $updated = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->api->respond($updated);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }

    // POST /maintenance/sync - Sync vehicle statuses with existing maintenance records
    public function sync() {
        $this->api->require_method('POST');

        try {
            $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Get all scheduled maintenance records
            $stmt = $pdo->query("SELECT DISTINCT vehicle_id FROM maintenance WHERE status = 'scheduled' AND deleted_at IS NULL");
            $scheduledVehicles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $updatedCount = 0;
            
            // Update vehicle statuses to maintenance for all vehicles with scheduled maintenance
            if (!empty($scheduledVehicles)) {
                $placeholders = str_repeat('?,', count($scheduledVehicles) - 1) . '?';
                $updateStmt = $pdo->prepare("UPDATE vehicles SET status = 'maintenance' WHERE id IN ($placeholders) AND status != 'rented'");
                $updateStmt->execute($scheduledVehicles);
                $updatedCount = $updateStmt->rowCount();
            }
            
            // Also ensure vehicles without scheduled maintenance are available (if not rented)
            $stmt = $pdo->query("
                UPDATE vehicles 
                SET status = 'available' 
                WHERE status = 'maintenance' 
                AND id NOT IN (
                    SELECT vehicle_id FROM maintenance 
                    WHERE status = 'scheduled' AND deleted_at IS NULL
                )
                AND status != 'rented'
            ");
            $resetCount = $stmt->rowCount();
            
            $this->api->respond([
                'message' => 'Vehicle statuses synchronized with maintenance records',
                'vehicles_set_to_maintenance' => $updatedCount,
                'vehicles_reset_to_available' => $resetCount,
                'scheduled_vehicles' => $scheduledVehicles
            ]);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }
}

?>