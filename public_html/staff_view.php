<?php
$page_title = 'Staff Details';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/user.php';
require_permission(['admin', 'manager']);

$current_user = get_logged_user();
$user_controller = new UserController($pdo);

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

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-user"></i> Staff Details</h1>
        </div>
        <div class="header-right">
            <a href="staff_edit.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Staff
            </a>
            <a href="staff_list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card" style="max-width: 1000px; margin: 0 auto;">
            <div class="card-header">
                <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
            </div>
            <div class="card-body">
                <!-- Staff Information -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <div>
                        <h4 style="margin-bottom: 15px; color: #334155; border-bottom: 2px solid #3b82f6; padding-bottom: 10px;">
                            <i class="fas fa-user"></i> Staff Information
                        </h4>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: bold; color: #64748b; display: block; margin-bottom: 5px;">Username:</label>
                            <p style="margin: 0;"><?php echo htmlspecialchars($user['username']); ?></p>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: bold; color: #64748b; display: block; margin-bottom: 5px;">Full Name:</label>
                            <p style="margin: 0;"><?php echo htmlspecialchars($user['full_name']); ?></p>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: bold; color: #64748b; display: block; margin-bottom: 5px;">Email:</label>
                            <p style="margin: 0;"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: bold; color: #64748b; display: block; margin-bottom: 5px;">Phone:</label>
                            <p style="margin: 0;"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></p>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: bold; color: #64748b; display: block; margin-bottom: 5px;">Role:</label>
                            <p style="margin: 0;">
                                <?php 
                                $role_badge = 'secondary';
                                if ($user['role'] === 'admin') $role_badge = 'danger';
                                elseif ($user['role'] === 'manager') $role_badge = 'warning';
                                ?>
                                <span class="badge badge-<?php echo $role_badge; ?>">
                                    <?php echo get_user_role_name($user['role']); ?>
                                </span>
                            </p>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: bold; color: #64748b; display: block; margin-bottom: 5px;">Shop:</label>
                            <p style="margin: 0;"><?php echo htmlspecialchars($user['shop_name'] ?? 'Not assigned'); ?></p>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: bold; color: #64748b; display: block; margin-bottom: 5px;">Status:</label>
                            <p style="margin: 0;">
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php elseif ($user['status'] === 'pending'): ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <?php if (!empty($user['passport_photo'])): ?>
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: bold; color: #64748b; display: block; margin-bottom: 5px;">Passport Photo:</label>
                            <img src="uploads/passports/<?php echo htmlspecialchars($user['passport_photo']); ?>" 
                                 alt="Passport Photo" style="max-width: 200px; border: 2px solid #e2e8f0; border-radius: 8px;">
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Guarantor Information -->
                    <div>
                        <h4 style="margin-bottom: 15px; color: #334155; border-bottom: 2px solid #10b981; padding-bottom: 10px;">
                            <i class="fas fa-user-shield"></i> Guarantor Information
                        </h4>
                        
                        <?php if (!empty($user['guarantor_full_name']) || !empty($user['guarantor_address']) || !empty($user['guarantor_phone']) || !empty($user['guarantor_photo'])): ?>
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: bold; color: #64748b; display: block; margin-bottom: 5px;">Guarantor Full Name:</label>
                                <p style="margin: 0;"><?php echo htmlspecialchars($user['guarantor_full_name'] ?? 'N/A'); ?></p>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: bold; color: #64748b; display: block; margin-bottom: 5px;">Guarantor Address:</label>
                                <p style="margin: 0;"><?php echo nl2br(htmlspecialchars($user['guarantor_address'] ?? 'N/A')); ?></p>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: bold; color: #64748b; display: block; margin-bottom: 5px;">Guarantor Phone:</label>
                                <p style="margin: 0;"><?php echo htmlspecialchars($user['guarantor_phone'] ?? 'N/A'); ?></p>
                            </div>
                            
                            <?php if (!empty($user['guarantor_photo'])): ?>
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: bold; color: #64748b; display: block; margin-bottom: 5px;">Guarantor Photo:</label>
                                <img src="uploads/guarantors/<?php echo htmlspecialchars($user['guarantor_photo']); ?>" 
                                     alt="Guarantor Photo" style="max-width: 200px; border: 2px solid #e2e8f0; border-radius: 8px;">
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p style="color: #94a3b8; font-style: italic;">
                                <i class="fas fa-info-circle"></i> No guarantor information available. 
                                <a href="staff_edit.php?id=<?php echo $user['id']; ?>">Add guarantor details</a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Additional Information -->
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-top: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="font-weight: bold; color: #64748b; display: block; margin-bottom: 5px;">Created At:</label>
                            <p style="margin: 0;"><?php echo date('M d, Y h:i A', strtotime($user['created_at'])); ?></p>
                        </div>
                        <div>
                            <label style="font-weight: bold; color: #64748b; display: block; margin-bottom: 5px;">Last Updated:</label>
                            <p style="margin: 0;"><?php echo date('M d, Y h:i A', strtotime($user['updated_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
