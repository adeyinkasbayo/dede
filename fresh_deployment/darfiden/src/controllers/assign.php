<?php
require_once __DIR__ . '/../init.php';

class AssignController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function get_all($shop_id = null, $status = null) {
        try {
            $sql = "SELECT a.*, u.full_name as staff_name, s.name as shop_name, 
                           ub.full_name as assigned_by_name
                    FROM assignments a
                    LEFT JOIN users u ON a.staff_id = u.id
                    LEFT JOIN shops s ON a.shop_id = s.id
                    LEFT JOIN users ub ON a.assigned_by = ub.id
                    WHERE 1=1";
            $params = [];
            
            if ($shop_id) {
                $sql .= " AND a.shop_id = ?";
                $params[] = $shop_id;
            }
            
            if ($status) {
                $sql .= " AND a.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY a.start_date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function create($data) {
        try {
            if (empty($data['staff_id']) || empty($data['shop_id']) || empty($data['start_date'])) {
                return ['success' => false, 'message' => 'Staff, shop, and start date are required'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO assignments (staff_id, shop_id, assigned_by, start_date, end_date, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['staff_id'],
                $data['shop_id'],
                $data['assigned_by'],
                $data['start_date'],
                $data['end_date'] ?? null,
                $data['notes'] ?? null,
                $data['status'] ?? 'active'
            ]);
            
            return ['success' => true, 'message' => 'Assignment created successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create assignment: ' . $e->getMessage()];
        }
    }
    
    public function update($id, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE assignments 
                SET staff_id = ?, shop_id = ?, start_date = ?, end_date = ?, notes = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['staff_id'],
                $data['shop_id'],
                $data['start_date'],
                $data['end_date'] ?? null,
                $data['notes'] ?? null,
                $data['status'] ?? 'active',
                $id
            ]);
            
            return ['success' => true, 'message' => 'Assignment updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update assignment: ' . $e->getMessage()];
        }
    }
}
?>