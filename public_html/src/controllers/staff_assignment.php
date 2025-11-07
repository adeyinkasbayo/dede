<?php
require_once __DIR__ . '/../init.php';

class StaffAssignmentController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function get_staff_shops($staff_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ssa.*, s.name as shop_name, s.code as shop_code, u.full_name as assigned_by_name
                FROM staff_shop_assignments ssa
                INNER JOIN shops s ON ssa.shop_id = s.id
                LEFT JOIN users u ON ssa.assigned_by = u.id
                WHERE ssa.staff_id = ? AND ssa.status = 'active'
                ORDER BY ssa.created_at DESC
            ");
            $stmt->execute([$staff_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function assign_shop($staff_id, $shop_id, $assigned_by, $notes = null) {
        try {
            // Check if already assigned
            $stmt = $this->pdo->prepare("
                SELECT id FROM staff_shop_assignments 
                WHERE staff_id = ? AND shop_id = ? AND status = 'active'
            ");
            $stmt->execute([$staff_id, $shop_id]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Staff is already assigned to this shop'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO staff_shop_assignments (staff_id, shop_id, assigned_by, assigned_date, notes)
                VALUES (?, ?, ?, CURDATE(), ?)
            ");
            $stmt->execute([$staff_id, $shop_id, $assigned_by, $notes]);
            
            return ['success' => true, 'message' => 'Shop assigned successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to assign shop: ' . $e->getMessage()];
        }
    }
    
    public function remove_assignment($assignment_id) {
        try {
            // Delete the assignment record instead of setting to inactive
            // This prevents duplicate inactive records in the unique constraint
            $stmt = $this->pdo->prepare("DELETE FROM staff_shop_assignments WHERE id = ?");
            $stmt->execute([$assignment_id]);
            return ['success' => true, 'message' => 'Assignment removed successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to remove assignment: ' . $e->getMessage()];
        }
    }
    
    public function get_all_assignments() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ssa.*, s.name as shop_name, s.code as shop_code, 
                       u.full_name as staff_name, u2.full_name as assigned_by_name
                FROM staff_shop_assignments ssa
                INNER JOIN shops s ON ssa.shop_id = s.id
                INNER JOIN users u ON ssa.staff_id = u.id
                LEFT JOIN users u2 ON ssa.assigned_by = u2.id
                WHERE ssa.status = 'active'
                ORDER BY u.full_name, s.code
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function get_assigned_shops_for_staff($staff_id) {
        try {
            // First, try to get from staff_shop_assignments table
            $stmt = $this->pdo->prepare("
                SELECT s.id, s.name, s.code
                FROM staff_shop_assignments ssa
                INNER JOIN shops s ON ssa.shop_id = s.id
                WHERE ssa.staff_id = ? AND ssa.status = 'active'
                ORDER BY s.code
            ");
            $stmt->execute([$staff_id]);
            $shops = $stmt->fetchAll();
            
            // FALLBACK: If no assignments found, check user's shop_id (legacy support)
            if (empty($shops)) {
                $stmt = $this->pdo->prepare("
                    SELECT s.id, s.name, s.code
                    FROM users u
                    INNER JOIN shops s ON u.shop_id = s.id
                    WHERE u.id = ? AND u.shop_id IS NOT NULL
                    ORDER BY s.code
                ");
                $stmt->execute([$staff_id]);
                $shops = $stmt->fetchAll();
            }
            
            return $shops;
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>
