<?php
require_once __DIR__ . '/../init.php';

class AuthController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function register($data) {
        try {
            // Validate input
            if (empty($data['username']) || empty($data['password']) || empty($data['full_name'])) {
                return ['success' => false, 'message' => 'All fields are required'];
            }
            
            // Check if username exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$data['username']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username already exists'];
            }
            
            // Hash password
            $hashed_password = hash_password($data['password']);
            
            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password, full_name, email, phone, role, shop_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $data['username'],
                $hashed_password,
                $data['full_name'],
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['role'] ?? 'staff',
                $data['shop_id'] ?? null
            ]);
            
            $user_id = $this->pdo->lastInsertId();
            
            // Log activity
            $this->log_activity($user_id, 'register', 'users', $user_id, 'User registered');
            
            return ['success' => true, 'message' => 'Registration successful', 'user_id' => $user_id];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    public function login($username, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            if (!verify_password($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            // Set session
            login_user($user);
            
            // Log activity
            $this->log_activity($user['id'], 'login', null, null, 'User logged in');
            
            return ['success' => true, 'message' => 'Login successful', 'user' => $user];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }
    
    public function change_password($user_id, $old_password, $new_password) {
        try {
            // Get current password
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Verify old password
            if (!verify_password($old_password, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Update password
            $hashed_password = hash_password($new_password);
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            // Log activity
            $this->log_activity($user_id, 'change_password', 'users', $user_id, 'Password changed');
            
            return ['success' => true, 'message' => 'Password changed successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to change password: ' . $e->getMessage()];
        }
    }
    
    private function log_activity($user_id, $action, $table_name, $record_id, $description) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $action,
                $table_name,
                $record_id,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (PDOException $e) {
            // Silently fail to avoid breaking main functionality
        }
    }
}
?>