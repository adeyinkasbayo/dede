<?php
require_once __DIR__ . '/../init.php';

class ExpenseController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function get_all($shop_id = null, $status = null) {
        try {
            $sql = "SELECT e.*, s.name as shop_name, u.full_name as created_by_name, 
                           ua.full_name as approved_by_name
                    FROM expenses e
                    LEFT JOIN shops s ON e.shop_id = s.id
                    LEFT JOIN users u ON e.created_by = u.id
                    LEFT JOIN users ua ON e.approved_by = ua.id
                    WHERE 1=1";
            $params = [];
            
            if ($shop_id) {
                $sql .= " AND e.shop_id = ?";
                $params[] = $shop_id;
            }
            
            if ($status) {
                $sql .= " AND e.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY e.expense_date DESC";
            
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
                SELECT e.*, s.name as shop_name, u.full_name as created_by_name
                FROM expenses e
                LEFT JOIN shops s ON e.shop_id = s.id
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function create($data) {
        try {
            if (empty($data['shop_id']) || empty($data['staff_id']) || empty($data['category']) || empty($data['amount']) || empty($data['expense_date'])) {
                return ['success' => false, 'message' => 'Shop, staff, category, amount, and date are required'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO expenses 
                (shop_id, staff_id, category, description, amount, expense_date, receipt_number, paid_to, payment_method, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['shop_id'],
                $data['staff_id'],
                $data['category'],
                $data['description'],
                $data['amount'],
                $data['expense_date'],
                $data['receipt_number'] ?? null,
                $data['paid_to'] ?? null,
                $data['payment_method'] ?? 'cash',
                $data['status'] ?? 'pending',
                $data['created_by']
            ]);
            
            return ['success' => true, 'message' => 'Expense created successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create expense: ' . $e->getMessage()];
        }
    }
    
    public function update($id, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE expenses 
                SET shop_id = ?, category = ?, description = ?, amount = ?, expense_date = ?, 
                    receipt_number = ?, paid_to = ?, payment_method = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['shop_id'],
                $data['category'],
                $data['description'],
                $data['amount'],
                $data['expense_date'],
                $data['receipt_number'] ?? null,
                $data['paid_to'] ?? null,
                $data['payment_method'] ?? 'cash',
                $data['status'] ?? 'pending',
                $id
            ]);
            
            return ['success' => true, 'message' => 'Expense updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update expense: ' . $e->getMessage()];
        }
    }
    
    public function approve($id, $approved_by) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE expenses 
                SET status = 'approved', approved_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$approved_by, $id]);
            
            return ['success' => true, 'message' => 'Expense approved successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to approve expense: ' . $e->getMessage()];
        }
    }
    
    public function delete($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true, 'message' => 'Expense deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete expense: ' . $e->getMessage()];
        }
    }
    
    public function get_total_by_staff_shop_date($staff_id, $shop_id, $date) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM expenses
                WHERE staff_id = ? AND shop_id = ? AND expense_date = ?
            ");
            $stmt->execute([$staff_id, $shop_id, $date]);
            $result = $stmt->fetch();
            
            return $result ? (float)$result['total'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
}
?>