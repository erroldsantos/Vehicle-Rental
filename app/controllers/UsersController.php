<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class UsersController extends Controller {
    
    private $pdo;
    
    public function __construct() {
        parent::__construct();
        $this->call->library('api');
        
        // Initialize database connection (using direct PDO like VehiclesController)
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
            
            $query = "SELECT id, first_name, last_name, email, phone, role, status FROM users WHERE deleted_at IS NULL";
            $params = [];
            
            if (!empty($filters['status'])) {
                $query .= " AND status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['role'])) {
                $query .= " AND role = ?";
                $params[] = $filters['role'];
            }
            
            if (!empty($filters['search'])) {
                $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }
            
            $query .= " ORDER BY first_name, last_name";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $users = $stmt->fetchAll();
            
            // Get statistics
            $stats = $this->getUserStats();
            
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
            
            $stmt = $this->pdo->prepare("SELECT id, first_name, last_name, email, phone, role, status FROM users WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
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
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->api->respond_error('Invalid JSON data', 400);
                return;
            }
            
            // Validate required fields
            $required = ['first_name', 'last_name', 'email', 'password'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    $this->api->respond_error("Field '$field' is required", 400);
                    return;
                }
            }
            
            // Validate email format
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                $this->api->respond_error('Invalid email format', 400);
                return;
            }
            
            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL");
            $stmt->execute([$input['email']]);
            if ($stmt->fetch()) {
                $this->api->respond_error('Email already exists', 400);
                return;
            }
            
            // Validate password length
            if (strlen($input['password']) < 6) {
                $this->api->respond_error('Password must be at least 6 characters long', 400);
                return;
            }
            
            // Hash password
            $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
            
            // Set defaults
            $role = $input['role'] ?? 'user';
            $status = $input['status'] ?? 'active';
            $phone = $input['phone'] ?? null;
            
            // Validate role and status
            if (!in_array($role, ['admin', 'user'])) {
                $this->api->respond_error('Invalid role', 400);
                return;
            }
            
            if (!in_array($status, ['active', 'inactive', 'suspended'])) {
                $this->api->respond_error('Invalid status', 400);
                return;
            }
            
            // Insert user
            $stmt = $this->pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['first_name'],
                $input['last_name'],
                $input['email'],
                $phone,
                $hashedPassword,
                $role,
                $status
            ]);
            
            $userId = $this->pdo->lastInsertId();
            
            // Fetch and return the created user
            $stmt = $this->pdo->prepare("SELECT id, first_name, last_name, email, phone, role, status FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            $this->api->respond($user, 201);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
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
            
            // Check if user exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                $this->api->respond_error('User not found', 404);
                return;
            }
            
            // Validate email if provided
            if (!empty($input['email'])) {
                if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                    $this->api->respond_error('Invalid email format', 400);
                    return;
                }
                
                // Check if email is taken by another user
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL");
                $stmt->execute([$input['email'], $id]);
                if ($stmt->fetch()) {
                    $this->api->respond_error('Email already exists', 400);
                    return;
                }
            }
            
            // Validate role and status if provided
            if (!empty($input['role']) && !in_array($input['role'], ['admin', 'user'])) {
                $this->api->respond_error('Invalid role', 400);
                return;
            }
            
            if (!empty($input['status']) && !in_array($input['status'], ['active', 'inactive', 'suspended'])) {
                $this->api->respond_error('Invalid status', 400);
                return;
            }
            
            // Build update query
            $updateFields = [];
            $params = [];
            
            $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'role', 'status'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            // Handle password update separately
            if (!empty($input['password'])) {
                if (strlen($input['password']) < 6) {
                    $this->api->respond_error('Password must be at least 6 characters long', 400);
                    return;
                }
                $updateFields[] = "password = ?";
                $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($updateFields)) {
                $this->api->respond_error('No valid fields to update', 400);
                return;
            }
            
            $params[] = $id;
            $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            // Fetch and return the updated user
            $stmt = $this->pdo->prepare("SELECT id, first_name, last_name, email, phone, role, status FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
            $this->api->respond($user);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
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
            
            // Check if user exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                $this->api->respond_error('User not found', 404);
                return;
            }
            
            // Soft delete
            $stmt = $this->pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->api->respond(['message' => 'User deleted successfully']);
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
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
            
            // Get user by email
            $stmt = $this->pdo->prepare("SELECT id, first_name, last_name, email, phone, password, role, status FROM users WHERE email = ? AND deleted_at IS NULL");
            $stmt->execute([$input['email']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->api->respond_error('Invalid email or password', 401);
                return;
            }
            
            if ($user['status'] !== 'active') {
                $this->api->respond_error('Account is not active', 403);
                return;
            }
            
            if (password_verify($input['password'], $user['password'])) {
                // Remove password from returned data
                unset($user['password']);
                $this->api->respond(['user' => $user, 'message' => 'Login successful']);
            } else {
                $this->api->respond_error('Invalid email or password', 401);
            }
            
        } catch (Exception $e) {
            $this->api->respond_error($e->getMessage(), 500);
        }
    }
    
    /**
     * Get user statistics
     */
    private function getUserStats() {
        $stats = [];
        
        // Total users
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL");
        $stats['total_users'] = $stmt->fetchColumn();
        
        // Active users
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'active' AND deleted_at IS NULL");
        $stats['active_users'] = $stmt->fetchColumn();
        
        // Inactive users
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'inactive' AND deleted_at IS NULL");
        $stats['inactive_users'] = $stmt->fetchColumn();
        
        // Suspended users
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'suspended' AND deleted_at IS NULL");
        $stats['suspended_users'] = $stmt->fetchColumn();
        
        // Admins
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND deleted_at IS NULL");
        $stats['admin_users'] = $stmt->fetchColumn();
        
        // Regular users
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND deleted_at IS NULL");
        $stats['regular_users'] = $stmt->fetchColumn();
        
        return $stats;
    }
}
?>