<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AuthController extends Controller {
    
    private $pdo;
    
    public function __construct() {
        parent::__construct();
        $this->call->library('api');
        
        // Initialize database connection
        try {
            $this->pdo = new PDO('mysql:host=localhost;dbname=vehicle_rental', 'root', '');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->api->respond_error('Database connection failed: ' . $e->getMessage(), 500);
            exit();
        }
    }
    
    /**
     * Login endpoint
     * POST /api/auth/login
     */
    public function login() {
        $this->api->require_method('POST');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->api->respond_error('Invalid JSON data', 400);
                return;
            }
            
            // Validate required fields
            if (empty($input['email']) || empty($input['password'])) {
                $this->api->respond_error('Email and password are required', 400);
                return;
            }
            
            $email = $input['email'];
            $password = $input['password'];
            $remember = $input['remember'] ?? false;
            
            // Check database for user first (prioritize database authentication)
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $passwordValid = false;
                    
                    // Check if password is hashed (starts with $2y$) or plain text
                    if (strpos($user['password'], '$2y$') === 0) {
                        // Password is hashed, use password_verify
                        $passwordValid = password_verify($password, $user['password']);
                    } else {
                        // Password is plain text (for backward compatibility)
                        $passwordValid = ($password === $user['password']);
                    }
                    
                    if ($passwordValid) {
                        // Generate token
                        $token = base64_encode($user['email'] . ':' . time() . ':' . rand(1000, 9999));
                        
                        $response = [
                            'success' => true,
                            'message' => 'Login successful',
                            'token' => $token,
                            'user' => [
                                'id' => $user['id'],
                                'email' => $user['email'],
                                'name' => $user['first_name'] . ' ' . $user['last_name'],
                                'role' => $user['role'] ?? 'user',
                                'permissions' => $user['role'] === 'admin' ? ['all'] : ['read']
                            ]
                        ];
                        
                        $this->api->respond($response);
                        return;
                    }
                }
            } catch (PDOException $e) {
                // Log the error but continue to demo accounts
                error_log("Database query error: " . $e->getMessage());
            }
            
            // Fallback to demo accounts if database authentication fails
            $demoAccounts = [
                'admin@vehiclerental.com' => [
                    'password' => 'admin123',
                    'name' => 'Demo Admin',
                    'id' => 999,
                    'role' => 'admin'
                ]
            ];
            
            if (isset($demoAccounts[$email]) && $demoAccounts[$email]['password'] === $password) {
                $account = $demoAccounts[$email];
                
                $token = base64_encode($email . ':' . time() . ':' . rand(1000, 9999));
                
                $response = [
                    'success' => true,
                    'message' => 'Login successful (Demo Account)',
                    'token' => $token,
                    'user' => [
                        'id' => $account['id'],
                        'email' => $email,
                        'name' => $account['name'],
                        'role' => $account['role'],
                        'permissions' => $account['role'] === 'admin' ? ['all'] : ['read']
                    ]
                ];
                
                $this->api->respond($response);
                return;
            }
            
            // If we reach here, no valid credentials found
            $this->api->respond_error('Invalid email or password', 401);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Logout endpoint
     * POST /api/auth/logout
     */
    public function logout() {
        $this->api->require_method('POST');
        
        try {
            // In a real application, you'd invalidate the token here
            // For now, just return success
            
            $this->api->respond([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Check authentication status
     * GET /api/auth/me
     */
    public function me() {
        $this->api->require_method('GET');
        
        try {
            // Get authorization header
            $headers = getallheaders();
            $token = null;
            
            if (isset($headers['Authorization'])) {
                $auth = $headers['Authorization'];
                if (strpos($auth, 'Bearer ') === 0) {
                    $token = substr($auth, 7);
                }
            }
            
            if (!$token) {
                $this->api->respond_error('No token provided', 401);
                return;
            }
            
            // For demo purposes, accept any valid-looking token
            if ($token === 'demo_token' || strpos($token, ':') !== false) {
                $this->api->respond([
                    'success' => true,
                    'user' => [
                        'id' => 1,
                        'email' => 'admin@vehiclerental.com',
                        'name' => 'Admin User',
                        'role' => 'admin',
                        'permissions' => ['all']
                    ]
                ]);
            } else {
                $this->api->respond_error('Invalid token', 401);
            }
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
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