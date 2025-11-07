<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/winnings.php';
require_login();

$current_user = get_logged_user();
$winning_controller = new WinningController($pdo);

// Handle winning approval/decline from dashboard
if (is_manager() && isset($_GET['action']) && isset($_GET['winning_id'])) {
    $winning_id = (int)$_GET['winning_id'];
    
    if ($_GET['action'] === 'approve') {
        $result = $winning_controller->approve($winning_id, $current_user['id']);
        set_message($result['message'], $result['success'] ? 'success' : 'danger');
    } elseif ($_GET['action'] === 'decline') {
        $result = $winning_controller->decline($winning_id);
        set_message($result['message'], $result['success'] ? 'success' : 'danger');
    }
    redirect('index.php');
}

// Get date filter for winnings (default to today)
$winning_date_filter = isset($_GET['winning_date']) ? $_GET['winning_date'] : date('Y-m-d');
$show_all_winnings = isset($_GET['show_all_winnings']) && $_GET['show_all_winnings'] == '1';

if ($show_all_winnings) {
    $winning_date_filter = null;
}

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
    
    // Get all winnings for admin with date filter
    if ($winning_date_filter) {
        $stats['dashboard_winnings'] = $winning_controller->get_all(null, null, null, $winning_date_filter, $winning_date_filter, null, 100, 0, null);
    } else {
        $stats['dashboard_winnings'] = $winning_controller->get_all(null, null, null, null, null, null, 100, 0, null);
    }
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
    
    // Get all winnings for managers with date filter
    if ($winning_date_filter) {
        $stats['dashboard_winnings'] = $winning_controller->get_all(null, null, null, $winning_date_filter, $winning_date_filter, null, 100, 0, null);
    } else {
        $stats['dashboard_winnings'] = $winning_controller->get_all(null, null, null, null, null, null, 100, 0, null);
    }
} else {
    // Staff sees their own statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_operations WHERE staff_id = ?");
    $stmt->execute([$current_user['id']]);
    $stats['operations'] = $stmt->fetchColumn();
    
    // Get assigned shops for staff with notes
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, s.code, s.address, ssa.notes
        FROM staff_shop_assignments ssa
        INNER JOIN shops s ON ssa.shop_id = s.id
        WHERE ssa.staff_id = ? AND ssa.status = 'active'
        ORDER BY s.code
    ");
    $stmt->execute([$current_user['id']]);
    $stats['assigned_shops'] = $stmt->fetchAll();
    
    // Get all staff debts
    $stmt = $pdo->prepare("
        SELECT d.*
        FROM debts d
        WHERE d.staff_id = ?
        ORDER BY d.debt_date DESC
        LIMIT 10
    ");
    $stmt->execute([$current_user['id']]);
    $stats['debts'] = $stmt->fetchAll();
    
    // Get total outstanding debt (balance column has the remaining amount - only unpaid/partial)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(balance), 0) as total_debt
        FROM debts
        WHERE staff_id = ? AND status IN ('pending', 'partial')
    ");
    $stmt->execute([$current_user['id']]);
    $stats['total_debt'] = $stmt->fetchColumn();
    
    // Get today's winnings
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT w.*, s.name as shop_name
        FROM winnings w
        LEFT JOIN shops s ON w.shop_id = s.id
        WHERE w.staff_id = ? AND w.winning_date = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$current_user['id'], $today]);
    $stats['daily_winnings'] = $stmt->fetchAll();
    
    // Calculate total today's winnings
    $stats['daily_winnings_total'] = array_sum(array_column($stats['daily_winnings'], 'amount'));
    
    // Get today's expenses
    $stmt = $pdo->prepare("
        SELECT e.*, s.name as shop_name
        FROM expenses e
        LEFT JOIN shops s ON e.shop_id = s.id
        WHERE e.staff_id = ? AND e.expense_date = ?
        ORDER BY e.created_at DESC
    ");
    $stmt->execute([$current_user['id'], $today]);
    $stats['daily_expenses'] = $stmt->fetchAll();
    
    // Calculate total today's expenses
    $stats['daily_expenses_total'] = array_sum(array_column($stats['daily_expenses'], 'amount'));
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
                    <div class="stat-value">₦<?php echo format_money($stats['monthly_sales']); ?></div>
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
                
                <!-- Winnings Management Section for Admin -->
                <div style="margin-top: 20px;">
                    <?php include __DIR__ . '/includes/messages.php'; ?>
                    
                    <div style="background: #fefce8; padding: 20px; border-left: 4px solid #eab308; border-radius: 6px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h4 style="margin: 0;">
                                <i class="fas fa-trophy"></i> 
                                <?php echo $show_all_winnings ? 'All Winnings Uploaded by Staff' : 'Today\'s Winnings from All Staff (' . date('M d, Y', strtotime($winning_date_filter)) . ')'; ?>
                            </h4>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <form method="GET" style="display: flex; gap: 10px; margin: 0;">
                                    <input type="date" name="winning_date" value="<?php echo $winning_date_filter ?: date('Y-m-d'); ?>" 
                                           class="form-control" style="width: 180px;">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                </form>
                                <?php if (!$show_all_winnings): ?>
                                    <a href="?show_all_winnings=1" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-list"></i> Show All
                                    </a>
                                <?php else: ?>
                                    <a href="index.php" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-calendar-day"></i> Today Only
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (empty($stats['dashboard_winnings'])): ?>
                            <p style="text-align: center; padding: 40px; color: #64748b;">
                                <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.5;"></i><br><br>
                                No winnings uploaded for the selected date.
                            </p>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="table" style="background: white;">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Staff</th>
                                            <th>Shop</th>
                                            <th>Ticket #</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Receipt</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_amount = 0;
                                        $pending_count = 0;
                                        $approved_count = 0;
                                        foreach ($stats['dashboard_winnings'] as $winning): 
                                            $total_amount += $winning['amount'];
                                            if ($winning['status'] === 'pending') $pending_count++;
                                            if ($winning['status'] === 'approved') $approved_count++;
                                        ?>
                                            <tr>
                                                <td><?php echo date('h:i A', strtotime($winning['created_at'])); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($winning['staff_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($winning['shop_name']); ?></td>
                                                <td style="color: #3b82f6; font-weight: bold;">
                                                    <?php echo htmlspecialchars($winning['ticket_number']); ?>
                                                </td>
                                                <td><strong style="color: #10b981;">₦<?php echo format_money($winning['amount']); ?></strong></td>
                                                <td>
                                                    <?php 
                                                    $badge_color = 'secondary';
                                                    if ($winning['status'] === 'approved') $badge_color = 'success';
                                                    elseif ($winning['status'] === 'declined') $badge_color = 'danger';
                                                    elseif ($winning['status'] === 'pending') $badge_color = 'warning';
                                                    ?>
                                                    <span class="badge badge-<?php echo $badge_color; ?>">
                                                        <?php echo ucfirst($winning['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($winning['receipt_image']): ?>
                                                        <button class="btn btn-sm btn-info" onclick="viewReceipt('uploads/winnings/<?php echo htmlspecialchars($winning['receipt_image']); ?>')" title="View Receipt">
                                                            <i class="fas fa-image"></i> View
                                                        </button>
                                                    <?php else: ?>
                                                        <span style="color: #94a3b8; font-size: 12px;">No receipt</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($winning['status'] === 'pending'): ?>
                                                        <a href="?action=approve&winning_id=<?php echo $winning['id']; ?>" 
                                                           class="btn btn-sm btn-success" 
                                                           onclick="return confirm('Approve this winning?')"
                                                           title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                        <a href="?action=decline&winning_id=<?php echo $winning['id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Decline this winning?')"
                                                           title="Decline">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="color: #94a3b8; font-size: 12px;">
                                                            <?php echo ucfirst($winning['status']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: #f8fafc; font-weight: bold;">
                                            <td colspan="4" style="text-align: right;">
                                                Total: <?php echo count($stats['dashboard_winnings']); ?> winnings
                                                (<?php echo $pending_count; ?> pending, <?php echo $approved_count; ?> approved)
                                            </td>
                                            <td><strong style="color: #10b981;">₦<?php echo format_money($total_amount); ?></strong></td>
                                            <td colspan="3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
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
                
                <!-- Winnings Management Section for Admin/Manager -->
                <div style="margin-top: 20px;">
                    <?php include __DIR__ . '/includes/messages.php'; ?>
                    
                    <div style="background: #fefce8; padding: 20px; border-left: 4px solid #eab308; border-radius: 6px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h4 style="margin: 0;">
                                <i class="fas fa-trophy"></i> 
                                <?php echo $show_all_winnings ? 'All Winnings Uploaded by Staff' : 'Today\'s Winnings from All Staff (' . date('M d, Y', strtotime($winning_date_filter)) . ')'; ?>
                            </h4>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <form method="GET" style="display: flex; gap: 10px; margin: 0;">
                                    <input type="date" name="winning_date" value="<?php echo $winning_date_filter ?: date('Y-m-d'); ?>" 
                                           class="form-control" style="width: 180px;">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                </form>
                                <?php if (!$show_all_winnings): ?>
                                    <a href="?show_all_winnings=1" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-list"></i> Show All
                                    </a>
                                <?php else: ?>
                                    <a href="index.php" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-calendar-day"></i> Today Only
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (empty($stats['dashboard_winnings'])): ?>
                            <p style="text-align: center; padding: 40px; color: #64748b;">
                                <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.5;"></i><br><br>
                                No winnings uploaded for the selected date.
                            </p>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="table" style="background: white;">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Staff</th>
                                            <th>Shop</th>
                                            <th>Ticket #</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Receipt</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_amount = 0;
                                        $pending_count = 0;
                                        $approved_count = 0;
                                        foreach ($stats['dashboard_winnings'] as $winning): 
                                            $total_amount += $winning['amount'];
                                            if ($winning['status'] === 'pending') $pending_count++;
                                            if ($winning['status'] === 'approved') $approved_count++;
                                        ?>
                                            <tr>
                                                <td><?php echo date('h:i A', strtotime($winning['created_at'])); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($winning['staff_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($winning['shop_name']); ?></td>
                                                <td style="color: #3b82f6; font-weight: bold;">
                                                    <?php echo htmlspecialchars($winning['ticket_number']); ?>
                                                </td>
                                                <td><strong style="color: #10b981;">₦<?php echo format_money($winning['amount']); ?></strong></td>
                                                <td>
                                                    <?php 
                                                    $badge_color = 'secondary';
                                                    if ($winning['status'] === 'approved') $badge_color = 'success';
                                                    elseif ($winning['status'] === 'declined') $badge_color = 'danger';
                                                    elseif ($winning['status'] === 'pending') $badge_color = 'warning';
                                                    ?>
                                                    <span class="badge badge-<?php echo $badge_color; ?>">
                                                        <?php echo ucfirst($winning['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($winning['receipt_image']): ?>
                                                        <button class="btn btn-sm btn-info" onclick="viewReceipt('uploads/winnings/<?php echo htmlspecialchars($winning['receipt_image']); ?>')" title="View Receipt">
                                                            <i class="fas fa-image"></i> View
                                                        </button>
                                                    <?php else: ?>
                                                        <span style="color: #94a3b8; font-size: 12px;">No receipt</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($winning['status'] === 'pending'): ?>
                                                        <a href="?action=approve&winning_id=<?php echo $winning['id']; ?>" 
                                                           class="btn btn-sm btn-success" 
                                                           onclick="return confirm('Approve this winning?')"
                                                           title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                        <a href="?action=decline&winning_id=<?php echo $winning['id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Decline this winning?')"
                                                           title="Decline">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="color: #94a3b8; font-size: 12px;">
                                                            <?php echo ucfirst($winning['status']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: #f8fafc; font-weight: bold;">
                                            <td colspan="4" style="text-align: right;">
                                                Total: <?php echo count($stats['dashboard_winnings']); ?> winnings
                                                (<?php echo $pending_count; ?> pending, <?php echo $approved_count; ?> approved)
                                            </td>
                                            <td><strong style="color: #10b981;">₦<?php echo format_money($total_amount); ?></strong></td>
                                            <td colspan="3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
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
                        <a href="expenses_create.php" class="btn btn-warning btn-sm">
                            <i class="fas fa-receipt"></i> Add Expense
                        </a>
                    </div>
                </div>
                
                <!-- Assigned Shops Section for Staff -->
                <div style="margin-top: 20px; padding: 15px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 6px;">
                    <h4 style="margin-bottom: 15px;"><i class="fas fa-store"></i> My Assigned Shops</h4>
                    <?php if (empty($stats['assigned_shops'])): ?>
                        <p style="color: #64748b;">
                            <i class="fas fa-info-circle"></i> No shops assigned yet. Please contact your manager.
                        </p>
                    <?php else: ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                            <?php foreach ($stats['assigned_shops'] as $shop): ?>
                                <div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #e5e7eb;">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                        <div style="background: #3b82f6; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            <?php echo strtoupper(substr($shop['code'], 0, 1)); ?>
                                        </div>
                                        <div style="flex: 1;">
                                            <strong style="color: #1e293b; display: block;"><?php echo htmlspecialchars($shop['code']); ?></strong>
                                            <small style="color: #64748b;"><?php echo htmlspecialchars($shop['name']); ?></small>
                                        </div>
                                    </div>
                                    <?php if (!empty($shop['notes'])): ?>
                                        <div style="margin: 10px 0; padding: 8px 10px; background: #fef3c7; border-left: 3px solid #f59e0b; font-size: 12px; color: #92400e; border-radius: 4px;">
                                            <i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($shop['notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($shop['address']): ?>
                                        <p style="font-size: 12px; color: #64748b; margin: 5px 0 0 0;">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($shop['address']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Staff Debts Section -->
                <div style="margin-top: 20px; padding: 15px; background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 6px;">
                    <h4 style="margin-bottom: 15px;">
                        <i class="fas fa-exclamation-triangle"></i> My Outstanding Debts
                        <?php if ($stats['total_debt'] > 0): ?>
                            <span style="float: right; font-size: 18px; color: #ef4444;">
                                ₦<?php echo format_money($stats['total_debt']); ?>
                            </span>
                        <?php endif; ?>
                    </h4>
                    <?php if (empty($stats['debts'])): ?>
                        <p style="color: #10b981;">
                            <i class="fas fa-check-circle"></i> No outstanding debts. You're all clear!
                        </p>
                    <?php else: ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <table class="table" style="font-size: 14px;">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Paid</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['debts'] as $debt): ?>
                                        <tr>
                                            <td><?php echo format_date($debt['debt_date']); ?></td>
                                            <td style="font-size: 12px;"><?php echo htmlspecialchars(substr($debt['description'] ?? 'Debt', 0, 30)); ?></td>
                                            <td>₦<?php echo format_money($debt['amount']); ?></td>
                                            <td style="color: #10b981;">₦<?php echo format_money($debt['amount_paid']); ?></td>
                                            <td style="color: #ef4444; font-weight: bold;">₦<?php echo format_money($debt['balance']); ?></td>
                                            <td>
                                                <?php 
                                                $badge_color = $debt['status'] === 'pending' ? '#ef4444' : '#f59e0b';
                                                ?>
                                                <span style="padding: 3px 8px; background: <?php echo $badge_color; ?>; color: white; border-radius: 4px; font-size: 11px;">
                                                    <?php echo ucfirst($debt['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p style="margin-top: 10px; font-size: 13px; color: #64748b;">
                            <a href="debt_list.php">View all debts →</a>
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Today's Winnings Section -->
                <div style="margin-top: 20px; padding: 15px; background: #f0fdf4; border-left: 4px solid #10b981; border-radius: 6px;">
                    <h4 style="margin-bottom: 15px;">
                        <i class="fas fa-trophy"></i> Today's Winnings (<?php echo date('M d, Y'); ?>)
                        <?php if ($stats['daily_winnings_total'] > 0): ?>
                            <span style="float: right; font-size: 18px; color: #10b981;">
                                ₦<?php echo format_money($stats['daily_winnings_total']); ?>
                            </span>
                        <?php endif; ?>
                    </h4>
                    <?php if (empty($stats['daily_winnings'])): ?>
                        <p style="color: #64748b;">
                            <i class="fas fa-info-circle"></i> No winnings uploaded today. <a href="winning_upload.php">Upload a winning</a>
                        </p>
                    <?php else: ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <table class="table" style="font-size: 14px;">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Shop</th>
                                        <th>Ticket #</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['daily_winnings'] as $winning): ?>
                                        <tr>
                                            <td><?php echo date('h:i A', strtotime($winning['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($winning['shop_name']); ?></td>
                                            <td><?php echo htmlspecialchars($winning['ticket_number']); ?></td>
                                            <td style="color: #10b981; font-weight: bold;">₦<?php echo format_money($winning['amount']); ?></td>
                                            <td>
                                                <?php 
                                                $badge_color = '#64748b'; // default
                                                if ($winning['status'] === 'pending') $badge_color = '#f59e0b';
                                                elseif ($winning['status'] === 'approved') $badge_color = '#10b981';
                                                elseif ($winning['status'] === 'declined') $badge_color = '#ef4444';
                                                ?>
                                                <span style="padding: 3px 8px; background: <?php echo $badge_color; ?>; color: white; border-radius: 4px; font-size: 11px;">
                                                    <?php echo ucfirst($winning['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p style="margin-top: 10px; font-size: 13px; color: #64748b;">
                            <a href="winning_upload.php">Upload more winnings →</a>
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Today's Expenses Section -->
                <div style="margin-top: 20px; padding: 15px; background: #fef9c3; border-left: 4px solid #eab308; border-radius: 6px;">
                    <h4 style="margin-bottom: 15px;">
                        <i class="fas fa-money-bill-wave"></i> Today's Expenses (<?php echo date('M d, Y'); ?>)
                        <?php if ($stats['daily_expenses_total'] > 0): ?>
                            <span style="float: right; font-size: 18px; color: #eab308;">
                                ₦<?php echo format_money($stats['daily_expenses_total']); ?>
                            </span>
                        <?php endif; ?>
                    </h4>
                    <?php if (empty($stats['daily_expenses'])): ?>
                        <p style="color: #64748b;">
                            <i class="fas fa-info-circle"></i> No expenses recorded today. <a href="expenses_create.php">Add an expense</a>
                        </p>
                    <?php else: ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <table class="table" style="font-size: 14px;">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Shop</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['daily_expenses'] as $expense): ?>
                                        <tr>
                                            <td><?php echo date('h:i A', strtotime($expense['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($expense['shop_name']); ?></td>
                                            <td><?php echo htmlspecialchars($expense['category']); ?></td>
                                            <td style="color: #eab308; font-weight: bold;">₦<?php echo format_money($expense['amount']); ?></td>
                                            <td style="font-size: 12px; color: #64748b;">
                                                <?php echo htmlspecialchars(substr($expense['description'], 0, 30)) . (strlen($expense['description']) > 30 ? '...' : ''); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p style="margin-top: 10px; font-size: 13px; color: #64748b;">
                            <a href="expenses_list.php">View all expenses →</a>
                        </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Image Modal -->
<div id="receiptModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); overflow: auto;">
    <span onclick="closeReceiptModal()" style="position: absolute; top: 20px; right: 40px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer;">&times;</span>
    <img id="receiptImage" style="margin: auto; display: block; max-width: 90%; max-height: 90%; margin-top: 50px; border-radius: 8px;">
</div>

<script src="assets/js/app.js"></script>
<script>
function viewReceipt(imagePath) {
    document.getElementById('receiptModal').style.display = 'block';
    document.getElementById('receiptImage').src = imagePath;
}

function closeReceiptModal() {
    document.getElementById('receiptModal').style.display = 'none';
}

// Close modal when clicking outside the image
document.getElementById('receiptModal').onclick = function(event) {
    if (event.target.id === 'receiptModal') {
        closeReceiptModal();
    }
}

// Close modal with ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeReceiptModal();
    }
});
</script>
</body>
</html>