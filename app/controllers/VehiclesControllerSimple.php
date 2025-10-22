<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class VehiclesController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->call->library('api');
    }

    // GET /vehicles - Simple version without model
    public function index() {
        $this->api->require_method('GET');

        try {
            // Direct PDO connection for testing
            $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->query("SELECT * FROM vehicles WHERE deleted_at IS NULL");
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->api->respond([
                'data' => $vehicles,
                'total' => count($vehicles)
            ]);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }

    // GET /vehicles/{id}
    public function show($id) {
        $this->api->require_method('GET');
        
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([(int)$id]);
            $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$vehicle) {
                $this->api->respond_error('Vehicle not found', 404);
                return;
            }
            
            $this->api->respond($vehicle);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }

    // POST /vehicles
    public function create() {
        $this->api->require_method('POST');

        $input = $this->api->body();

        // Basic validation
        $required = ['brand','model','year','plate_number','daily_rate'];
        foreach ($required as $r) {
            if (empty($input[$r])) {
                $this->api->respond_error($r . ' is required', 400);
                return;
            }
        }

        try {
            $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check for duplicate plate
            $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE plate_number = ? AND deleted_at IS NULL");
            $stmt->execute([$input['plate_number']]);
            if ($stmt->fetch()) {
                $this->api->respond_error('Vehicle with this plate number already exists', 409);
                return;
            }
            
            $sql = "INSERT INTO vehicles (brand, model, year, plate_number, daily_rate, status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $input['brand'],
                $input['model'],
                (int)$input['year'],
                $input['plate_number'],
                $input['daily_rate'],
                $input['status'] ?? 'available'
            ]);
            
            $id = $pdo->lastInsertId();
            
            // Fetch the created record
            $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
            $stmt->execute([$id]);
            $created = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->api->respond($created, 201);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }

    // PUT /vehicles/{id}
    public function update($id) {
        $this->api->require_method('PUT');

        $input = $this->api->body();

        try {
            $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if vehicle exists
            $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([(int)$id]);
            $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$vehicle) {
                $this->api->respond_error('Vehicle not found', 404);
                return;
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
                $this->api->respond_error('No fields to update', 400);
                return;
            }
            
            $values[] = (int)$id; // for WHERE clause
            
            $sql = "UPDATE vehicles SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            
            // Fetch updated record
            $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
            $stmt->execute([(int)$id]);
            $updated = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->api->respond($updated);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }

    // DELETE /vehicles/{id}
    public function delete($id) {
        $this->api->require_method('DELETE');

        try {
            $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if vehicle exists
            $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([(int)$id]);
            if (!$stmt->fetch()) {
                $this->api->respond_error('Vehicle not found', 404);
                return;
            }
            
            // Soft delete
            $stmt = $pdo->prepare("UPDATE vehicles SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([(int)$id]);
            
            $this->api->respond(['message' => 'Vehicle deleted']);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }
}

?>