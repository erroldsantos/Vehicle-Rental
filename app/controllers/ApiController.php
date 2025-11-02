<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * 
 * All API controllers should extend this class.
 */
class ApiController extends Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Load API library for all API controllers
        $this->call->library('api');
        
        // Explicitly load and assign database to $this->db
        $this->call->database();
        // The database is now accessible via $this->db (assigned by __get magic method)
    }
    
    /**
     * Validate required fields in request body
     * 
     * @param array $data Request data
     * @param array $required Required field names
     * @return bool Returns true if valid, sends error response and returns false otherwise
     */
    protected function validateRequired($data, $required) {
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->api->respond_error("Field '$field' is required", 400);
                return false;
            }
        }
        return true;
    }
    
    /**
     * Handle database errors consistently
     * 
     * @param Exception $e The exception
     * @param string $message Custom error message
     */
    protected function handleDbError($e, $message = 'Database error occurred') {
        // Log the actual error (in production, use proper logging)
        error_log($e->getMessage());
        
        // Send user-friendly error
        $this->api->respond_error($message, 500);
    }
    
    /**
     * Quick success response
     * 
     * @param mixed $data Data to return
     * @param string $message Optional message
     */
    protected function success($data, $message = null) {
        $response = ['data' => $data];
        if ($message) {
            $response['message'] = $message;
        }
        $this->api->respond($response);
    }
    
    /**
     * Quick error response
     * 
     * @param string $message Error message
     * @param int $code HTTP status code
     */
    protected function error($message, $code = 400) {
        $this->api->respond_error($message, $code);
    }
    
    /**
     * Health check endpoint for Vue frontend
     */
    public function health() {
        $this->api->require_method('GET');
        
        try {
            // Test database connection using raw SQL
            $stmt = $this->db->raw('SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL');
            $result = $stmt->fetch();
            $userCount = $result['count'];
            
            $this->api->respond([
                'status' => 'ok',
                'message' => 'LavaLust API is running',
                'database' => 'connected',
                'users_count' => $userCount,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
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