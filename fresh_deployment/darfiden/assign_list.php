<?php
$page_title = 'Assignments';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/assign.php';
require_permission(['admin', 'manager']);

$current_user = get_logged_user();
$assign_controller = new AssignController($pdo);

$shop_id = is_admin() ? null : $current_user['shop_id'];
$assignments = $assign_controller->get_all($shop_id);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-tasks"></i> Assignments</h1>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Staff Assignments</h3>
            </div>
            <div class="card-body">
                <?php if (empty($assignments)): ?>
                    <p style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fas fa-tasks" style="font-size: 48px; opacity: 0.5;"></i><br><br>
                        No assignments found.
                    </p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Shop</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Assigned By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($assignment['staff_name']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['shop_name']); ?></td>
                                <td><?php echo format_date($assignment['start_date']); ?></td>
                                <td><?php echo $assignment['end_date'] ? format_date($assignment['end_date']) : 'Ongoing'; ?></td>
                                <td><?php echo htmlspecialchars($assignment['assigned_by_name']); ?></td>
                                <td>
                                    <?php 
                                    $status_badge = 'secondary';
                                    if ($assignment['status'] === 'active') $status_badge = 'success';
                                    elseif ($assignment['status'] === 'completed') $status_badge = 'info';
                                    elseif ($assignment['status'] === 'cancelled') $status_badge = 'danger';
                                    ?>
                                    <span class="badge badge-<?php echo $status_badge; ?>">
                                        <?php echo ucfirst($assignment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>