<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once APP_DIR . 'controllers/ApiController.php';

class UsersController extends ApiController {
    
    public function __construct() {
        parent::__construct();
        // Load the User model (ORM-based)
        $this->call->model('User');
    }
    
    /**
     * Get all users
     * GET /api/users
     */
    public function index() {
        $this->api->require_method('GET');
        
        try {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'role' => $_GET['role'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            // Use ORM-based User model
            $users = $this->User->getAllUsers($filters);
            
            // Convert objects to arrays for consistent JSON output
            if (!empty($users)) {
                $users = array_map(function($user) {
                    return is_object($user) ? (array)$user : $user;
                }, $users);
            }
            
            // Get statistics
            $stats = $this->User->getUserStats();
            
            $this->api->respond([
                'users' => $users,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Get single user
     * GET /api/users/{id}
     */
    public function show($id) {
        $this->api->require_method('GET');
        
        try {
            if (empty($id)) {
                $this->api->respond_error('User ID is required', 400);
                return;
            }
            
            // Use ORM to find user
            $user = $this->User->getUserById($id);
            
            if (!$user) {
                $this->api->respond_error('User not found', 404);
                return;
            }
            
            // Convert object to array if needed
            $user = is_object($user) ? (array)$user : $user;
            
            $this->api->respond($user);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Create new user
     * POST /api/users
     */
    public function create() {
        $this->api->require_method('POST');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->api->respond_error('Invalid JSON data', 400);
                return;
            }
            
            // Use ORM model to create user (includes validation)
            $this->User->createUser($input);
            $userId = $this->db->last_id();
            
            // Fetch and return the created user
            $user = $this->User->getUserById($userId);
            $user = is_object($user) ? (array)$user : $user;
            
            $this->api->respond($user, 201);
            
        } catch (Exception $e) {
            // Model throws exceptions with validation errors
            $this->api->respond_error($e->getMessage(), 400);
        }
    }
    
    /**
     * Update user
     * PUT /api/users/{id}
     */
    public function update($id) {
        $this->api->require_method('PUT');
        
        try {
            if (empty($id)) {
                $this->api->respond_error('User ID is required', 400);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->api->respond_error('Invalid JSON data', 400);
                return;
            }
            
            // Use ORM model to update user (includes validation)
            $this->User->updateUser($id, $input);
            
            // Fetch and return the updated user
            $user = $this->User->getUserById($id);
            $user = is_object($user) ? (array)$user : $user;
            
            $this->api->respond($user);
            
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
     * Delete user (soft delete)
     * DELETE /api/users/{id}
     */
    public function delete($id) {
        $this->api->require_method('DELETE');
        
        try {
            if (empty($id)) {
                $this->api->respond_error('User ID is required', 400);
                return;
            }
            
            // Use ORM model to soft delete user
            $this->User->deleteUser($id);
            
            $this->api->respond(['message' => 'User deleted successfully']);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false) {
                $this->api->respond_error($e->getMessage(), 404);
            } else {
                $this->api->respond_error($e->getMessage(), 500);
            }
        }
    }
    
    /**
     * Authenticate user
     * POST /api/users/login
     */
    public function login() {
        $this->api->require_method('POST');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['email']) || empty($input['password'])) {
                $this->api->respond_error('Email and password are required', 400);
                return;
            }
            
            // Use ORM model to authenticate
            $user = $this->User->authenticateUser($input['email'], $input['password']);
            
            if (!$user) {
                $this->api->respond_error('Invalid email or password', 401);
                return;
            }
            
            // Convert object to array if needed
            $user = is_object($user) ? (array)$user : $user;
            
            $this->api->respond(['user' => $user, 'message' => 'Login successful']);
            
        } catch (Exception $e) {
            // Handle specific error messages (like account not active)
            if (strpos($e->getMessage(), 'not active') !== false) {
                $this->api->respond_error($e->getMessage(), 403);
            } else {
                $this->api->respond_error($e->getMessage(), 500);
            }
        }
    }
    
    /**
     * Register a new user (customer only)
     * POST /api/users/register
     */
    public function register() {
        $this->api->require_method('POST');
        
        try {
            $input = $this->api->get_input();
            
            // Force role to 'user' for registration
            $input['role'] = 'user';
            $input['status'] = 'active';
            
            // Use ORM model to create user (includes validation)
            $this->User->createUser($input);
            $userId = $this->db->last_id();
            
            // Get the created user (without password)
            $user = $this->User->getUserById($userId);
            $user = is_object($user) ? (array)$user : $user;
            
            // Remove password from output (just in case)
            if (isset($user['password'])) {
                unset($user['password']);
            }
            
            $this->api->respond([
                'message' => 'User registered successfully',
                'user' => $user
            ], 201);
            
        } catch (Exception $e) {
            // Model throws exceptions with validation errors
            $this->api->respond_error($e->getMessage(), 400);
        }
    }
}
?>
