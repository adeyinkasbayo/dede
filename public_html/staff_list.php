<?php
$page_title = 'Staff Management';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/user.php';
require_permission(['admin', 'manager']);

$current_user = get_current_user();
$user_controller = new UserController($pdo);

// Handle delete
if (isset($_GET['delete'])) {
    $result = $user_controller->delete($_GET['delete']);
    set_message($result['message'], $result['success'] ? 'success' : 'danger');
    redirect('staff_list.php');
}

// Get staff based on role
$shop_id = is_admin() ? null : $current_user['shop_id'];
$staff = $user_controller->get_all(null, $shop_id);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-users"></i> Staff Management</h1>
        </div>
        <div class="header-right">
            <a href="staff_create.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add New Staff
            </a>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>All Staff Members</h3>
                <input type="text" id="searchInput" class="form-control" style="max-width: 300px;" 
                       placeholder="Search staff..." onkeyup="filterTable('searchInput', 'staffTable')">
            </div>
            <div class="card-body">
                <?php if (empty($staff)): ?>
                    <p style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fas fa-users" style="font-size: 48px; opacity: 0.5;"></i><br><br>
                        No staff members found. <a href="staff_create.php">Add your first staff member</a>
                    </p>
                <?php else: ?>
                    <table class="table" id="staffTable">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Shop</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $member): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($member['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($member['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($member['phone'] ?? '-'); ?></td>
                                <td>
                                    <?php 
                                    $role_badge = 'secondary';
                                    if ($member['role'] === 'admin') $role_badge = 'danger';
                                    elseif ($member['role'] === 'manager') $role_badge = 'warning';
                                    ?>
                                    <span class="badge badge-<?php echo $role_badge; ?>">
                                        <?php echo get_user_role_name($member['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($member['shop_name'] ?? 'Not assigned'); ?></td>
                                <td>
                                    <?php if ($member['status'] === 'active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <a href="staff_edit.php?id=<?php echo $member['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($member['role'] !== 'admin'): ?>
                                        <a href="?delete=<?php echo $member['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirmDelete('Delete this staff member?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
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