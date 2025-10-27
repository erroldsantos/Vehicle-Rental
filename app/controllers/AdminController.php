<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once APP_DIR . 'controllers/ApiController.php';

class AdminController extends ApiController {
    
    // Constructor inherited from ApiController - has $this->db and $this->api
    
    /**
     * Dashboard statistics endpoint
     */
    public function stats() {
        $this->api->require_method('GET');
        
        // Sample statistics - replace with actual database queries
        $stats = [
            'users' => $this->getUserCount(),
            'items' => $this->getItemCount(),
            'orders' => $this->getOrderCount(),
            'revenue' => $this->getRevenue(),
            'growth' => $this->getGrowthData(),
            'recent_activity' => $this->getRecentActivity()
        ];
        
        $this->api->respond($stats);
    }
    
    /**
     * Get all users with pagination
     */
    public function users() {
        $this->api->require_method('GET');
        
        $page = $this->api->get_query_params()['page'] ?? 1;
        $limit = $this->api->get_query_params()['limit'] ?? 10;
        
        // Sample user data - replace with database queries
        $users = [
            [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => '2023-01-15 10:30:00',
                'last_login' => '2023-10-20 14:25:00'
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'role' => 'user',
                'status' => 'active',
                'created_at' => '2023-02-20 09:15:00',
                'last_login' => '2023-10-19 16:45:00'
            ],
            [
                'id' => 3,
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
                'role' => 'moderator',
                'status' => 'inactive',
                'created_at' => '2023-03-10 11:20:00',
                'last_login' => '2023-10-18 08:30:00'
            ],
            [
                'id' => 4,
                'name' => 'Alice Brown',
                'email' => 'alice@example.com',
                'role' => 'user',
                'status' => 'active',
                'created_at' => '2023-04-05 13:45:00',
                'last_login' => '2023-10-21 10:15:00'
            ]
        ];
        
        $response = [
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($users),
                'pages' => 1
            ]
        ];
        
        $this->api->respond($response);
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
        
        // Sample deletion - replace with database deletion
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
        
        $type = $this->api->get_query_params()['type'] ?? 'overview';
        
        $analytics = [
            'user_growth' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'data' => [12, 19, 15, 25, 22, 30]
            ],
            'item_statistics' => [
                'labels' => ['Active Items', 'Inactive Items', 'Pending Items'],
                'data' => [65, 25, 10]
            ],
            'revenue_trend' => [
                'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                'data' => [1200, 1500, 1800, 2100]
            ],
            'page_views' => [
                'today' => 245,
                'yesterday' => 198,
                'this_week' => 1423,
                'this_month' => 6789
            ]
        ];
        
        $this->api->respond($analytics);
    }
    
    /**
     * System settings
     */
    public function settings() {
        if ($this->api->get_query_params() && isset($this->api->get_query_params()['method'])) {
            $method = $this->api->get_query_params()['method'];
        } else {
            $method = $_SERVER['REQUEST_METHOD'];
        }
        
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
            
            // Sample settings update - replace with actual configuration save
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
        
        $overview = [
            'stats' => [
                'users' => $this->getUserCount(),
                'items' => $this->getItemCount(),
                'orders' => $this->getOrderCount(),
                'revenue' => $this->getRevenue()
            ],
            'recent_activity' => $this->getRecentActivity(),
            'alerts' => $this->getSystemAlerts(),
            'quick_stats' => [
                'online_users' => 12,
                'pending_orders' => 5,
                'low_stock_items' => 3,
                'unread_messages' => 8
            ]
        ];
        
        $this->api->respond($overview);
    }
    
    // Helper Methods
    private function getUserCount() {
        // Replace with actual database query
        // Example: return $this->db->table('users')->count();
        return 125;
    }
    
    private function getItemCount() {
        // Replace with actual database query
        return 89;
    }
    
    private function getOrderCount() {
        // Replace with actual database query
        return 45;
    }
    
    private function getRevenue() {
        // Replace with actual database query
        return 12500;
    }
    
    private function getGrowthData() {
        return [
            'users_this_month' => 15,
            'users_last_month' => 12,
            'items_this_month' => 8,
            'items_last_month' => 6
        ];
    }
    
    private function getRecentActivity() {
        return [
            [
                'id' => 1,
                'action' => 'User registered',
                'user' => 'John Doe',
                'time' => '2 minutes ago',
                'status' => 'active'
            ],
            [
                'id' => 2,
                'action' => 'Item created',
                'user' => 'Admin',
                'time' => '5 minutes ago',
                'status' => 'active'
            ],
            [
                'id' => 3,
                'action' => 'User login',
                'user' => 'Jane Smith',
                'time' => '10 minutes ago',
                'status' => 'active'
            ],
            [
                'id' => 4,
                'action' => 'Item deleted',
                'user' => 'Admin',
                'time' => '15 minutes ago',
                'status' => 'inactive'
            ]
        ];
    }
    
    private function getSystemAlerts() {
        return [
            [
                'type' => 'warning',
                'message' => 'System backup is overdue',
                'created_at' => '2023-10-21 09:00:00'
            ],
            [
                'type' => 'info',
                'message' => 'New update available',
                'created_at' => '2023-10-20 15:30:00'
            ]
        ];
    }
}
?>