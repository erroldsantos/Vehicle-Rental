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
    
    /**
     * Upload driver's license
     * POST /api/users/{id}/license/upload
     */
    public function uploadLicense($id) {
        $this->api->require_method('POST');
        
        try {
            if (empty($id)) {
                $this->api->respond_error('User ID is required', 400);
                return;
            }
            
            // Check if file was uploaded
            if (!isset($_FILES['license_image']) || $_FILES['license_image']['error'] === UPLOAD_ERR_NO_FILE) {
                $this->api->respond_error('License image is required', 400);
                return;
            }
            
            $file = $_FILES['license_image'];
            
            // Validate file upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
                ];
                $errorMsg = isset($errorMessages[$file['error']]) ? $errorMessages[$file['error']] : 'Unknown upload error';
                $this->api->respond_error('File upload error: ' . $errorMsg, 400);
                return;
            }
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $this->api->respond_error('Invalid file type. Only JPG, PNG, and GIF are allowed. Detected: ' . $mimeType, 400);
                return;
            }
            
            // Validate file size (max 5MB)
            $maxSize = 5 * 1024 * 1024; // 5MB in bytes
            if ($file['size'] > $maxSize) {
                $this->api->respond_error('File size exceeds maximum limit of 5MB', 400);
                return;
            }
            
            // Prepare upload directory
            $uploadDir = 'public/images/licenses/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'license_' . $id . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $this->api->respond_error('Failed to save uploaded file', 500);
                return;
            }
            
            // Update user record
            $this->User->submitLicense($id, $uploadPath);
            
            // Get updated user
            $user = $this->User->getUserById($id);
            
            // Remove sensitive data
            if (isset($user['password'])) {
                unset($user['password']);
            }
            
            $this->api->respond([
                'message' => 'License uploaded successfully and is pending verification',
                'user' => $user
            ]);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Get user's license status
     * GET /api/users/{id}/license/status
     */
    public function getLicenseStatus($id) {
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
            
            $response = [
                'license_status' => $user['license_status'] ?? 'not_submitted',
                'license_image' => $user['license_image'] ?? null,
                'license_submitted_at' => $user['license_submitted_at'] ?? null,
                'license_verified_at' => $user['license_verified_at'] ?? null,
                'license_rejection_reason' => $user['license_rejection_reason'] ?? null
            ];
            
            $this->api->respond($response);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
}
?>
