<?php
require_once __DIR__ . '/../init.php';

class WinningController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function get_all($shop_id = null, $status = null) {
        try {
            $sql = "SELECT w.*, s.name as shop_name, u.full_name as staff_name, 
                           uv.full_name as verified_by_name
                    FROM winnings w
                    LEFT JOIN shops s ON w.shop_id = s.id
                    LEFT JOIN users u ON w.staff_id = u.id
                    LEFT JOIN users uv ON w.verified_by = uv.id
                    WHERE 1=1";
            $params = [];
            
            if ($shop_id) {
                $sql .= " AND w.shop_id = ?";
                $params[] = $shop_id;
            }
            
            if ($status) {
                $sql .= " AND w.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY w.winning_date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function create($data) {
        try {
            if (empty($data['shop_id']) || empty($data['staff_id']) || empty($data['amount']) || empty($data['winning_date'])) {
                return ['success' => false, 'message' => 'Shop, staff, amount, and date are required'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO winnings 
                (shop_id, staff_id, customer_name, ticket_number, amount, winning_date, receipt_image, notes, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['shop_id'],
                $data['staff_id'],
                $data['customer_name'] ?? null,
                $data['ticket_number'] ?? null,
                $data['amount'],
                $data['winning_date'],
                $data['receipt_image'] ?? null,
                $data['notes'] ?? null,
                $data['status'] ?? 'pending',
                $data['created_by']
            ]);
            
            return ['success' => true, 'message' => 'Winning record created successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create winning: ' . $e->getMessage()];
        }
    }
    
    public function verify($id, $verified_by) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE winnings 
                SET status = 'verified', verified_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$verified_by, $id]);
            
            return ['success' => true, 'message' => 'Winning verified successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to verify winning: ' . $e->getMessage()];
        }
    }
}
?>