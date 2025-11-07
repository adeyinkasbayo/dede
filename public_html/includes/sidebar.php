<?php
// Get pending winnings count for managers
$pending_winnings_count = 0;
if (is_manager()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM winnings WHERE status = 'pending'");
    $stmt->execute();
    $pending_winnings_count = $stmt->fetchColumn();
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h3><?php echo APP_NAME; ?></h3>
        <p style="font-size: 12px; opacity: 0.7;"><?php echo get_user_role_name($current_user['role']); ?></p>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php">
            <i class="fas fa-home"></i> Dashboard
        </a>
        
        <?php if (is_admin()): ?>
        <a href="shop_list.php">
            <i class="fas fa-store"></i> Shops
        </a>
        <?php endif; ?>
        
        <?php if (is_manager()): ?>
        <a href="staff_list.php">
            <i class="fas fa-users"></i> Staff Management
        </a>
        
        <a href="staff_approve.php">
            <i class="fas fa-user-check"></i> Approve Staff
        </a>
        
        <a href="staff_shop_assignments.php">
            <i class="fas fa-users-cog"></i> Shop Assignments
        </a>
        
        <a href="assign_list.php">
            <i class="fas fa-tasks"></i> Assignments
        </a>
        <?php endif; ?>
        
        <a href="daily_list.php">
            <i class="fas fa-calendar-day"></i> Daily Operations
        </a>
        
        <a href="expenses_list.php">
            <i class="fas fa-receipt"></i> Expenses
        </a>
        
        <?php if (is_manager()): ?>
        <a href="debt_list.php">
            <i class="fas fa-money-bill-wave"></i> Debt Management
        </a>
        <?php endif; ?>
        
        <a href="winning_upload.php">
            <i class="fas fa-upload"></i> Upload Winning
        </a>
        
        <?php if (is_manager()): ?>
        <a href="winnings_approve.php">
            <i class="fas fa-check-circle"></i> Approve Winnings
        </a>
        <a href="winnings_list.php">
            <i class="fas fa-trophy"></i> All Winnings
        </a>
        <?php endif; ?>
        
        <?php if (is_manager()): ?>
        <a href="report_staff_enhanced.php">
            <i class="fas fa-chart-bar"></i> Staff Reports
        </a>
        <?php endif; ?>
        
        <a href="upload_passport.php">
            <i class="fas fa-id-card"></i> Upload Passport
        </a>
        
        <a href="change_password.php">
            <i class="fas fa-key"></i> Change Password
        </a>
        
        <a href="logout.php" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>