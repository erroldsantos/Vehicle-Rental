<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once APP_DIR . 'controllers/ApiController.php';

class MaintenanceController extends ApiController {
    
    public function __construct() {
        parent::__construct();
        $this->call->model('Maintenance');
        $this->call->model('Vehicle');
    }

    // GET /maintenance - List all maintenance records with vehicle info
    public function index() {
        $this->api->require_method('GET');

        try {
            $filters = [];
            if (isset($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            if (isset($_GET['vehicle_id'])) {
                $filters['vehicle_id'] = $_GET['vehicle_id'];
            }

            $maintenance = $this->Maintenance->getAllMaintenance($filters);
            $stats = $this->Maintenance->getMaintenanceStats();
            
            $this->api->respond([
                'data' => $maintenance,
                'total' => count($maintenance),
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }

    // GET /maintenance/{id}
    public function show($id) {
        $this->api->require_method('GET');
        
        try {
            $maintenance = $this->Maintenance->getMaintenanceById($id);
            
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
            // Get vehicles that are not rented using Vehicle model
            $filters = ['exclude_rented' => true];
            $vehicles = $this->Vehicle->getAllVehicles($filters);
            
            // Format for display
            $formatted = array_map(function($v) {
                $v['display_name'] = "{$v['brand']} {$v['model']} ({$v['year']}) - {$v['plate_number']} [" . strtoupper($v['status']) . "]";
                return $v;
            }, $vehicles);
            
            $this->api->respond($formatted);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }

    // POST /maintenance
    public function create() {
        $this->api->require_method('POST');

        $input = $this->api->body();

        try {
            $created = $this->Maintenance->createMaintenance($input);
            $this->api->respond($created, 201);
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // PUT /maintenance/{id}
    public function update($id) {
        $this->api->require_method('PUT');

        $input = $this->api->body();

        try {
            $updated = $this->Maintenance->updateMaintenance($id, $input);
            $this->api->respond($updated);
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // DELETE /maintenance/{id}
    public function delete($id) {
        $this->api->require_method('DELETE');

        try {
            $this->Maintenance->deleteMaintenance($id);
            $this->api->respond(['message' => 'Maintenance record deleted']);
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // PUT /maintenance/{id}/complete - Mark maintenance as completed
    public function complete($id) {
        $this->api->require_method('PUT');

        $input = $this->api->body();

        try {
            $cost = isset($input['cost']) ? (float)$input['cost'] : null;
            $updated = $this->Maintenance->completeMaintenance($id, $cost);
            $this->api->respond($updated);
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // POST /maintenance/sync - Sync vehicle statuses with existing maintenance records
    public function sync() {
        $this->api->require_method('POST');

        try {
            // Get all scheduled maintenance records using the model
            $scheduledMaintenance = $this->Maintenance->getAllMaintenance(['status' => 'scheduled']);
            $scheduledVehicleIds = array_unique(array_column($scheduledMaintenance, 'vehicle_id'));
            
            $updatedCount = 0;
            $resetCount = 0;
            
            // Update each vehicle with scheduled maintenance
            foreach ($scheduledVehicleIds as $vehicleId) {
                $vehicle = $this->Vehicle->getVehicleById($vehicleId);
                if ($vehicle && $vehicle['status'] !== 'rented') {
                    $this->Vehicle->updateVehicle($vehicleId, ['status' => 'maintenance']);
                    $updatedCount++;
                }
            }
            
            // Reset vehicles without scheduled maintenance
            $allVehicles = $this->Vehicle->getAllVehicles();
            foreach ($allVehicles as $vehicle) {
                if ($vehicle['status'] === 'maintenance' && !in_array($vehicle['id'], $scheduledVehicleIds)) {
                    $this->Vehicle->updateVehicle($vehicle['id'], ['status' => 'available']);
                    $resetCount++;
                }
            }
            
            $this->api->respond([
                'message' => 'Vehicle statuses synchronized with maintenance records',
                'vehicles_set_to_maintenance' => $updatedCount,
                'vehicles_reset_to_available' => $resetCount,
                'scheduled_vehicles' => $scheduledVehicleIds
            ]);
            
        } catch (Exception $e) {
            $this->api->respond_error('Database error: ' . $e->getMessage(), 500);
        }
    }
}

?>
