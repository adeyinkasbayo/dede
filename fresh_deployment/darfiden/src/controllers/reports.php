<?php
require_once __DIR__ . '/../init.php';

class ReportController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function get_staff_performance($staff_id, $start_date = null, $end_date = null) {
        try {
            // Get staff info
            $stmt = $this->pdo->prepare("
                SELECT u.*, s.name as shop_name 
                FROM users u 
                LEFT JOIN shops s ON u.shop_id = s.id 
                WHERE u.id = ?
            ");
            $stmt->execute([$staff_id]);
            $staff = $stmt->fetch();
            
            if (!$staff) {
                return null;
            }
            
            // Get daily operations
            $sql = "SELECT * FROM daily_operations WHERE staff_id = ?";
            $params = [$staff_id];
            
            if ($start_date) {
                $sql .= " AND operation_date >= ?";
                $params[] = $start_date;
            }
            
            if ($end_date) {
                $sql .= " AND operation_date <= ?";
                $params[] = $end_date;
            }
            
            $sql .= " ORDER BY operation_date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $operations = $stmt->fetchAll();
            
            // Calculate totals
            $total_sales = 0;
            $total_expenses = 0;
            foreach ($operations as $op) {
                $total_sales += $op['total_sales'];
                $total_expenses += $op['total_expenses'];
            }
            
            return [
                'staff' => $staff,
                'operations' => $operations,
                'summary' => [
                    'total_sales' => $total_sales,
                    'total_expenses' => $total_expenses,
                    'net' => $total_sales - $total_expenses,
                    'operations_count' => count($operations)
                ]
            ];
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function get_shop_summary($shop_id, $start_date = null, $end_date = null) {
        try {
            // Get shop info
            $stmt = $this->pdo->prepare("SELECT * FROM shops WHERE id = ?");
            $stmt->execute([$shop_id]);
            $shop = $stmt->fetch();
            
            if (!$shop) {
                return null;
            }
            
            $params = [$shop_id];
            $date_condition = "";
            
            if ($start_date && $end_date) {
                $date_condition = " AND operation_date BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
            }
            
            // Get daily operations summary
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as operations_count,
                    SUM(total_sales) as total_sales,
                    SUM(total_expenses) as total_expenses
                FROM daily_operations 
                WHERE shop_id = ? {$date_condition}
            ");
            $stmt->execute($params);
            $daily_summary = $stmt->fetch();
            
            // Get expenses summary
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as expense_count,
                    SUM(amount) as total_amount
                FROM expenses 
                WHERE shop_id = ? AND status = 'approved' {$date_condition}
            ");
            $stmt->execute($params);
            $expense_summary = $stmt->fetch();
            
            // Get winnings summary
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as winning_count,
                    SUM(amount) as total_amount
                FROM winnings 
                WHERE shop_id = ? AND status IN ('verified', 'paid') {$date_condition}
            ");
            $stmt->execute($params);
            $winning_summary = $stmt->fetch();
            
            return [
                'shop' => $shop,
                'daily_operations' => $daily_summary,
                'expenses' => $expense_summary,
                'winnings' => $winning_summary
            ];
        } catch (PDOException $e) {
            return null;
        }
    }
}
?>