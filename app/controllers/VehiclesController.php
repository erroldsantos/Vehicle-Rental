<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class VehiclesController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->call->library('api');
        $this->call->model('Vehicle');
        $this->call->model('Booking');
        $this->call->model('Maintenance');
    }

    // GET /vehicles
    public function index() {
        $this->api->require_method('GET');

        try {
            // Get all GET parameters if any exist
            $getAllParams = !empty($_GET) ? $this->io->get() : [];
            $filters = [
                'status' => isset($getAllParams['status']) ? $getAllParams['status'] : null,
                'search' => isset($getAllParams['search']) ? $getAllParams['search'] : null,
                'start_date' => isset($getAllParams['start_date']) ? $getAllParams['start_date'] : null,
                'end_date' => isset($getAllParams['end_date']) ? $getAllParams['end_date'] : null
            ];
            
            $vehicles = $this->Vehicle->getAllVehicles($filters);
            
            // If dates are provided, check availability for each vehicle
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $vehicles = array_map(function($vehicle) use ($filters) {
                    $vehicle = is_object($vehicle) ? (array)$vehicle : $vehicle;
                    $vehicle['is_available'] = $this->checkVehicleAvailability(
                        $vehicle['id'], 
                        $filters['start_date'], 
                        $filters['end_date']
                    );
                    return $vehicle;
                }, $vehicles);
            } else {
                // Convert objects to arrays for consistent JSON output
                if (!empty($vehicles)) {
                    $vehicles = array_map(function($vehicle) {
                        return is_object($vehicle) ? (array)$vehicle : $vehicle;
                    }, $vehicles);
                }
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
    
    // GET /vehicles/{id}/booked-dates
    public function bookedDates($id) {
        $this->api->require_method('GET');
        
        try {
            // Get all confirmed and pending bookings for this vehicle
            $bookedDates = $this->db->raw("
                SELECT start_date, end_date 
                FROM bookings 
                WHERE vehicle_id = ? 
                AND status IN ('confirmed', 'pending')
                AND deleted_at IS NULL
                ORDER BY start_date
            ", [$id])->fetchAll();
            
            // Also get maintenance dates
            $maintenanceDates = $this->db->raw("
                SELECT scheduled_date 
                FROM maintenance 
                WHERE vehicle_id = ? 
                AND status = 'scheduled'
                AND deleted_at IS NULL
                ORDER BY scheduled_date
            ", [$id])->fetchAll();
            
            $this->api->respond([
                'booked_dates' => $bookedDates,
                'maintenance_dates' => $maintenanceDates
            ]);
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->api->respond_error('Failed to fetch booked dates', 500);
        }
    }
    
    /**
     * Check if a vehicle is available for a specific date range
     * Considers existing bookings and maintenance schedules
     */
    private function checkVehicleAvailability($vehicle_id, $start_date, $end_date) {
        // Check for conflicting bookings (confirmed or pending)
        $bookingConflict = $this->db->raw("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE vehicle_id = ? 
            AND status IN ('confirmed', 'pending')
            AND deleted_at IS NULL
            AND (
                (start_date <= ? AND end_date >= ?) OR
                (start_date <= ? AND end_date >= ?) OR
                (start_date >= ? AND end_date <= ?)
            )
        ", [
            $vehicle_id,
            $start_date, $start_date,
            $end_date, $end_date,
            $start_date, $end_date
        ])->fetch();
        
        if ($bookingConflict['count'] > 0) {
            return false;
        }
        
        // Check for maintenance schedule conflicts
        // Since maintenance table doesn't have end date or duration, 
        // we'll assume maintenance takes 1 day by default
        $maintenanceConflict = $this->db->raw("
            SELECT COUNT(*) as count 
            FROM maintenance 
            WHERE vehicle_id = ? 
            AND status = 'scheduled'
            AND deleted_at IS NULL
            AND (
                (scheduled_date >= ? AND scheduled_date <= ?)
            )
        ", [
            $vehicle_id,
            $start_date,
            $end_date
        ])->fetch();
        
        if ($maintenanceConflict['count'] > 0) {
            return false;
        }
        
        return true;
    }
}

?>
