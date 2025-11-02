<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once APP_DIR . 'controllers/ApiController.php';

/**
 * AuthController - User Authentication & Authorization
 * Uses LavaLust's Lauth library and Session management
 * Based on: https://lavalust.netlify.app/example/auth
 */
class AuthController extends ApiController {
    
    public function __construct() {
        parent::__construct();
        $this->call->library('lauth');
        $this->call->library('session');
    }
    
    /**
     * Login endpoint
     * POST /api/auth/login
     */
    public function login() {
        $this->api->require_method('POST');
        
        try {
            $input = $this->api->body();
            
            // Validate required fields
            if (empty($input['email']) || empty($input['password'])) {
                $this->api->respond_error('Email and password are required', 400);
                return;
            }
            
            $email = $input['email'];
            $password = $input['password'];
            
            // Attempt login using Lauth library
            $user = $this->lauth->login($email, $password);
            
            if ($user) {
                // Generate token for API authentication
                $token = $this->lauth->generate_token($user);
                
                // Construct full name from first_name and last_name
                $fullname = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                
                $response = [
                    'success' => true,
                    'message' => 'Login successful',
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'name' => $fullname,
                        'role' => $user['role'],
                        'permissions' => $user['role'] === 'admin' ? ['all'] : ['read']
                    ]
                ];
                
                $this->api->respond($response);
            } else {
                $this->api->respond_error('Invalid email or password', 401);
            }
            
        } catch (Exception $e) {
            $this->api->respond_error('Login failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Register endpoint
     * POST /api/auth/register
     */
    public function register() {
        $this->api->require_method('POST');
        
        try {
            $input = $this->api->body();
            
            // Validate required fields
            $required = ['email', 'password', 'fullname'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    $this->api->respond_error("Field '{$field}' is required", 400);
                    return;
                }
            }
            
            // Validate email format
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                $this->api->respond_error('Invalid email format', 400);
                return;
            }
            
            $success = $this->lauth->register([
                'email' => $input['email'],
                'password' => $input['password'],
                'fullname' => $input['fullname'],
                'phone' => $input['phone'] ?? '',
                'role' => $input['role'] ?? 'user'
            ]);
            
            if ($success) {
                $this->api->respond([
                    'success' => true,
                    'message' => 'Registration successful. You can now login.'
                ], 201);
            } else {
                $this->api->respond_error('Registration failed. Email may already exist.', 400);
            }
            
        } catch (Exception $e) {
            $this->api->respond_error('Registration failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Logout endpoint
     * POST /api/auth/logout
     */
    public function logout() {
        $this->api->require_method('POST');
        
        try {
            $this->lauth->logout();
            
            $this->api->respond([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
            
        } catch (Exception $e) {
            $this->api->respond_error('Logout failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Check authentication status
     * GET /api/auth/me
     */
    public function me() {
        $this->api->require_method('GET');
        
        try {
            // Check if user is logged in via session
            if ($this->lauth->is_logged_in()) {
                $user = $this->lauth->user();
                
                $this->api->respond([
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'name' => $user['fullname'],
                        'role' => $user['role'],
                        'permissions' => $user['role'] === 'admin' ? ['all'] : ['read']
                    ]
                ]);
            } else {
                // Check for Bearer token in headers
                $headers = getallheaders();
                $token = null;
                
                if (isset($headers['Authorization'])) {
                    $auth = $headers['Authorization'];
                    if (strpos($auth, 'Bearer ') === 0) {
                        $token = substr($auth, 7);
                    }
                }
                
                if ($token && $this->lauth->validate_token($token)) {
                    $user = $this->lauth->user();
                    
                    $this->api->respond([
                        'success' => true,
                        'user' => [
                            'id' => $user['id'],
                            'email' => $user['email'],
                            'name' => $user['fullname'],
                            'role' => $user['role'],
                            'permissions' => $user['role'] === 'admin' ? ['all'] : ['read']
                        ]
                    ]);
                } else {
                    $this->api->respond_error('Not authenticated', 401);
                }
            }
            
        } catch (Exception $e) {
            $this->api->respond_error('Authentication check failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Change password
     * POST /api/auth/change-password
     */
    public function changePassword() {
        $this->api->require_method('POST');
        
        try {
            // Require authentication
            if (!$this->lauth->is_logged_in()) {
                $this->api->respond_error('Not authenticated', 401);
                return;
            }
            
            $input = $this->api->body();
            
            // Validate required fields
            if (empty($input['current_password']) || empty($input['new_password'])) {
                $this->api->respond_error('Current password and new password are required', 400);
                return;
            }
            
            $user_id = $this->lauth->user_id();
            
            // Verify current password
            if (!$this->lauth->verify_password($user_id, $input['current_password'])) {
                $this->api->respond_error('Current password is incorrect', 400);
                return;
            }
            
            // Update password
            if ($this->lauth->update_password($user_id, $input['new_password'])) {
                $this->api->respond([
                    'success' => true,
                    'message' => 'Password changed successfully'
                ]);
            } else {
                $this->api->respond_error('Failed to update password', 500);
            }
            
        } catch (Exception $e) {
            $this->api->respond_error('Password change failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Password reset request
     * POST /api/auth/forgot-password
     */
    public function forgotPassword() {
        $this->api->require_method('POST');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['email'])) {
                $this->api->respond_error('Email is required', 400);
                return;
            }
            
            // For demo purposes, just return success
            $this->api->respond([
                'success' => true,
                'message' => 'Password reset instructions have been sent to your email'
            ]);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
}
?>