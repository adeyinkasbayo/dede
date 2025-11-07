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
            
            // Convert empty string to null for shop_id
            $shop_id = (!empty($data['shop_id']) && $data['shop_id'] !== '') ? $data['shop_id'] : null;
            
            $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, role = ?, shop_id = ?, status = ?, 
                    guarantor_full_name = ?, guarantor_address = ?, guarantor_phone = ?";
            $params = [
                $data['username'],
                $data['full_name'],
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['role'] ?? 'staff',
                $shop_id,
                $data['status'] ?? 'active',
                $data['guarantor_full_name'] ?? null,
                $data['guarantor_address'] ?? null,
                $data['guarantor_phone'] ?? null
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
    
    public function update_guarantor_photo($user_id, $filename) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET guarantor_photo = ? WHERE id = ?");
            $stmt->execute([$filename, $user_id]);
            return ['success' => true, 'message' => 'Guarantor photo updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update guarantor photo: ' . $e->getMessage()];
        }
    }
    
    public function delete($id) {
        try {
            // Don't allow deleting admin users
            $check_stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $check_stmt->execute([$id]);
            $user = $check_stmt->fetch();
            $check_stmt->closeCursor(); // Close cursor to prevent statement conflict
            
            if ($user && $user['role'] === 'admin') {
                return ['success' => false, 'message' => 'Cannot delete admin users'];
            }
            
            // Delete related records first to prevent foreign key constraint issues
            $this->pdo->prepare("DELETE FROM staff_shop_assignments WHERE staff_id = ?")->execute([$id]);
            
            // Now delete the user
            $delete_stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $delete_stmt->execute([$id]);
            $delete_stmt->closeCursor();
            
            return ['success' => true, 'message' => 'Staff member deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete staff: ' . $e->getMessage()];
        }
    }
    
    public function get_pending_staff($shop_id = null) {
        try {
            $sql = "SELECT u.*, s.name as shop_name 
                    FROM users u 
                    LEFT JOIN shops s ON u.shop_id = s.id 
                    WHERE u.status = 'pending'";
            $params = [];
            
            if ($shop_id) {
                $sql .= " AND u.shop_id = ?";
                $params[] = $shop_id;
            }
            
            $sql .= " ORDER BY u.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function approve_user($id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true, 'message' => 'Staff member approved successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to approve staff: ' . $e->getMessage()];
        }
    }
    
    public function decline_user($id) {
        try {
            // Delete the pending user
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ? AND status = 'pending'");
            $stmt->execute([$id]);
            
            return ['success' => true, 'message' => 'Registration declined and removed'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to decline registration: ' . $e->getMessage()];
        }
    }
}
?>