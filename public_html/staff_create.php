<?php
$page_title = 'Create Staff';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/user.php';
require_once __DIR__ . '/src/controllers/shop.php';
require_permission(['admin', 'manager']);

$current_user = get_logged_user();
$user_controller = new UserController($pdo);
$shop_controller = new ShopController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => sanitize_input($_POST['username'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'full_name' => sanitize_input($_POST['full_name'] ?? ''),
        'email' => sanitize_input($_POST['email'] ?? ''),
        'phone' => sanitize_input($_POST['phone'] ?? ''),
        'role' => $_POST['role'] ?? 'staff',
        'shop_id' => $_POST['shop_id'] ?? null,
        'status' => $_POST['status'] ?? 'active'
    ];
    
    // Managers can only create staff, not managers or admins
    if (!is_admin() && in_array($data['role'], ['admin', 'manager'])) {
        set_message('You do not have permission to create managers or admins', 'danger');
    } else {
        $result = $user_controller->create($data);
        
        if ($result['success']) {
            set_message($result['message'], 'success');
            redirect('staff_list.php');
        } else {
            set_message($result['message'], 'danger');
        }
    }
}

// Get shops for dropdown
$shops = get_accessible_shops($pdo);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-user-plus"></i> Create Staff</h1>
        </div>
        <div class="header-right">
            <a href="staff_list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <div class="card-header">
                <h3>Staff Information</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               placeholder="Enter full name" required
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               placeholder="Enter username" required
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Enter password" required>
                        <small style="color: #64748b;">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="Enter email"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               placeholder="Enter phone number"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" class="form-control" required>
                            <?php if (is_admin()): ?>
                                <option value="admin">Administrator</option>
                                <option value="manager">Manager</option>
                            <?php endif; ?>
                            <option value="staff" selected>Staff Member</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="shop_id">Assign to Shop</label>
                        <select id="shop_id" name="shop_id" class="form-control">
                            <option value="">-- Select Shop --</option>
                            <?php foreach ($shops as $shop): ?>
                                <option value="<?php echo $shop['id']; ?>">
                                    <?php echo htmlspecialchars($shop['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Staff
                        </button>
                        <a href="staff_list.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>