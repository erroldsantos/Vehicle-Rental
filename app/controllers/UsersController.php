<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class UsersController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->call->library('api');
        $this->call->model('User');
    }
    
    /**
     * Get all users
     * GET /api/users
     */
    public function index() {
        $this->api->require_method('GET');
        
        try {
            // Get all GET parameters if any exist
            $getAllParams = !empty($_GET) ? $this->io->get() : [];
            $filters = [
                'status' => isset($getAllParams['status']) ? $getAllParams['status'] : null,
                'role' => isset($getAllParams['role']) ? $getAllParams['role'] : null,
                'search' => isset($getAllParams['search']) ? $getAllParams['search'] : null
            ];

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
            
            $user = $this->User->getUserById($id);
            
            if (!$user) {
                $this->api->respond_error('User not found', 404);
                return;
            }
            
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
            $input = $this->api->body();
            
            //create user (includes validation)
            $this->User->createUser($input);
            $userId = $this->db->last_id();
            
            // Fetch and return the created user
            $user = $this->User->getUserById($userId);
            $user = is_object($user) ? (array)$user : $user;
            
            $this->api->respond($user, 201);
            
        } catch (Exception $e) {
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
            
            $input = $this->api->body();
            
            // Update user (includes validation)
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
            
            // Soft delete user
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
            $input = $this->api->body();
            
            if (!$input || empty($input['email']) || empty($input['password'])) {
                $this->api->respond_error('Email and password are required', 400);
                return;
            }
            
            // authenticate
            $user = $this->User->authenticateUser($input['email'], $input['password']);
            
            if (!$user) {
                $this->api->respond_error('Invalid email or password', 401);
                return;
            }
            
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
            $input = $this->api->body();
            
            // Force role to 'user' for registration
            $input['role'] = 'user';
            $input['status'] = 'active';

            // Create user (includes validation)
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
