<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class BookingsController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->call->library('api');
        $this->call->model('Booking');
    }
    
    /**
     * Get all bookings with filtering
     * GET /api/bookings
     */
    public function index() {
        $this->api->require_method('GET');
        
        try {
            // Get all GET parameters if any exist
            $getAllParams = !empty($_GET) ? $this->io->get() : [];
            $filters = [
                'status' => isset($getAllParams['status']) ? $getAllParams['status'] : null,
                'user_id' => isset($getAllParams['user_id']) ? $getAllParams['user_id'] : null,
                'vehicle_id' => isset($getAllParams['vehicle_id']) ? $getAllParams['vehicle_id'] : null,
                'start_date' => isset($getAllParams['start_date']) ? $getAllParams['start_date'] : null,
                'end_date' => isset($getAllParams['end_date']) ? $getAllParams['end_date'] : null,
                'search' => isset($getAllParams['search']) ? $getAllParams['search'] : null
            ];
            
            $bookings = $this->Booking->getAllBookings($filters);
            
            // Convert objects to arrays for consistent JSON output
            if (!empty($bookings)) {
                $bookings = array_map(function($booking) {
                    return is_object($booking) ? (array)$booking : $booking;
                }, $bookings);
            }
            
            // Get statistics
            $stats = $this->Booking->getBookingStats();
            
            $this->api->respond([
                'bookings' => $bookings,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Get single booking
     * GET /api/bookings/{id}
     */
    public function show($id) {
        $this->api->require_method('GET');
        
        try {
            if (empty($id)) {
                $this->api->respond_error('Booking ID is required', 400);
                return;
            }

            // Find booking
            $booking = $this->Booking->getBookingById($id);
            
            if (!$booking) {
                $this->api->respond_error('Booking not found', 404);
                return;
            }
            
            // Convert object to array if needed
            $booking = is_object($booking) ? (array)$booking : $booking;
            
            $this->api->respond($booking);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Create new booking
     * POST /api/bookings
     */
    public function create() {
        $this->api->require_method('POST');
        
        try {
            $input = $this->api->body();
            
            // create booking (includes validation)
            $booking_id = $this->Booking->createBooking($input);
            
            // Fetch and return the created booking
            $booking = $this->Booking->getBookingById($booking_id);
            $booking = is_object($booking) ? (array)$booking : $booking;
            
            $this->api->respond($booking, 201);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 400);
        }
    }
    
    /**
     * Update booking
     * PUT /api/bookings/{id}
     */
    public function update($id) {
        $this->api->require_method('PUT');
        
        try {
            if (empty($id)) {
                $this->api->respond_error('Booking ID is required', 400);
                return;
            }
            
            $input = $this->api->body();
            
            // Check if status is being changed to 'confirmed'
            $warning = null;
            if (isset($input['status']) && $input['status'] === 'confirmed') {
                // Get the booking to find the user_id
                $booking = $this->Booking->getBookingById($id);
                if ($booking) {
                    $userId = is_object($booking) ? $booking->user_id : $booking['user_id'];
                    // Check user's confirmed bookings count
                    $confirmedCount = $this->Booking->getUserConfirmedBookingsCount($userId);
                    if ($confirmedCount >= 2) {
                        $warning = "Warning: This user already has {$confirmedCount} confirmed bookings. The recommended limit is 2 confirmed bookings per user.";
                    }
                }
            }
            
            // update booking (includes validation)
            $this->Booking->updateBooking($id, $input);
            
            // Fetch and return the updated booking
            $booking = $this->Booking->getBookingById($id);
            $booking = is_object($booking) ? (array)$booking : $booking;
            
            $response = ['booking' => $booking];
            if ($warning) {
                $response['warning'] = $warning;
            }
            
            $this->api->respond($response);
            
        } catch (Exception $e) {
            // Handle different error types
            if (strpos($e->getMessage(), 'not found') !== false) {
                $this->api->respond_error($e->getMessage(), 404);
            } else {
                $this->api->respond_error($e->getMessage(), 400);
            }
        }
    }
    
    /**
     * Cancel booking
     * PUT /api/bookings/{id}/cancel
     */
    public function cancel($id) {
        $this->api->require_method('PUT');
        
        try {
            if (empty($id)) {
                $this->api->respond_error('Booking ID is required', 400);
                return;
            }
            
            // cancel booking
            $this->Booking->cancelBooking($id);
            
            $this->api->respond(['message' => 'Booking cancelled successfully']);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false) {
                $this->api->respond_error($e->getMessage(), 404);
            } else {
                $this->api->respond_error($e->getMessage(), 500);
            }
        }
    }
    
    /**
     * Delete booking (soft delete)
     * DELETE /api/bookings/{id}
     */
    public function delete($id) {
        $this->api->require_method('DELETE');
        
        try {
            if (empty($id)) {
                $this->api->respond_error('Booking ID is required', 400);
                return;
            }
            
            // soft delete booking
            $this->Booking->deleteBooking($id);
            
            $this->api->respond(['message' => 'Booking deleted successfully']);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false) {
                $this->api->respond_error($e->getMessage(), 404);
            } else {
                $this->api->respond_error($e->getMessage(), 500);
            }
        }
    }
    
    /**
     * Get available vehicles for date range
     */
    public function availableVehicles() {
        $this->api->require_method('GET');
        
        try {
            $getAllParams = !empty($_GET) ? $this->io->get() : [];
            $start_date = isset($getAllParams['start_date']) ? $getAllParams['start_date'] : null;
            $end_date = isset($getAllParams['end_date']) ? $getAllParams['end_date'] : null;
            
            if (!$start_date || !$end_date) {
                $this->api->respond_error('start_date and end_date are required', 400);
                return;
            }
            
            if ($start_date >= $end_date) {
                $this->api->respond_error('End date must be after start date', 400);
                return;
            }
            
            $vehicles = $this->Booking->getAvailableVehicles($start_date, $end_date);
            
            // Convert objects to arrays for consistent JSON output
            if (!empty($vehicles)) {
                $vehicles = array_map(function($vehicle) {
                    return is_object($vehicle) ? (array)$vehicle : $vehicle;
                }, $vehicles);
            }
            
            $this->api->respond(['vehicles' => $vehicles]);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Get all users for booking form
     * GET /api/bookings/users
     */
    public function users() {
        $this->api->require_method('GET');
        
        try {
            $stmt = $this->db->raw("SELECT id, first_name, last_name, email FROM users WHERE deleted_at IS NULL ORDER BY first_name, last_name");
            $users = $stmt->fetchAll();
            
            $this->api->respond(['users' => $users]);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
}
?>