<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * LavaLust Authentication Library
 * Based on LavaLust documentation: https://lavalust.netlify.app/example/auth
 */
class Lauth
{
    protected $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->database();
        $this->_lava->call->library('session');
    }

    /**
     * Register a new user
     *
     * @param array $data User data (email, password, first_name, last_name, phone, role)
     * @return bool
     */
    public function register($data)
    {
        // Validate required fields
        if (empty($data['email']) || empty($data['password'])) {
            return false;
        }

        // Check if email already exists using raw SQL
        $query = "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->_lava->db->raw($query, [$data['email']]);
        $existing = $stmt->fetchAll();
        
        if (!empty($existing)) {
            return false; // Email already exists
        }

        // Hash the password
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Prepare user data to match actual schema
        $userData = [
            'email' => $data['email'],
            'password' => $hash,
            'first_name' => $data['first_name'] ?? $data['fullname'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'phone' => $data['phone'] ?? '',
            'role' => $data['role'] ?? 'user',
            'status' => 'active'
        ];

        return $this->_lava->db->table('users')->insert($userData);
    }

    /**
     * Login user
     *
     * @param string $email
     * @param string $password
     * @return bool|array Returns user data on success, false on failure
     */
    public function login($email, $password)
    {
        // Use raw SQL to avoid Query Builder reliability issues
        $query = "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->_lava->db->raw($query, [$email]);
        $users = $stmt->fetchAll();
        $user = !empty($users) ? $users[0] : null;

        if ($user && password_verify($password, $user['password'])) {
            // Set session data
            $fullname = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $this->_lava->session->set_userdata([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'] ?? '',
                'last_name' => $user['last_name'] ?? '',
                'fullname' => $fullname,
                'role' => $user['role'],
                'logged_in' => true
            ]);
            
            return $user;
        }

        return false;
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public function is_logged_in()
    {
        return (bool) $this->_lava->session->userdata('logged_in');
    }

    /**
     * Get current logged in user data
     *
     * @return array|null
     */
    public function user()
    {
        if (!$this->is_logged_in()) {
            return null;
        }

        return [
            'id' => $this->_lava->session->userdata('user_id'),
            'email' => $this->_lava->session->userdata('email'),
            'fullname' => $this->_lava->session->userdata('fullname'),
            'role' => $this->_lava->session->userdata('role')
        ];
    }

    /**
     * Get current user ID
     *
     * @return int|null
     */
    public function user_id()
    {
        return $this->_lava->session->userdata('user_id');
    }

    /**
     * Check user role
     *
     * @param string $role
     * @return bool
     */
    public function has_role($role)
    {
        return $this->_lava->session->userdata('role') === $role;
    }

    /**
     * Check if user is admin
     *
     * @return bool
     */
    public function is_admin()
    {
        return $this->has_role('admin');
    }

    /**
     * Logout user
     *
     * @return void
     */
    public function logout()
    {
        $this->_lava->session->unset_userdata([
            'user_id',
            'email',
            'fullname',
            'role',
            'logged_in'
        ]);
        $this->_lava->session->sess_destroy();
    }

    /**
     * Generate authentication token (for API)
     *
     * @param array $user
     * @return string
     */
    public function generate_token($user)
    {
        $token = base64_encode($user['email'] . ':' . time() . ':' . bin2hex(random_bytes(16)));
        
        // Store token in session for validation
        $this->_lava->session->set_userdata('auth_token', $token);
        
        return $token;
    }

    /**
     * Validate authentication token
     *
     * @param string $token
     * @return bool
     */
    public function validate_token($token)
    {
        $stored_token = $this->_lava->session->userdata('auth_token');
        return $stored_token && $stored_token === $token;
    }

    /**
     * Require authentication (redirect if not logged in)
     *
     * @param string $redirect_to URL to redirect to if not logged in
     * @return void
     */
    public function require_login($redirect_to = 'auth/login')
    {
        if (!$this->is_logged_in()) {
            redirect($redirect_to);
            exit;
        }
    }

    /**
     * Require specific role (return error if not authorized)
     *
     * @param string $role Required role
     * @return bool
     */
    public function require_role($role)
    {
        if (!$this->has_role($role)) {
            return false;
        }
        return true;
    }

    /**
     * Update user password
     *
     * @param int $user_id
     * @param string $new_password
     * @return bool
     */
    public function update_password($user_id, $new_password)
    {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        return $this->_lava->db->table('users')
                    ->where('id', $user_id)
                    ->update(['password' => $hash]);
    }

    /**
     * Verify current password
     *
     * @param int $user_id
     * @param string $password
     * @return bool
     */
    public function verify_password($user_id, $password)
    {
        $query = "SELECT password FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->_lava->db->raw($query, [$user_id]);
        $users = $stmt->fetchAll();
        $user = !empty($users) ? $users[0] : null;

        if ($user) {
            return password_verify($password, $user['password']);
        }

        return false;
    }
}
?>
