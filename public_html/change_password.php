<?php
$page_title = 'Change Password';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/auth_controller.php';
require_login();

$current_user = get_logged_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        set_message('All fields are required', 'danger');
    } elseif ($new_password !== $confirm_password) {
        set_message('New passwords do not match', 'danger');
    } elseif (strlen($new_password) < 6) {
        set_message('Password must be at least 6 characters long', 'danger');
    } else {
        $auth = new AuthController($pdo);
        $result = $auth->change_password($current_user['id'], $old_password, $new_password);
        
        if ($result['success']) {
            set_message('Password changed successfully', 'success');
            redirect('change_password.php');
        } else {
            set_message($result['message'], 'danger');
        }
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-key"></i> Change Password</h1>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($current_user['username'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($current_user['username']); ?></span>
            </div>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header">
                <h3>Change Your Password</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="old_password">Current Password *</label>
                        <input type="password" id="old_password" name="old_password" class="form-control" 
                               placeholder="Enter your current password" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" 
                               placeholder="Enter new password (min. 6 characters)" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Re-enter new password" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Change Password
                        </button>
                        <a href="index.php" class="btn btn-secondary">
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