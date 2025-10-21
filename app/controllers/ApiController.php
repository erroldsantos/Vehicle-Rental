<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ApiController extends Controller {
    
    public function __construct() {
        parent::__construct();
        // Load API library for Vue frontend communication
        $this->call->library('api');
    }
    
    /**
     * Get all items - Example endpoint for Vue frontend
     */
    public function index() {
        $this->api->require_method('GET');
        
        // Sample data - replace with your database queries
        $items = [
            ['id' => 1, 'name' => 'Item 1', 'description' => 'First item'],
            ['id' => 2, 'name' => 'Item 2', 'description' => 'Second item'],
            ['id' => 3, 'name' => 'Item 3', 'description' => 'Third item']
        ];
        
        $this->api->respond($items);
    }
    
    /**
     * Get single item by ID
     */
    public function show($id) {
        $this->api->require_method('GET');
        
        // Sample data - replace with database query
        $item = ['id' => $id, 'name' => 'Item ' . $id, 'description' => 'Description for item ' . $id];
        
        if (!$item) {
            $this->api->respond_error('Item not found', 404);
            return;
        }
        
        $this->api->respond($item);
    }
    
    /**
     * Create new item
     */
    public function create() {
        $this->api->require_method('POST');
        
        $input = $this->api->body();
        
        // Validate input
        if (empty($input['name'])) {
            $this->api->respond_error('Name is required', 400);
            return;
        }
        
        // Sample creation logic - replace with database insertion
        $new_item = [
            'id' => rand(100, 999),
            'name' => $input['name'],
            'description' => $input['description'] ?? ''
        ];
        
        $this->api->respond($new_item, 201);
    }
    
    /**
     * Update existing item
     */
    public function update($id) {
        $this->api->require_method('PUT');
        
        $input = $this->api->body();
        
        // Sample update logic - replace with database update
        $updated_item = [
            'id' => $id,
            'name' => $input['name'] ?? 'Updated Item',
            'description' => $input['description'] ?? 'Updated description'
        ];
        
        $this->api->respond($updated_item);
    }
    
    /**
     * Delete item
     */
    public function delete($id) {
        $this->api->require_method('DELETE');
        
        // Sample deletion logic - replace with database deletion
        $this->api->respond(['message' => 'Item ' . $id . ' deleted successfully']);
    }
    
    /**
     * Health check endpoint for Vue frontend
     */
    public function health() {
        $this->api->require_method('GET');
        
        $this->api->respond([
            'status' => 'ok',
            'message' => 'LavaLust API is running',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get configuration data for Vue frontend
     */
    public function config() {
        $this->api->require_method('GET');
        
        $config = [
            'app_name' => 'LavaLust Vue App',
            'version' => '1.0.0',
            'timezone' => date_default_timezone_get()
        ];
        
        $this->api->respond($config);
    }
}
?>