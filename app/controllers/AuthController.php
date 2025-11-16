<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AuthController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->call->library('api');
        $this->call->library('lauth');
        $this->call->library('session');
        $this->call->helper('mail');
        $this->call->model('User');
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
            
            // Get user by email
            $user = $this->User->getUserByEmail($email);
            
            // Check if user exists
            if (!$user) {
                $this->api->respond_error('Invalid email or password', 401);
                return;
            }
            
            // Check if email is verified
            if (isset($user['email_verified']) && $user['email_verified'] == 0) {
                $this->api->respond_error('Please verify your email address before logging in. Check your inbox for the verification link.', 403);
                return;
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                $this->api->respond_error('Invalid email or password', 401);
                return;
            }
            
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
            // Handle specific error messages from Lauth
            $errorMsg = $e->getMessage();
            $statusCode = 500;
            
            if (strpos($errorMsg, 'verify your email') !== false) {
                $statusCode = 403;
            } elseif (strpos($errorMsg, 'Invalid') !== false || strpos($errorMsg, 'password') !== false) {
                $statusCode = 401;
            }
            
            $this->api->respond_error($errorMsg, $statusCode);
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
            
            // Split fullname into first and last name
            $nameParts = explode(' ', trim($input['fullname']), 2);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
            
            // Create user with email verification
            $userId = $this->User->createUser([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $input['email'],
                'password' => $input['password'],
                'phone' => $input['phone'] ?? '',
                'role' => $input['role'] ?? 'user'
            ]);
            
            if ($userId) {
                // Get the created user to retrieve verification token
                $user = $this->User->getUserByEmail($input['email']);
                
                if (!$user || empty($user['verification_token'])) {
                    error_log("Registration: User created but token not found for " . $input['email']);
                    $this->api->respond([
                        'success' => true,
                        'message' => 'Registration successful but verification email could not be sent. Please contact support.',
                        'debug' => 'User created but token not found',
                        'user_data' => $user ? 'User found, token: ' . (empty($user['verification_token']) ? 'EMPTY' : 'EXISTS') : 'User not found'
                    ], 201);
                    return;
                }
                
                // Log email sending attempt
                error_log("Attempting to send verification email to: " . $input['email'] . " with token: " . substr($user['verification_token'], 0, 10) . "...");
                
                // Send verification email
                $emailSent = sendVerificationEmail(
                    $input['email'],
                    $input['fullname'],
                    $user['verification_token']
                );
                
                error_log("Email sent result: " . ($emailSent ? 'SUCCESS' : 'FAILED'));
                
                if ($emailSent) {
                    $this->api->respond([
                        'success' => true,
                        'message' => 'Registration successful. Please check your email to verify your account.'
                    ], 201);
                } else {
                    $this->api->respond([
                        'success' => true,
                        'message' => 'Registration successful but verification email could not be sent. Please contact support.',
                        'warning' => 'Email delivery failed'
                    ], 201);
                }
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
                        // Get request body
            $input = $this->api->body();
            
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
    
    /**
     * Verify email address
     * GET /api/auth/verify-email?token=xxx
     */
    public function verifyEmail() {
        $this->api->require_method('GET');
        
        try {
            $token = $_GET['token'] ?? '';
            
            if (empty($token)) {
                // Redirect to Vue with error
                $redirectUrl = 'http://localhost:5173/verify-email?status=error&message=' . urlencode('Verification token is required');
                header("Location: $redirectUrl");
                exit;
            }
            
            // Verify the email
            $verified = $this->User->verifyEmail($token);
            
            if ($verified) {
                // Redirect to Vue with success
                $redirectUrl = 'http://localhost:5173/verify-email?status=success&message=' . urlencode('Your email has been successfully verified!');
                header("Location: $redirectUrl");
                exit;
            } else {
                // Redirect to Vue with error
                $redirectUrl = 'http://localhost:5173/verify-email?status=error&message=' . urlencode('Email verification failed');
                header("Location: $redirectUrl");
                exit;
            }
            
        } catch (Exception $e) {
            // Check if already verified
            if (strpos($e->getMessage(), 'already verified') !== false) {
                $redirectUrl = 'http://localhost:5173/verify-email?status=already-verified&message=' . urlencode('Your email is already verified. You can login now.');
            } else {
                $redirectUrl = 'http://localhost:5173/verify-email?status=error&message=' . urlencode($e->getMessage());
            }
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Resend verification email
     * POST /api/auth/resend-verification
     */
    public function resendVerification() {
        $this->api->require_method('POST');
        
        try {
            $input = $this->api->body();
            
            if (empty($input['email'])) {
                $this->api->respond_error('Email is required', 400);
                return;
            }
            
            // Get user by email
            $user = $this->User->getUserByEmail($input['email']);
            
            if (!$user) {
                // Don't reveal if email exists or not for security
                $this->api->respond([
                    'success' => true,
                    'message' => 'If the email exists, a verification link has been sent.'
                ]);
                return;
            }
            
            // Resend verification token
            $newToken = $this->User->resendVerificationToken($input['email']);
            
            if ($newToken) {
                // Send new verification email
                $fullname = trim($user['first_name'] . ' ' . $user['last_name']);
                $emailSent = sendVerificationEmail($input['email'], $fullname, $newToken);
                
                if ($emailSent) {
                    $this->api->respond([
                        'success' => true,
                        'message' => 'Verification email has been resent. Please check your inbox.'
                    ]);
                } else {
                    $this->api->respond_error('Failed to send verification email', 500);
                }
            } else {
                $this->api->respond_error('Failed to generate verification token', 500);
            }
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 400);
        }
    }
}
?>