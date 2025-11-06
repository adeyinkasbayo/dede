<?php
$page_title = 'Staff Shop Assignments';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/staff_assignment.php';
require_once __DIR__ . '/src/controllers/user.php';
require_once __DIR__ . '/src/controllers/shop.php';
require_permission(['admin', 'manager']);

$current_user = get_logged_user();
$assignment_controller = new StaffAssignmentController($pdo);
$user_controller = new UserController($pdo);
$shop_controller = new ShopController($pdo);

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'assign') {
        $result = $assignment_controller->assign_shop(
            $_POST['staff_id'],
            $_POST['shop_id'],
            $current_user['id'],
            sanitize_input($_POST['notes'] ?? '')
        );
        set_message($result['message'], $result['success'] ? 'success' : 'danger');
    } elseif ($_POST['action'] === 'remove') {
        $result = $assignment_controller->remove_assignment($_POST['assignment_id']);
        set_message($result['message'], $result['success'] ? 'success' : 'danger');
    }
    redirect('staff_shop_assignments.php');
}

// Get data
$staff_list = $user_controller->get_all('staff');
$shops = get_accessible_shops($pdo);
$assignments = $assignment_controller->get_all_assignments();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-users-cog"></i> Staff Shop Assignments</h1>
        </div>
        <div class="header-right">
            <button class="btn btn-primary" onclick="document.getElementById('assignModal').style.display='block'">
                <i class="fas fa-plus"></i> Assign Staff to Shop
            </button>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>All Staff Shop Assignments</h3>
                <p style="color: #64748b; margin-top: 5px;">Staff can be assigned to multiple shop codes</p>
            </div>
            <div class="card-body">
                <?php if (empty($assignments)): ?>
                    <p style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fas fa-users-cog" style="font-size: 48px; opacity: 0.5;"></i><br><br>
                        No assignments yet. Click "Assign Staff to Shop" to begin.
                    </p>
                <?php else: ?>
                    <?php 
                    // Group by staff
                    $grouped = [];
                    foreach ($assignments as $assignment) {
                        $grouped[$assignment['staff_name']][] = $assignment;
                    }
                    ?>
                    
                    <?php foreach ($grouped as $staff_name => $staff_assignments): ?>
                        <div style="margin-bottom: 30px; padding: 20px; background: #f8fafc; border-radius: 8px;">
                            <h4 style="margin-bottom: 15px; color: #334155;">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($staff_name); ?>
                            </h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                                <?php foreach ($staff_assignments as $assignment): ?>
                                    <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                        <div style="display: flex; justify-content: space-between; align-items: start;">
                                            <div>
                                                <p style="margin: 0; font-weight: bold; color: #3b82f6;">
                                                    <?php echo htmlspecialchars($assignment['shop_code']); ?>
                                                </p>
                                                <p style="margin: 5px 0 0 0; color: #64748b; font-size: 14px;">
                                                    <?php echo htmlspecialchars($assignment['shop_name']); ?>
                                                </p>
                                                <p style="margin: 10px 0 0 0; font-size: 12px; color: #94a3b8;">
                                                    Assigned: <?php echo format_date($assignment['assigned_date']); ?>
                                                </p>
                                            </div>
                                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Remove this assignment?')">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Remove Assignment">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Assignment Modal -->
<div id="assignModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow: auto;">
    <div style="background: white; margin: 5% auto; padding: 0; width: 90%; max-width: 600px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">Assign Staff to Shop</h3>
            <button onclick="document.getElementById('assignModal').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div style="padding: 20px;">
            <form method="POST">
                <input type="hidden" name="action" value="assign">
                
                <div class="form-group">
                    <label for="staff_id">Select Staff *</label>
                    <select id="staff_id" name="staff_id" class="form-control" required>
                        <option value="">-- Select Staff --</option>
                        <?php foreach ($staff_list as $staff): ?>
                            <option value="<?php echo $staff['id']; ?>">
                                <?php echo htmlspecialchars($staff['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="shop_id">Select Shop *</label>
                    <select id="shop_id" name="shop_id" class="form-control" required>
                        <option value="">-- Select Shop --</option>
                        <?php foreach ($shops as $shop): ?>
                            <option value="<?php echo $shop['id']; ?>">
                                <?php echo htmlspecialchars($shop['code']); ?> - <?php echo htmlspecialchars($shop['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes (Optional)</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Any additional notes"></textarea>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Assign Shop
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('assignModal').style.display='none'">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
