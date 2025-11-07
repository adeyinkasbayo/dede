<?php
require_once __DIR__ . '/../init.php';

class WinningController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function get_all($shop_id = null, $status = null, $search = null, $date_from = null, $date_to = null, $month = null, $limit = 20, $offset = 0, $staff_id = null) {
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
            
            if ($staff_id) {
                $sql .= " AND w.staff_id = ?";
                $params[] = $staff_id;
            }
            
            if ($status) {
                $sql .= " AND w.status = ?";
                $params[] = $status;
            }
            
            // Search by ticket number
            if ($search) {
                $sql .= " AND w.ticket_number LIKE ?";
                $params[] = '%' . $search . '%';
            }
            
            // Filter by date range
            if ($date_from) {
                $sql .= " AND w.winning_date >= ?";
                $params[] = $date_from;
            }
            
            if ($date_to) {
                $sql .= " AND w.winning_date <= ?";
                $params[] = $date_to;
            }
            
            // Filter by month
            if ($month) {
                $sql .= " AND DATE_FORMAT(w.winning_date, '%Y-%m') = ?";
                $params[] = $month;
            }
            
            $sql .= " ORDER BY w.winning_date DESC";
            
            // Add pagination
            if ($limit > 0) {
                $sql .= " LIMIT ? OFFSET ?";
                $params[] = $limit;
                $params[] = $offset;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function count_all($shop_id = null, $status = null, $search = null, $date_from = null, $date_to = null, $month = null) {
        try {
            $sql = "SELECT COUNT(*) FROM winnings w WHERE 1=1";
            $params = [];
            
            if ($shop_id) {
                $sql .= " AND w.shop_id = ?";
                $params[] = $shop_id;
            }
            
            if ($status) {
                $sql .= " AND w.status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $sql .= " AND w.ticket_number LIKE ?";
                $params[] = '%' . $search . '%';
            }
            
            if ($date_from) {
                $sql .= " AND w.winning_date >= ?";
                $params[] = $date_from;
            }
            
            if ($date_to) {
                $sql .= " AND w.winning_date <= ?";
                $params[] = $date_to;
            }
            
            if ($month) {
                $sql .= " AND DATE_FORMAT(w.winning_date, '%Y-%m') = ?";
                $params[] = $month;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    public function create($data) {
        try {
            if (empty($data['shop_id']) || empty($data['staff_id']) || empty($data['amount']) || empty($data['winning_date']) || empty($data['ticket_number'])) {
                return ['success' => false, 'message' => 'Shop, staff, ticket number, amount, and date are required'];
            }
            
            // Check if ticket number already exists
            $stmt = $this->pdo->prepare("SELECT id FROM winnings WHERE ticket_number = ?");
            $stmt->execute([$data['ticket_number']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'This ticket number has already been used'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO winnings 
                (shop_id, staff_id, ticket_number, amount, winning_date, receipt_image, notes, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['shop_id'],
                $data['staff_id'],
                $data['ticket_number'],
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
    
    public function approve($id, $approved_by) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE winnings 
                SET status = 'verified', verified_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$approved_by, $id]);
            
            return ['success' => true, 'message' => 'Winning approved successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to approve winning: ' . $e->getMessage()];
        }
    }
    
    public function decline($id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE winnings 
                SET status = 'rejected'
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            
            return ['success' => true, 'message' => 'Winning declined successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to decline winning: ' . $e->getMessage()];
        }
    }
    
    public function get_total_by_staff_shop_date($staff_id, $shop_id, $date) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM winnings
                WHERE staff_id = ? AND shop_id = ? AND winning_date = ?
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