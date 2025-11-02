<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once APP_DIR . 'controllers/ApiController.php';

class VehiclesController extends ApiController {

    public function __construct() {
        parent::__construct();
        // Load the Vehicle model (ORM-based)
        $this->call->model('Vehicle');
    }

    // GET /vehicles
    public function index() {
        $this->api->require_method('GET');

        try {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            // Use ORM-based Vehicle model
            $vehicles = $this->Vehicle->getAllVehicles($filters);
            
            // Convert objects to arrays for consistent JSON output
            if (!empty($vehicles)) {
                $vehicles = array_map(function($vehicle) {
                    return is_object($vehicle) ? (array)$vehicle : $vehicle;
                }, $vehicles);
            }
            
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
            // Use ORM to find vehicle
            $vehicle = $this->Vehicle->getVehicleById($id);
            
            if (!$vehicle) {
                return $this->error('Vehicle not found', 404);
            }
            
            // Convert object to array if needed
            $vehicle = is_object($vehicle) ? (array)$vehicle : $vehicle;
            
            $this->success($vehicle);
            
        } catch (Exception $e) {
            $this->handleDbError($e, 'Failed to fetch vehicle');
        }
    }

    // POST /vehicles
    public function create() {
        $this->api->require_method('POST');
        $input = $this->api->body();

        try {
            // Use ORM model to create vehicle (includes validation)
            $this->Vehicle->createVehicle($input);
            $id = $this->db->last_id();
            
            // Fetch the created record
            $created = $this->Vehicle->getVehicleById($id);
            $created = is_object($created) ? (array)$created : $created;
            
            $this->api->respond($created, 201);
            
        } catch (Exception $e) {
            // Model throws exceptions with validation errors
            $this->error($e->getMessage(), 400);
        }
    }


    // PUT /vehicles/{id}
    public function update($id) {
        $this->api->require_method('PUT');
        $input = $this->api->body();

        try {
            // Use ORM model to update vehicle (includes validation)
            $this->Vehicle->updateVehicle($id, $input);
            
            // Fetch updated record
            $updated = $this->Vehicle->getVehicleById($id);
            $updated = is_object($updated) ? (array)$updated : $updated;
            
            $this->success($updated, 'Vehicle updated successfully');
            
        } catch (Exception $e) {
            // Handle different error types
            if (strpos($e->getMessage(), 'not found') !== false) {
                $this->error($e->getMessage(), 404);
            } else {
                $this->error($e->getMessage(), 400);
            }
        }
    }

    // DELETE /vehicles/{id}
    public function delete($id) {
        $this->api->require_method('DELETE');

        try {
            // Use ORM model to soft delete vehicle
            $this->Vehicle->deleteVehicle($id);
            
            $this->api->respond(['message' => 'Vehicle deleted successfully']);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false) {
                $this->error($e->getMessage(), 404);
            } else {
                $this->error($e->getMessage(), 400);
            }
        }
    }
}

?>
