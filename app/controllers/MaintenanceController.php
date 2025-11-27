<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class MaintenanceController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->call->library('api');
        $this->call->model('Maintenance');
        $this->call->model('Vehicle');
        $this->call->model('Booking');
    }

    public function index() {
        $this->api->require_method('GET');

        try {
            $getAllParams = !empty($_GET) ? $this->io->get() : [];
            $filters = [
                'status' => isset($getAllParams['status']) ? $getAllParams['status'] : null,
                'vehicle_id' => isset($getAllParams['vehicle_id']) ? $getAllParams['vehicle_id'] : null
            ];

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
    
    public function vehicles() {
        $this->api->require_method('GET');
        
        try {
            $filters = ['exclude_rented' => true];
            $vehicles = $this->Vehicle->getAllVehicles($filters);
            
            // Format for display
            $formatted = array_map(function($v) {
                $v['display_name'] = "{$v['brand']} {$v['model']} ({$v['year']}) - {$v['plate_number']}";
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

    /**
     * POST /maintenance/inspect/{booking_id} - Create damage inspection for returned booking
     */
    public function inspect($booking_id) {
        $this->api->require_method('POST');

        $input = $this->api->body();

        try {
            // Get booking details
            $booking = $this->Booking->getBookingById($booking_id);
            if (!$booking) {
                $this->api->respond_error('Booking not found', 404);
                return;
            }

            // Check if booking is returned
            if ($booking['status'] !== 'returned') {
                $this->api->respond_error('Only returned bookings can be inspected', 400);
                return;
            }

            // Check damage status
            $has_damage = isset($input['has_damage']) ? filter_var($input['has_damage'], FILTER_VALIDATE_BOOLEAN) : false;

            if (!$has_damage) {
                // No damage - mark booking as completed
                $this->Booking->updateBooking($booking_id, ['status' => 'completed']);
                
                $this->api->respond([
                    'message' => 'Vehicle inspected - no damage found',
                    'booking_status' => 'completed'
                ]);
                return;
            }

            // Has damage - create maintenance record
            $damageData = [
                'vehicle_id' => $booking['vehicle_id'],
                'booking_id' => $booking_id,
                'damage_type' => $input['damage_type'] ?? 'Unspecified damage',
                'cost' => $input['cost'] ?? 0,
                'description' => $input['notes'] ?? 'Damage from rental: ' . ($input['damage_type'] ?? 'Unspecified')
            ];

            $maintenance = $this->Maintenance->createDamageInspection($damageData);

            $this->api->respond([
                'message' => 'Damage recorded - awaiting customer payment',
                'maintenance' => $maintenance,
                'booking_status' => 'returned'
            ], 201);

        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * PUT /maintenance/{id}/mark-paid - Mark damage as paid and start repair
     */
    public function markPaid($id) {
        $this->api->require_method('PUT');

        try {
            $maintenance = $this->Maintenance->getMaintenanceById($id);
            
            if (!$maintenance) {
                $this->api->respond_error('Maintenance record not found', 404);
                return;
            }

            if ($maintenance['status'] !== 'pending') {
                $this->api->respond_error('Only pending damage can be marked as paid', 400);
                return;
            }

            // Mark as paid (changes status to scheduled)
            $updated = $this->Maintenance->markDamagePaid($id);

            // If there's a booking_id, complete the booking now that damage is paid
            if ($maintenance['booking_id']) {
                $this->Booking->updateBooking($maintenance['booking_id'], ['status' => 'completed']);
            }

            $this->api->respond([
                'message' => 'Damage payment recorded - vehicle in maintenance',
                'maintenance' => $updated
            ]);

        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * GET /maintenance/booking/{booking_id} - Get maintenance records for a booking
     */
    public function byBooking($booking_id) {
        $this->api->require_method('GET');

        try {
            $maintenance = $this->Maintenance->getByBookingId($booking_id);
            $this->api->respond($maintenance);
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
}

?>
