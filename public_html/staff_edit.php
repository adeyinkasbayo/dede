<?php
$page_title = 'Edit Staff';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/user.php';
require_once __DIR__ . '/src/controllers/shop.php';
require_permission(['admin', 'manager']);

$current_user = get_logged_user();
$user_controller = new UserController($pdo);
$shop_controller = new ShopController($pdo);

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    set_message('Staff member not found', 'danger');
    redirect('staff_list.php');
}

$user = $user_controller->get_by_id($user_id);
if (!$user) {
    set_message('Staff member not found', 'danger');
    redirect('staff_list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => sanitize_input($_POST['username'] ?? ''),
        'full_name' => sanitize_input($_POST['full_name'] ?? ''),
        'email' => sanitize_input($_POST['email'] ?? ''),
        'phone' => sanitize_input($_POST['phone'] ?? ''),
        'role' => $_POST['role'] ?? 'staff',
        'shop_id' => $_POST['shop_id'] ?? null,
        'status' => $_POST['status'] ?? 'active',
        'guarantor_full_name' => sanitize_input($_POST['guarantor_full_name'] ?? ''),
        'guarantor_address' => sanitize_input($_POST['guarantor_address'] ?? ''),
        'guarantor_phone' => sanitize_input($_POST['guarantor_phone'] ?? '')
    ];
    
    // Update password if provided
    if (!empty($_POST['password'])) {
        $data['password'] = $_POST['password'];
    }
    
    $result = $user_controller->update($user_id, $data);
    
    // Handle guarantor photo upload
    if ($result['success'] && isset($_FILES['guarantor_photo']) && $_FILES['guarantor_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/guarantors/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_info = pathinfo($_FILES['guarantor_photo']['name']);
        $file_ext = strtolower($file_info['extension']);
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = 'guarantor_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['guarantor_photo']['tmp_name'], $upload_path)) {
                $user_controller->update_guarantor_photo($user_id, $new_filename);
            }
        }
    }
    
    if ($result['success']) {
        set_message($result['message'], 'success');
        redirect('staff_list.php');
    } else {
        set_message($result['message'], 'danger');
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
            <h1><i class="fas fa-user-edit"></i> Edit Staff</h1>
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
                <h3>Edit Staff Information</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               placeholder="Enter full name" required
                               value="<?php echo htmlspecialchars($user['full_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               placeholder="Enter username" required
                               value="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Leave blank to keep current password">
                        <small style="color: #64748b;">Only fill if you want to change the password</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="Enter email"
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               placeholder="Enter phone number"
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" class="form-control" required>
                            <?php if (is_admin()): ?>
                                <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                <option value="manager" <?php echo ($user['role'] === 'manager') ? 'selected' : ''; ?>>Manager</option>
                            <?php endif; ?>
                            <option value="staff" <?php echo ($user['role'] === 'staff') ? 'selected' : ''; ?>>Staff Member</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="shop_id">Assign to Shop</label>
                        <select id="shop_id" name="shop_id" class="form-control">
                            <option value="">-- Select Shop --</option>
                            <?php foreach ($shops as $shop): ?>
                                <option value="<?php echo $shop['id']; ?>"
                                    <?php echo ($user['shop_id'] == $shop['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($shop['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="active" <?php echo ($user['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($user['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Staff
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