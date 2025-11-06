<?php
/**
 * One-time Migration Script
 * Migrates existing staff shop assignments from users.shop_id to staff_shop_assignments table
 */

require_once __DIR__ . '/src/init.php';
require_permission(['admin']);

$current_user = get_logged_user();
$migrated = 0;
$errors = [];

// Check if staff_shop_assignments table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'staff_shop_assignments'");
    if ($stmt->rowCount() == 0) {
        die("ERROR: staff_shop_assignments table does not exist. Please run migration_v1.0.9_multi_shop_tips.sql first.");
    }
} catch (PDOException $e) {
    die("ERROR checking table: " . $e->getMessage());
}

// Get all staff with shop_id assigned but no entries in staff_shop_assignments
try {
    $stmt = $pdo->query("
        SELECT u.id, u.full_name, u.shop_id, s.name as shop_name, s.code as shop_code
        FROM users u
        INNER JOIN shops s ON u.shop_id = s.id
        WHERE u.role = 'staff' 
        AND u.shop_id IS NOT NULL
        AND NOT EXISTS (
            SELECT 1 FROM staff_shop_assignments ssa 
            WHERE ssa.staff_id = u.id AND ssa.shop_id = u.shop_id AND ssa.status = 'active'
        )
    ");
    $staff_to_migrate = $stmt->fetchAll();
    
    if (empty($staff_to_migrate)) {
        $message = "✅ No staff members need migration. All staff are already properly assigned.";
    } else {
        // Migrate each staff member
        foreach ($staff_to_migrate as $staff) {
            try {
                $insert_stmt = $pdo->prepare("
                    INSERT INTO staff_shop_assignments (staff_id, shop_id, assigned_by, assigned_date, status, notes)
                    VALUES (?, ?, ?, CURDATE(), 'active', 'Auto-migrated from users.shop_id')
                ");
                $insert_stmt->execute([
                    $staff['id'],
                    $staff['shop_id'],
                    $current_user['id']
                ]);
                $migrated++;
            } catch (PDOException $e) {
                $errors[] = "Failed to migrate {$staff['full_name']}: " . $e->getMessage();
            }
        }
        
        if ($migrated > 0) {
            $message = "✅ Successfully migrated {$migrated} staff member(s) to new assignment system.";
        }
    }
    
} catch (PDOException $e) {
    die("ERROR during migration: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Staff Assignment Migration</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { background: white; padding: 40px; max-width: 800px; margin: 0 auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Staff Assignment Migration</h1>
        
        <?php if (isset($message)): ?>
            <div class="success">
                <strong><?php echo $message; ?></strong>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <strong>⚠️ Errors occurred during migration:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($staff_to_migrate) && $migrated > 0): ?>
            <h3>Migrated Staff Members:</h3>
            <table>
                <thead>
                    <tr>
                        <th>Staff Name</th>
                        <th>Shop Code</th>
                        <th>Shop Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff_to_migrate as $staff): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($staff['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($staff['shop_code']); ?></td>
                            <td><?php echo htmlspecialchars($staff['shop_name']); ?></td>
                            <td>✅ Migrated</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div class="info">
            <strong>ℹ️ What This Migration Does:</strong>
            <ul>
                <li>Checks for staff members with shop assignments in old system (users.shop_id)</li>
                <li>Creates corresponding entries in new system (staff_shop_assignments)</li>
                <li>Marks assignments as 'active'</li>
                <li>Adds note: "Auto-migrated from users.shop_id"</li>
            </ul>
        </div>
        
        <div class="info">
            <strong>📋 Next Steps:</strong>
            <ol>
                <li>Staff can now see their assigned shops in Daily Operations</li>
                <li>Managers can assign staff to additional shops via "Shop Assignments"</li>
                <li>Staff can work at multiple shop codes</li>
                <li>This migration script can be deleted after successful migration</li>
            </ol>
        </div>
        
        <a href="index.php" class="btn">← Back to Dashboard</a>
        <a href="staff_shop_assignments.php" class="btn" style="background: #28a745;">Manage Shop Assignments</a>
    </div>
</body>
</html>
