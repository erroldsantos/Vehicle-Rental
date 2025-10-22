<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class User extends Model {
    
    protected $table = 'users';
    protected $primaryKey = 'id';
    
    /**
     * Get all users with optional filtering
     */
    public function getAllUsers($filters = []) {
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
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $query = "SELECT id, first_name, last_name, email, phone, role, status FROM users WHERE id = ? AND deleted_at IS NULL";
        $result = $this->db->query($query, [$id]);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        $query = "SELECT id, first_name, last_name, email, phone, password, role, status FROM users WHERE email = ? AND deleted_at IS NULL";
        $result = $this->db->query($query, [$email]);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Create new user
     */
    public function createUser($data) {
        // Validate required fields
        $required = ['first_name', 'last_name', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Check if email already exists
        if ($this->getUserByEmail($data['email'])) {
            throw new Exception("Email already exists");
        }
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Set defaults
        $data['role'] = $data['role'] ?? 'user';
        $data['status'] = $data['status'] ?? 'active';
        
        $query = "INSERT INTO users (first_name, last_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $data['first_name'],
            $data['last_name'], 
            $data['email'],
            $data['phone'] ?? null,
            $data['password'],
            $data['role'],
            $data['status']
        ];
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Update user
     */
    public function updateUser($id, $data) {
        // Check if user exists
        $user = $this->getUserById($id);
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Validate email if provided
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }
            
            // Check if email is taken by another user
            $existingUser = $this->getUserByEmail($data['email']);
            if ($existingUser && $existingUser['id'] != $id) {
                throw new Exception("Email already exists");
            }
        }
        
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'role', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        // Handle password update separately
        if (!empty($data['password'])) {
            $updateFields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updateFields)) {
            throw new Exception("No valid fields to update");
        }
        
        $params[] = $id;
        $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Soft delete user
     */
    public function deleteUser($id) {
        // Check if user exists
        $user = $this->getUserById($id);
        if (!$user) {
            throw new Exception("User not found");
        }
        
        $query = "UPDATE users SET deleted_at = NOW() WHERE id = ?";
        return $this->db->query($query, [$id]);
    }
    
    /**
     * Authenticate user
     */
    public function authenticateUser($email, $password) {
        $user = $this->getUserByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        if ($user['status'] !== 'active') {
            throw new Exception("Account is not active");
        }
        
        if (password_verify($password, $user['password'])) {
            // Remove password from returned data
            unset($user['password']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats() {
        $stats = [];
        
        // Total users
        $result = $this->db->query("SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL");
        $stats['total_users'] = $result[0]['count'];
        
        // Active users
        $result = $this->db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active' AND deleted_at IS NULL");
        $stats['active_users'] = $result[0]['count'];
        
        // Inactive users
        $result = $this->db->query("SELECT COUNT(*) as count FROM users WHERE status = 'inactive' AND deleted_at IS NULL");
        $stats['inactive_users'] = $result[0]['count'];
        
        // Suspended users
        $result = $this->db->query("SELECT COUNT(*) as count FROM users WHERE status = 'suspended' AND deleted_at IS NULL");
        $stats['suspended_users'] = $result[0]['count'];
        
        // Admins
        $result = $this->db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND deleted_at IS NULL");
        $stats['admin_users'] = $result[0]['count'];
        
        // Regular users
        $result = $this->db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND deleted_at IS NULL");
        $stats['regular_users'] = $result[0]['count'];
        
        return $stats;
    }
    
    /**
     * Validate user data
     */
    public function validateUserData($data, $isUpdate = false) {
        $errors = [];
        
        if (!$isUpdate) {
            // Required fields for creation
            $required = ['first_name', 'last_name', 'email', 'password'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $errors[] = "Field '$field' is required";
                }
            }
        }
        
        // Validate email format
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        // Validate role
        if (!empty($data['role']) && !in_array($data['role'], ['admin', 'user'])) {
            $errors[] = "Invalid role. Must be 'admin' or 'user'";
        }
        
        // Validate status
        if (!empty($data['status']) && !in_array($data['status'], ['active', 'inactive', 'suspended'])) {
            $errors[] = "Invalid status. Must be 'active', 'inactive', or 'suspended'";
        }
        
        // Validate password strength
        if (!empty($data['password']) && strlen($data['password']) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        }
        
        // Validate phone number format (optional)
        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s()]+$/', $data['phone'])) {
            $errors[] = "Invalid phone number format";
        }
        
        return $errors;
    }
}
?>