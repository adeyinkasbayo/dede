<?php
require_once __DIR__ . '/../init.php';

class DailyController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function get_all($shop_id = null, $start_date = null, $end_date = null, $shop_code = null) {
        try {
            $sql = "SELECT d.*, s.name as shop_name, s.code as shop_code_name, u.full_name as staff_name,
                    (d.cash_balance + d.tips) as tips_calculation
                    FROM daily_operations d
                    LEFT JOIN shops s ON d.shop_id = s.id
                    LEFT JOIN users u ON d.staff_id = u.id
                    WHERE 1=1";
            $params = [];
            
            if ($shop_id) {
                $sql .= " AND d.shop_id = ?";
                $params[] = $shop_id;
            }
            
            if ($shop_code) {
                $sql .= " AND d.shop_code = ?";
                $params[] = $shop_code;
            }
            
            if ($start_date) {
                $sql .= " AND d.operation_date >= ?";
                $params[] = $start_date;
            }
            
            if ($end_date) {
                $sql .= " AND d.operation_date <= ?";
                $params[] = $end_date;
            }
            
            $sql .= " ORDER BY d.operation_date DESC, d.shop_code ASC";
            
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
                SELECT d.*, s.name as shop_name, s.code as shop_code_name, u.full_name as staff_name,
                (d.cash_balance + d.tips) as tips_calculation
                FROM daily_operations d
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
            if (empty($data['shop_code']) || empty($data['staff_id']) || empty($data['operation_date'])) {
                return ['success' => false, 'message' => 'Shop code, staff, and operation date are required'];
            }
            
            // Get shop_id from shop_code
            $stmt = $this->pdo->prepare("SELECT id FROM shops WHERE code = ?");
            $stmt->execute([$data['shop_code']]);
            $shop = $stmt->fetch();
            
            if (!$shop) {
                return ['success' => false, 'message' => 'Invalid shop code'];
            }
            $shop_id = $shop['id'];
            
            // Check for duplicate entry (staff + shop_code + date)
            $stmt = $this->pdo->prepare("
                SELECT id FROM daily_operations 
                WHERE staff_id = ? AND shop_code = ? AND operation_date = ?
            ");
            $stmt->execute([$data['staff_id'], $data['shop_code'], $data['operation_date']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Daily operation already exists for this staff, shop code, and date'];
            }
            
            // Calculate cash balance: Opening + Transfer - Winnings - Expenses - Daily Debt - Closing
            $opening = $data['opening_balance'] ?? 0;
            $transfer = $data['transfer_to_staff'] ?? 0;
            $winnings = $data['total_winnings'] ?? 0;
            $expenses = $data['total_expenses'] ?? 0;
            $daily_debt = $data['daily_debt'] ?? 0;
            $closing = $data['closing_balance'] ?? 0;
            $cash_balance = $opening + $transfer - $winnings - $expenses - $daily_debt - $closing;
            
            // Calculate tips calculation: Cash Balance + Tips
            $tips = $data['tips'] ?? 0;
            $tips_calculation = $cash_balance + $tips;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO daily_operations 
                (shop_id, shop_code, staff_id, operation_date, opening_balance, closing_balance, total_sales, total_expenses, total_winnings, transfer_to_staff, daily_debt, cash_balance, tips, tips_calculation, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $shop_id,
                $data['shop_code'],
                $data['staff_id'],
                $data['operation_date'],
                $opening,
                $closing,
                $data['total_sales'] ?? 0,
                $expenses,
                $winnings,
                $transfer,
                $daily_debt,
                $cash_balance,
                $tips,
                $tips_calculation,
                $data['notes'] ?? null,
                $data['created_by']
            ]);
            
            return ['success' => true, 'message' => 'Daily operation recorded successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create daily operation: ' . $e->getMessage()];
        }
    }
    
    public function update($id, $data) {
        try {
            // Get shop_id from shop_code
            if (!empty($data['shop_code'])) {
                $stmt = $this->pdo->prepare("SELECT id FROM shops WHERE code = ?");
                $stmt->execute([$data['shop_code']]);
                $shop = $stmt->fetch();
                
                if (!$shop) {
                    return ['success' => false, 'message' => 'Invalid shop code'];
                }
                $shop_id = $shop['id'];
            } else {
                $shop_id = $data['shop_id'];
            }
            
            // Calculate cash balance: Opening + Transfer - Winnings - Expenses - Daily Debt - Closing
            $opening = $data['opening_balance'] ?? 0;
            $transfer = $data['transfer_to_staff'] ?? 0;
            $winnings = $data['total_winnings'] ?? 0;
            $expenses = $data['total_expenses'] ?? 0;
            $daily_debt = $data['daily_debt'] ?? 0;
            $closing = $data['closing_balance'] ?? 0;
            $cash_balance = $opening + $transfer - $winnings - $expenses - $daily_debt - $closing;
            
            // Calculate tips calculation: Cash Balance + Tips
            $tips = $data['tips'] ?? 0;
            $tips_calculation = $cash_balance + $tips;
            
            $stmt = $this->pdo->prepare("
                UPDATE daily_operations 
                SET shop_id = ?, shop_code = ?, staff_id = ?, operation_date = ?, opening_balance = ?, 
                    closing_balance = ?, total_sales = ?, total_expenses = ?, total_winnings = ?, 
                    transfer_to_staff = ?, daily_debt = ?, cash_balance = ?, tips = ?, tips_calculation = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $shop_id,
                $data['shop_code'] ?? null,
                $data['staff_id'],
                $data['operation_date'],
                $opening,
                $closing,
                $data['total_sales'] ?? 0,
                $expenses,
                $winnings,
                $transfer,
                $daily_debt,
                $cash_balance,
                $tips,
                $tips_calculation,
                $data['notes'] ?? null,
                $id
            ]);
            
            return ['success' => true, 'message' => 'Daily operation updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update daily operation: ' . $e->getMessage()];
        }
    }
    
    public function get_by_staff_and_date_range($staff_id, $start_date, $end_date) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.*, s.name as shop_name, s.code as shop_code_name, u.full_name as staff_name,
                (d.cash_balance + d.tips) as tips_calculation
                FROM daily_operations d
                LEFT JOIN shops s ON d.shop_id = s.id
                LEFT JOIN users u ON d.staff_id = u.id
                WHERE d.staff_id = ? AND d.operation_date BETWEEN ? AND ?
                ORDER BY d.operation_date ASC, d.shop_code ASC
            ");
            $stmt->execute([$staff_id, $start_date, $end_date]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>