<?php
require_once __DIR__ . '/../init.php';

class UserController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function get_all($role = null, $shop_id = null) {
        try {
            $sql = "SELECT u.*, s.name as shop_name 
                    FROM users u 
                    LEFT JOIN shops s ON u.shop_id = s.id 
                    WHERE 1=1";
            $params = [];
            
            if ($role) {
                $sql .= " AND u.role = ?";
                $params[] = $role;
            }
            
            if ($shop_id) {
                $sql .= " AND u.shop_id = ?";
                $params[] = $shop_id;
            }
            
            $sql .= " ORDER BY u.full_name";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function get_by_id($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, s.name as shop_name 
                FROM users u 
                LEFT JOIN shops s ON u.shop_id = s.id 
                WHERE u.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function create($data) {
        try {
            // Validate
            if (empty($data['username']) || empty($data['password']) || empty($data['full_name'])) {
                return ['success' => false, 'message' => 'Username, password, and full name are required'];
            }
            
            // Check if username exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$data['username']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username already exists'];
            }
            
            // Hash password
            $hashed_password = hash_password($data['password']);
            
            // Convert empty string to null for shop_id
            $shop_id = (!empty($data['shop_id']) && $data['shop_id'] !== '') ? $data['shop_id'] : null;
            
            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password, full_name, email, phone, role, shop_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['username'],
                $hashed_password,
                $data['full_name'],
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['role'] ?? 'staff',
                $shop_id,
                $data['status'] ?? 'active'
            ]);
            
            $user_id = $this->pdo->lastInsertId();
            
            return ['success' => true, 'message' => 'Staff member created successfully', 'user_id' => $user_id];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create staff: ' . $e->getMessage()];
        }
    }
    
    public function update($id, $data) {
        try {
            // Check if username exists for other users
            if (!empty($data['username'])) {
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$data['username'], $id]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Username already exists'];
                }
            }
            
            $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, role = ?, shop_id = ?, status = ?";
            $params = [
                $data['username'],
                $data['full_name'],
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['role'] ?? 'staff',
                $data['shop_id'] ?? null,
                $data['status'] ?? 'active'
            ];
            
            // Update password if provided
            if (!empty($data['password'])) {
                $sql .= ", password = ?";
                $params[] = hash_password($data['password']);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true, 'message' => 'Staff member updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update staff: ' . $e->getMessage()];
        }
    }
    
    public function update_passport($user_id, $filename) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET passport_photo = ? WHERE id = ?");
            $stmt->execute([$filename, $user_id]);
            return ['success' => true, 'message' => 'Passport photo updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update passport: ' . $e->getMessage()];
        }
    }
    
    public function delete($id) {
        try {
            // Don't allow deleting admin users
            $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
            if ($user && $user['role'] === 'admin') {
                return ['success' => false, 'message' => 'Cannot delete admin users'];
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true, 'message' => 'Staff member deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete staff: ' . $e->getMessage()];
        }
    }
}
?>