<?php
require_once __DIR__ . '/../init.php';

class ShopController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function get_all($status = null) {
        try {
            $sql = "SELECT s.*, u.full_name as manager_name 
                    FROM shops s 
                    LEFT JOIN users u ON s.manager_id = u.id";
            
            if ($status) {
                $sql .= " WHERE s.status = ?";}
            $sql .= " ORDER BY s.name";
            
            $stmt = $this->pdo->prepare($sql);
            if ($status) {
                $stmt->execute([$status]);
            } else {
                $stmt->execute();
            }
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function get_by_id($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.full_name as manager_name 
                FROM shops s 
                LEFT JOIN users u ON s.manager_id = u.id 
                WHERE s.id = ?
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
            if (empty($data['name']) || empty($data['code'])) {
                return ['success' => false, 'message' => 'Shop name and code are required'];
            }
            
            // Check if code exists
            $stmt = $this->pdo->prepare("SELECT id FROM shops WHERE code = ?");
            $stmt->execute([$data['code']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Shop code already exists'];
            }
            
            // Convert empty string to null for manager_id
            $manager_id = (!empty($data['manager_id']) && $data['manager_id'] !== '') ? $data['manager_id'] : null;
            
            // Insert shop
            $stmt = $this->pdo->prepare("
                INSERT INTO shops (name, code, location, address, phone, manager_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['name'],
                $data['code'],
                $data['location'] ?? null,
                $data['address'] ?? null,
                $data['phone'] ?? null,
                $manager_id,
                $data['status'] ?? 'active'
            ]);
            
            $shop_id = $this->pdo->lastInsertId();
            
            return ['success' => true, 'message' => 'Shop created successfully', 'shop_id' => $shop_id];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create shop: ' . $e->getMessage()];
        }
    }
    
    public function update($id, $data) {
        try {
            // Check if code exists for other shops
            if (!empty($data['code'])) {
                $stmt = $this->pdo->prepare("SELECT id FROM shops WHERE code = ? AND id != ?");
                $stmt->execute([$data['code'], $id]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Shop code already exists'];
                }
            }
            
            // Convert empty string to null for manager_id
            $manager_id = (!empty($data['manager_id']) && $data['manager_id'] !== '') ? $data['manager_id'] : null;
            
            $stmt = $this->pdo->prepare("
                UPDATE shops 
                SET name = ?, code = ?, location = ?, address = ?, phone = ?, manager_id = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['name'],
                $data['code'],
                $data['location'] ?? null,
                $data['address'] ?? null,
                $data['phone'] ?? null,
                $manager_id,
                $data['status'] ?? 'active',
                $id
            ]);
            
            return ['success' => true, 'message' => 'Shop updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update shop: ' . $e->getMessage()];
        }
    }
    
    public function delete($id) {
        try {
            // Check if shop has associated records
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE shop_id = ?");
            $stmt->execute([$id]);
            $user_count = $stmt->fetchColumn();
            
            if ($user_count > 0) {
                return ['success' => false, 'message' => 'Cannot delete shop with associated staff members'];
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM shops WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true, 'message' => 'Shop deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete shop: ' . $e->getMessage()];
        }
    }
}
?>