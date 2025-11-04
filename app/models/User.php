<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class User extends Model {
    
    protected $table = 'users';
    protected $primary_key = 'id';
    protected $soft_delete = true; // Enable soft deletes
    
    /**
     * Get all users with optional filtering
     */
    public function getAllUsers($filters = []) {

        if (!empty($filters['search'])) {
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
            
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            
            $query .= " ORDER BY first_name, last_name";
            
            $stmt = $this->db->raw($query, $params);
            return $stmt->fetchAll();
        }
        
        $users = $this->all(); // Gets all non-deleted users
        
        // Apply filters if needed
        if (!empty($filters['status']) || !empty($filters['role'])) {
            $users = array_filter($users, function($user) use ($filters) {
                if (!empty($filters['status']) && $user->status !== $filters['status']) {
                    return false;
                }
                if (!empty($filters['role']) && $user->role !== $filters['role']) {
                    return false;
                }
                return true;
            });
        }
        
        return array_values($users); // Re-index array
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($id) {
        return $this->find($id); 
    }
    
    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        $stmt = $this->db->raw("SELECT id, first_name, last_name, email, phone, password, role, status FROM users WHERE email = ? AND deleted_at IS NULL", [$email]);
        $result = $stmt->fetchAll();
        return !empty($result) ? $result[0] : null;
    }
    
    public function createUser($data) {
        // Validate required fields
        $required = ['first_name', 'last_name', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        if ($this->getUserByEmail($data['email'])) {
            throw new Exception("Email already exists");
        }
        
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $data['role'] = $data['role'] ?? 'user';
        $data['status'] = $data['status'] ?? 'active';
        
        return $this->insert([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'], 
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => $data['role'],
            'status' => $data['status']
        ]);
    }
    
    /**
     * Update user
     */
    public function updateUser($id, $data) {
        $user = $this->getUserById($id);
        if (!$user) {
            throw new Exception("User not found");
        }
        
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
        
        $updateData = [];
        $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'role', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        // Handle password update separately
        if (!empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updateData)) {
            throw new Exception("No valid fields to update");
        }
        
        // Use ORM update method
        return $this->update($id, $updateData);
    }
    
    /**
     * Soft delete user using ORM
     */
    public function deleteUser($id) {
        // Check if user exists
        $user = $this->getUserById($id);
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Use ORM soft delete method
        return $this->soft_delete($id);
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
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['total_users'] = $result['count'];
        
        // Active users
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM users WHERE status = 'active' AND deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['active_users'] = $result['count'];
        
        // Inactive users
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM users WHERE status = 'inactive' AND deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['inactive_users'] = $result['count'];
        
        // Suspended users
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM users WHERE status = 'suspended' AND deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['suspended_users'] = $result['count'];
        
        // Admins
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['admin_users'] = $result['count'];
        
        // Regular users
        $stmt = $this->db->raw("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND deleted_at IS NULL");
        $result = $stmt->fetch();
        $stats['regular_users'] = $result['count'];
        
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