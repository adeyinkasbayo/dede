<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/src/init.php';
require_login();

$current_user = get_logged_user();

// Get statistics based on role
$stats = [];

if (is_admin()) {
    // Admin sees all statistics
    $stmt = $pdo->query("SELECT COUNT(*) FROM shops WHERE status = 'active'");
    $stats['shops'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $stats['staff'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM expenses");
    $stats['pending_expenses'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM winnings WHERE status = 'pending'");
    $stats['pending_winnings'] = $stmt->fetchColumn();
} elseif (is_manager()) {
    // Manager sees shop-specific statistics
    $shop_id = $current_user['shop_id'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE shop_id = ? AND status = 'active'");
    $stmt->execute([$shop_id]);
    $stats['staff'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE shop_id = ? AND status = 'pending'");
    $stmt->execute([$shop_id]);
    $stats['pending_expenses'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM winnings WHERE shop_id = ? AND status = 'pending'");
    $stmt->execute([$shop_id]);
    $stats['pending_winnings'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT SUM(total_sales) FROM daily_operations WHERE shop_id = ? AND operation_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stmt->execute([$shop_id]);
    $stats['monthly_sales'] = $stmt->fetchColumn() ?? 0;
} else {
    // Staff sees their own statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_operations WHERE staff_id = ?");
    $stmt->execute([$current_user['id']]);
    $stats['operations'] = $stmt->fetchColumn();
    
    // Get assigned shops for staff
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, s.code, s.address
        FROM staff_shop_assignments ssa
        INNER JOIN shops s ON ssa.shop_id = s.id
        WHERE ssa.staff_id = ? AND ssa.status = 'active'
        ORDER BY s.code
    ");
    $stmt->execute([$current_user['id']]);
    $stats['assigned_shops'] = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-home"></i> Dashboard</h1>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($current_user['username'], 0, 1)); ?></div>
                <div>
                    <strong><?php echo htmlspecialchars($current_user['username']); ?></strong><br>
                    <small><?php echo get_user_role_name($current_user['role']); ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="stats-grid">
            <?php if (is_admin()): ?>
                <div class="stat-card" style="border-left-color: #2563eb;">
                    <h4>Active Shops</h4>
                    <div class="stat-value"><?php echo $stats['shops']; ?></div>
                </div>
                
                <div class="stat-card" style="border-left-color: #10b981;">
                    <h4>Total Staff</h4>
                    <div class="stat-value"><?php echo $stats['staff']; ?></div>
                </div>
                
                <div class="stat-card" style="border-left-color: #f59e0b;">
                    <h4>Pending Expenses</h4>
                    <div class="stat-value"><?php echo $stats['pending_expenses']; ?></div>
                </div>
                
                <div class="stat-card" style="border-left-color: #8b5cf6;">
                    <h4>Pending Winnings</h4>
                    <div class="stat-value"><?php echo $stats['pending_winnings']; ?></div>
                </div>
            <?php elseif (is_manager()): ?>
                <div class="stat-card" style="border-left-color: #10b981;">
                    <h4>Shop Staff</h4>
                    <div class="stat-value"><?php echo $stats['staff']; ?></div>
                </div>
                
                <div class="stat-card" style="border-left-color: #f59e0b;">
                    <h4>Pending Expenses</h4>
                    <div class="stat-value"><?php echo $stats['pending_expenses']; ?></div>
                </div>
                
                <div class="stat-card" style="border-left-color: #8b5cf6;">
                    <h4>Pending Winnings</h4>
                    <div class="stat-value"><?php echo $stats['pending_winnings']; ?></div>
                </div>
                
                <div class="stat-card" style="border-left-color: #06b6d4;">
                    <h4>Monthly Sales</h4>
                    <div class="stat-value">$<?php echo format_money($stats['monthly_sales']); ?></div>
                </div>
            <?php else: ?>
                <div class="stat-card" style="border-left-color: #2563eb;">
                    <h4>My Operations</h4>
                    <div class="stat-value"><?php echo $stats['operations']; ?></div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle"></i> Welcome to <?php echo APP_NAME; ?></h3>
            </div>
            <div class="card-body">
                <p style="font-size: 16px; line-height: 1.8;">
                    Welcome, <strong><?php echo htmlspecialchars($current_user['username']); ?></strong>!
                </p>
                <p style="margin-top: 15px;">
                    This system helps you manage shops, staff, daily operations, expenses, and winnings efficiently.
                    Use the navigation menu on the left to access different features.
                </p>
                
                <?php if (is_admin()): ?>
                <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-left: 4px solid #2563eb; border-radius: 6px;">
                    <h4 style="margin-bottom: 10px;"><i class="fas fa-crown"></i> Admin Quick Links</h4>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="shop_create.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add New Shop
                        </a>
                        <a href="staff_create.php" class="btn btn-success btn-sm">
                            <i class="fas fa-user-plus"></i> Add New Staff
                        </a>
                        <a href="expenses_list.php" class="btn btn-warning btn-sm">
                            <i class="fas fa-file-invoice"></i> Review Expenses
                        </a>
                    </div>
                </div>
                <?php elseif (is_manager()): ?>
                <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-left: 4px solid #10b981; border-radius: 6px;">
                    <h4 style="margin-bottom: 10px;"><i class="fas fa-briefcase"></i> Manager Quick Links</h4>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="staff_create.php" class="btn btn-success btn-sm">
                            <i class="fas fa-user-plus"></i> Add Staff
                        </a>
                        <a href="daily_create.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-calendar-plus"></i> Add Daily Operation
                        </a>
                        <a href="report_staff.php" class="btn btn-info btn-sm">
                            <i class="fas fa-chart-bar"></i> View Reports
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-left: 4px solid #06b6d4; border-radius: 6px;">
                    <h4 style="margin-bottom: 10px;"><i class="fas fa-user"></i> Staff Quick Links</h4>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="daily_create.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-calendar-plus"></i> Record Daily Operation
                        </a>
                        <a href="winning_upload.php" class="btn btn-success btn-sm">
                            <i class="fas fa-trophy"></i> Upload Winning
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>