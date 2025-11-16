<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AdminController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->call->library('api');
        $this->call->model('User');
        $this->call->model('Vehicle');
        $this->call->model('Booking');
        $this->call->model('Maintenance');
    }
    
    /**
     * Dashboard statistics endpoint
     */
    public function stats() {
        $this->api->require_method('GET');
        
        try {
            $stats = [
                'users' => $this->User->getUserStats(),
                'vehicles' => $this->Vehicle->getVehicleStats(),
                'bookings' => $this->Booking->getBookingStats(),
                'maintenance' => $this->Maintenance->getMaintenanceStats(),
                'recent_activity' => $this->getRecentActivity()
            ];
            
            $this->api->respond($stats);
        } catch (Exception $e) {
            $this->api->respond_error('Failed to load dashboard stats: ' . $e->getMessage(), 500);
        }
    }
    
    public function users() {
        $this->api->require_method('GET');
        
        try {
            // Get all GET parameters if any exist
            $getAllParams = !empty($_GET) ? $this->io->get() : [];
            $filters = [
                'search' => isset($getAllParams['search']) ? $getAllParams['search'] : null,
                'role' => isset($getAllParams['role']) ? $getAllParams['role'] : null
            ];
            
            $users = $this->User->getAllUsers($filters);
            $stats = $this->User->getUserStats();
            
            $response = [
                'users' => $users,
                'stats' => $stats,
                'total' => count($users)
            ];
            
            $this->api->respond($response);
        } catch (Exception $e) {
            $this->api->respond_error('Failed to load users: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Create new user
     */
    public function create_user() {
        $this->api->require_method('POST');
        
        $input = $this->api->body();
        
        // Validate input
        if (empty($input['name']) || empty($input['email'])) {
            $this->api->respond_error('Name and email are required', 400);
            return;
        }
        
        // Sample user creation - replace with database insertion
        $new_user = [
            'id' => rand(100, 999),
            'name' => $input['name'],
            'email' => $input['email'],
            'role' => $input['role'] ?? 'user',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => null
        ];
        
        $this->api->respond($new_user, 201);
    }
    
    /**
     * Update user
     */
    public function update_user($id) {
        $this->api->require_method('PUT');
        
        $input = $this->api->body();
        
        // Sample user update - replace with database update
        $updated_user = [
            'id' => $id,
            'name' => $input['name'] ?? 'Updated User',
            'email' => $input['email'] ?? 'updated@example.com',
            'role' => $input['role'] ?? 'user',
            'status' => $input['status'] ?? 'active',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->api->respond($updated_user);
    }
    
    /**
     * Delete user
     */
    public function delete_user($id) {
        $this->api->require_method('DELETE');
        
        // Check if user exists and can be deleted
        if ($id == 1) {
            $this->api->respond_error('Cannot delete admin user', 403);
            return;
        }
        
        $this->api->respond(['message' => 'User ' . $id . ' deleted successfully']);
    }
    
    /**
     * Get system logs
     */
    public function logs() {
        $this->api->require_method('GET');
        
        $logs = [
            [
                'id' => 1,
                'level' => 'info',
                'message' => 'User login successful',
                'user_id' => 2,
                'ip_address' => '192.168.1.100',
                'timestamp' => '2023-10-21 10:30:00'
            ],
            [
                'id' => 2,
                'level' => 'warning',
                'message' => 'Failed login attempt',
                'user_id' => null,
                'ip_address' => '192.168.1.105',
                'timestamp' => '2023-10-21 10:25:00'
            ],
            [
                'id' => 3,
                'level' => 'info',
                'message' => 'New item created',
                'user_id' => 1,
                'ip_address' => '192.168.1.100',
                'timestamp' => '2023-10-21 10:20:00'
            ]
        ];
        
        $this->api->respond($logs);
    }
    
    /**
     * Get analytics data
     */
    public function analytics() {
        $this->api->require_method('GET');
        
        try {
            $bookingStats = $this->Booking->getBookingStats();
            $vehicleStats = $this->Vehicle->getVehicleStats();
            $maintenanceStats = $this->Maintenance->getMaintenanceStats();
            
            $analytics = [
                'booking_status' => [
                    'labels' => ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
                    'data' => [
                        $bookingStats['pending_bookings'],
                        $bookingStats['confirmed_bookings'],
                        $bookingStats['completed_bookings'],
                        $bookingStats['cancelled_bookings']
                    ]
                ],
                'vehicle_status' => [
                    'labels' => ['Available', 'Rented', 'Maintenance'],
                    'data' => [
                        $vehicleStats['available_vehicles'],
                        $vehicleStats['rented_vehicles'],
                        $vehicleStats['maintenance_vehicles']
                    ]
                ],
                'maintenance_status' => [
                    'labels' => ['Scheduled', 'In Progress', 'Completed'],
                    'data' => [
                        $maintenanceStats['scheduled_maintenance'],
                        $maintenanceStats['inprogress_maintenance'],
                        $maintenanceStats['completed_maintenance']
                    ]
                ],
                'revenue' => [
                    'total_revenue' => $bookingStats['total_revenue'],
                    'monthly_bookings' => $bookingStats['monthly_bookings'],
                    'total_maintenance_cost' => $maintenanceStats['total_cost']
                ]
            ];
            
            $this->api->respond($analytics);
        } catch (Exception $e) {
            $this->api->respond_error('Failed to load analytics: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * System settings
     */
    public function settings() {
        $method = $this->io->method();
        
        if ($method === 'GET') {
            $settings = [
                'site_name' => 'LavaLust Admin',
                'site_description' => 'Admin Dashboard for LavaLust Framework',
                'items_per_page' => 10,
                'enable_registration' => true,
                'maintenance_mode' => false,
                'api_rate_limit' => 100,
                'session_timeout' => 3600
            ];
            
            $this->api->respond($settings);
        } else if ($method === 'POST') {
            $input = $this->api->body();

            $this->api->respond(['message' => 'Settings updated successfully']);
        }
    }
    
    /**
     * Export data
     */
    public function export() {
        $this->api->require_method('GET');
        
        $type = $this->api->get_query_params()['type'] ?? 'users';
        
        // Sample export data
        $export_data = [
            'type' => $type,
            'generated_at' => date('Y-m-d H:i:s'),
            'total_records' => 100,
            'download_url' => '/exports/' . $type . '_' . date('Ymd') . '.csv'
        ];
        
        $this->api->respond($export_data);
    }
    
    /**
     * Dashboard overview
     */
    public function overview() {
        $this->api->require_method('GET');
        
        try {
            $userStats = $this->User->getUserStats();
            $vehicleStats = $this->Vehicle->getVehicleStats();
            $bookingStats = $this->Booking->getBookingStats();
            $maintenanceStats = $this->Maintenance->getMaintenanceStats();
            
            $overview = [
                'stats' => [
                    'total_users' => $userStats['total_users'],
                    'total_vehicles' => $vehicleStats['total_vehicles'],
                    'total_bookings' => $bookingStats['total_bookings'],
                    'total_revenue' => $bookingStats['total_revenue']
                ],
                'recent_activity' => $this->getRecentActivity(),
                'alerts' => $this->getSystemAlerts(),
                'quick_stats' => [
                    'available_vehicles' => $vehicleStats['available_vehicles'],
                    'pending_bookings' => $bookingStats['pending_bookings'],
                    'vehicles_in_maintenance' => $vehicleStats['maintenance_vehicles'],
                    'scheduled_maintenance' => $maintenanceStats['scheduled_maintenance']
                ],
                'user_stats' => $userStats,
                'vehicle_stats' => $vehicleStats,
                'booking_stats' => $bookingStats,
                'maintenance_stats' => $maintenanceStats
            ];
            
            $this->api->respond($overview);
        } catch (Exception $e) {
            $this->api->respond_error('Failed to load dashboard overview: ' . $e->getMessage(), 500);
        }
    }
    
    private function getRecentActivity() {
        try {
            // Get recent bookings with user and vehicle info
            $query = "SELECT 
                        b.id,
                        b.booking_reference,
                        b.status,
                        b.created_at,
                        u.fullname as user_name,
                        CONCAT(v.brand, ' ', v.model) as vehicle_name
                      FROM bookings b
                      LEFT JOIN users u ON b.user_id = u.id
                      LEFT JOIN vehicles v ON b.vehicle_id = v.id
                      WHERE b.deleted_at IS NULL
                      ORDER BY b.created_at DESC
                      LIMIT 10";
            
            $stmt = $this->db->raw($query);
            $bookings = $stmt->fetchAll();
            
            $activities = [];
            foreach ($bookings as $booking) {
                $activities[] = [
                    'id' => $booking['id'],
                    'action' => 'Booking ' . ucfirst($booking['status']),
                    'user' => $booking['user_name'],
                    'description' => $booking['vehicle_name'] . ' - ' . $booking['booking_reference'],
                    'time' => $booking['created_at'],
                    'status' => $booking['status']
                ];
            }
            
            return $activities;
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getSystemAlerts() {
        try {
            $alerts = [];
            
            // Check for vehicles in maintenance
            $maintenanceStats = $this->Maintenance->getMaintenanceStats();
            if ($maintenanceStats['scheduled_maintenance'] > 0) {
                $alerts[] = [
                    'type' => 'info',
                    'message' => $maintenanceStats['scheduled_maintenance'] . ' vehicle(s) scheduled for maintenance',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
            
            // Check for pending bookings
            $bookingStats = $this->Booking->getBookingStats();
            if ($bookingStats['pending_bookings'] > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => $bookingStats['pending_bookings'] . ' pending booking(s) require attention',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
            
            // Check for low vehicle availability
            $vehicleStats = $this->Vehicle->getVehicleStats();
            $availabilityRate = ($vehicleStats['total_vehicles'] > 0) 
                ? ($vehicleStats['available_vehicles'] / $vehicleStats['total_vehicles']) * 100 
                : 0;
            
            if ($availabilityRate < 30) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => 'Low vehicle availability: ' . round($availabilityRate) . '%',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
            
            return $alerts;
        } catch (Exception $e) {
            return [];
        }
    }
}
?>