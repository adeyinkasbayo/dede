<?php
$page_title = 'Approve Staff';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/user.php';
require_permission(['admin', 'manager']);

$current_user = get_logged_user();
$user_controller = new UserController($pdo);

// Handle approve/decline actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    if ($_GET['action'] === 'approve') {
        $result = $user_controller->approve_user($user_id);
        set_message($result['message'], $result['success'] ? 'success' : 'danger');
        redirect('staff_approve.php');
    } elseif ($_GET['action'] === 'decline') {
        $result = $user_controller->decline_user($user_id);
        set_message($result['message'], $result['success'] ? 'success' : 'danger');
        redirect('staff_approve.php');
    }
}

// Get pending staff
$shop_id = is_admin() ? null : $current_user['shop_id'];
$pending_staff = $user_controller->get_pending_staff($shop_id);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-user-check"></i> Approve Staff Registrations</h1>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Pending Staff Registrations</h3>
            </div>
            <div class="card-body">
                <?php if (empty($pending_staff)): ?>
                    <p style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fas fa-user-check" style="font-size: 48px; opacity: 0.5;"></i><br><br>
                        No pending staff registrations.
                    </p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Shop</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_staff as $staff): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($staff['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($staff['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($staff['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($staff['phone'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge badge-secondary">
                                        <?php echo get_user_role_name($staff['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($staff['shop_name'] ?? 'Not assigned'); ?></td>
                                <td><?php echo format_datetime($staff['created_at']); ?></td>
                                <td class="table-actions">
                                    <a href="?action=approve&id=<?php echo $staff['id']; ?>" 
                                       class="btn btn-sm btn-success" 
                                       onclick="return confirm('Approve this staff member?')" 
                                       title="Approve">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <a href="?action=decline&id=<?php echo $staff['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Decline this registration? This will delete the user.')" 
                                       title="Decline">
                                        <i class="fas fa-times"></i> Decline
                                    </a>
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
