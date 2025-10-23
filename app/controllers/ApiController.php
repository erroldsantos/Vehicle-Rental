<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ApiController extends Controller {
    
    public function __construct() {
        parent::__construct();
        // Load API library for Vue frontend communication
        $this->call->library('api');
    }
    
    /**
     * Health check endpoint for Vue frontend
     */
    public function health() {
        $this->api->require_method('GET');
        
        try {
            // Test database connection
            $pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Test basic query
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL");
            $userCount = $stmt->fetch()['count'];
            
            $this->api->respond([
                'status' => 'ok',
                'message' => 'LavaLust API is running',
                'database' => 'connected',
                'users_count' => $userCount,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (PDOException $e) {
            $this->api->respond([
                'status' => 'warning',
                'message' => 'API running but database connection failed',
                'database' => 'disconnected',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ], 200);
        }
    }
    
    /**
     * Get configuration data for Vue frontend
     */
    public function config() {
        $this->api->require_method('GET');
        
        $config = [
            'app_name' => 'Vehicle Rental Management System',
            'version' => '1.0.0',
            'timezone' => date_default_timezone_get(),
            'api_base_url' => '/Vehicle-Rental/api',
            'features' => [
                'vehicle_management' => true,
                'booking_management' => true,
                'user_management' => true,
                'maintenance_tracking' => true,
                'payment_processing' => true
            ]
        ];
        
        $this->api->respond($config);
    }
}
?>