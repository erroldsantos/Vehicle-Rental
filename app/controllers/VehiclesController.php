<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class VehiclesController extends Controller {

    public function __construct() {
        parent::__construct();
        // Load the API library
        $this->call->library('api');
        // Load the Vehicle model (ORM-based)
        $this->call->model('Vehicle');
    }

    // GET /vehicles
    public function index() {
        $this->api->require_method('GET');

        try {
            // Get all GET parameters if any exist
            $getAllParams = !empty($_GET) ? $this->io->get() : [];
            $filters = [
                'status' => isset($getAllParams['status']) ? $getAllParams['status'] : null,
                'search' => isset($getAllParams['search']) ? $getAllParams['search'] : null
            ];
            
            $vehicles = $this->Vehicle->getAllVehicles($filters);
            
            // Convert objects to arrays for consistent JSON output
            if (!empty($vehicles)) {
                $vehicles = array_map(function($vehicle) {
                    return is_object($vehicle) ? (array)$vehicle : $vehicle;
                }, $vehicles);
            }
            
            $this->api->respond([
                'vehicles' => $vehicles,
                'total' => count($vehicles)
            ]);
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->api->respond_error('Failed to fetch vehicles', 500);
        }
    }

    // GET /vehicles/{id}
    public function show($id) {
        $this->api->require_method('GET');
        
        try {
            // Find vehicle
            $vehicle = $this->Vehicle->getVehicleById($id);
            
            if (!$vehicle) {
                return $this->api->respond_error('Vehicle not found', 404);
            }
            
            // Convert object to array if needed
            $vehicle = is_object($vehicle) ? (array)$vehicle : $vehicle;
            
            $this->api->respond($vehicle);
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->api->respond_error('Failed to fetch vehicle', 500);
        }
    }

    // POST /vehicles
    public function create() {
        $this->api->require_method('POST');
        $input = $this->api->body();

        try {
            // Create vehicle (includes validation)
            $this->Vehicle->createVehicle($input);
            $id = $this->db->last_id();
            
            // Fetch the created record
            $created = $this->Vehicle->getVehicleById($id);
            $created = is_object($created) ? (array)$created : $created;
            
            $this->api->respond($created, 201);
            
        } catch (Exception $e) {
            // Model throws exceptions with validation errors
            $this->api->respond_error($e->getMessage(), 400);
        }
    }


    // PUT /vehicles/{id}
    public function update($id) {
        $this->api->require_method('PUT');
        $input = $this->api->body();

        try {
            // Update vehicle (includes validation)
            $this->Vehicle->updateVehicle($id, $input);
            
            // Fetch updated record
            $updated = $this->Vehicle->getVehicleById($id);
            $updated = is_object($updated) ? (array)$updated : $updated;
            
            $this->api->respond([
                'data' => $updated,
                'message' => 'Vehicle updated successfully'
            ]);
            
        } catch (Exception $e) {
            // Handle different error types
            if (strpos($e->getMessage(), 'not found') !== false) {
                $this->api->respond_error($e->getMessage(), 404);
            } else {
                $this->api->respond_error($e->getMessage(), 400);
            }
        }
    }

    // DELETE /vehicles/{id}
    public function delete($id) {
        $this->api->require_method('DELETE');

        try {
            // Soft delete vehicle
            $this->Vehicle->deleteVehicle($id);
            
            $this->api->respond(['message' => 'Vehicle deleted successfully']);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false) {
                $this->api->respond_error($e->getMessage(), 404);
            } else {
                $this->api->respond_error($e->getMessage(), 400);
            }
        }
    }
}

?>
