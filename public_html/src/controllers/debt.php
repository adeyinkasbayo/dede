<?php
require_once __DIR__ . '/../init.php';

class DebtController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function get_all($staff_id = null, $shop_id = null, $status = null, $date_from = null, $date_to = null) {
        try {
            $sql = "SELECT d.*, s.name as shop_name, u.full_name as staff_name
                    FROM debts d
                    LEFT JOIN shops s ON d.shop_id = s.id
                    LEFT JOIN users u ON d.staff_id = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($staff_id) {
                $sql .= " AND d.staff_id = ?";
                $params[] = $staff_id;
            }
            
            if ($shop_id) {
                $sql .= " AND d.shop_id = ?";
                $params[] = $shop_id;
            }
            
            if ($status) {
                $sql .= " AND d.status = ?";
                $params[] = $status;
            }
            
            if ($date_from) {
                $sql .= " AND d.debt_date >= ?";
                $params[] = $date_from;
            }
            
            if ($date_to) {
                $sql .= " AND d.debt_date <= ?";
                $params[] = $date_to;
            }
            
            $sql .= " ORDER BY d.debt_date DESC";
            
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
                SELECT d.*, s.name as shop_name, u.full_name as staff_name
                FROM debts d
                LEFT JOIN shops s ON d.shop_id = s.id
                LEFT JOIN users u ON d.staff_id = u.id
                WHERE d.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function create($data) {
        try {
            if (empty($data['staff_id']) || empty($data['amount']) || empty($data['debt_date'])) {
                return ['success' => false, 'message' => 'Staff, amount, and date are required'];
            }
            
            $balance = $data['amount'];
            
            $stmt = $this->pdo->prepare("
                INSERT INTO debts (staff_id, shop_id, amount, debt_date, description, balance, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['staff_id'],
                $data['shop_id'],
                $data['amount'],
                $data['debt_date'],
                $data['description'] ?? null,
                $balance,
                $data['created_by']
            ]);
            
            return ['success' => true, 'message' => 'Debt recorded successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to record debt: ' . $e->getMessage()];
        }
    }
    
    public function record_payment($debt_id, $amount, $payment_date, $notes, $recorded_by) {
        try {
            $this->pdo->beginTransaction();
            
            // Get current debt
            $debt = $this->get_by_id($debt_id);
            if (!$debt) {
                throw new Exception('Debt not found');
            }
            
            // Record payment
            $stmt = $this->pdo->prepare("
                INSERT INTO debt_payments (debt_id, amount, payment_date, notes, recorded_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$debt_id, $amount, $payment_date, $notes, $recorded_by]);
            
            // Update debt
            $new_total_paid = $debt['total_paid'] + $amount;
            $new_balance = $debt['amount'] - $new_total_paid;
            
            if ($new_balance <= 0) {
                $status = 'paid';
                $new_balance = 0;
            } elseif ($new_total_paid > 0) {
                $status = 'partially_paid';
            } else {
                $status = 'unpaid';
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE debts 
                SET total_paid = ?, balance = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$new_total_paid, $new_balance, $status, $debt_id]);
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Payment recorded successfully'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to record payment: ' . $e->getMessage()];
        }
    }
    
    public function get_staff_debt_summary($staff_id, $date_from = null, $date_to = null) {
        try {
            $sql = "SELECT 
                        SUM(amount) as total_debt,
                        SUM(total_paid) as total_paid,
                        SUM(balance) as total_balance
                    FROM debts 
                    WHERE staff_id = ?";
            $params = [$staff_id];
            
            if ($date_from) {
                $sql .= " AND debt_date >= ?";
                $params[] = $date_from;
            }
            
            if ($date_to) {
                $sql .= " AND debt_date <= ?";
                $params[] = $date_to;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function get_payments($debt_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT dp.*, u.full_name as recorded_by_name
                FROM debt_payments dp
                LEFT JOIN users u ON dp.recorded_by = u.id
                WHERE dp.debt_id = ?
                ORDER BY dp.payment_date DESC
            ");
            $stmt->execute([$debt_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>
