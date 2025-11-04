<?php
$page_title = 'Staff Reports';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/reports.php';
require_once __DIR__ . '/src/controllers/user.php';
require_permission(['admin', 'manager']);

$current_user = get_current_user();
$report_controller = new ReportController($pdo);
$user_controller = new UserController($pdo);

$report_data = null;
$selected_staff_id = null;
$start_date = null;
$end_date = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_staff_id = $_POST['staff_id'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    
    if ($selected_staff_id) {
        $report_data = $report_controller->get_staff_performance($selected_staff_id, $start_date, $end_date);
        
        if (!$report_data) {
            set_message('Unable to generate report', 'danger');
        }
    } else {
        set_message('Please select a staff member', 'danger');
    }
}

// Get staff members
$shop_id = is_admin() ? null : $current_user['shop_id'];
$staff_members = $user_controller->get_all('staff', $shop_id);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-chart-bar"></i> Staff Reports</h1>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h3>Generate Staff Performance Report</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="flex gap-10" style="align-items: flex-end;">
                    <div class="form-group" style="flex: 2; margin-bottom: 0;">
                        <label for="staff_id">Select Staff *</label>
                        <select id="staff_id" name="staff_id" class="form-control" required>
                            <option value="">-- Select Staff Member --</option>
                            <?php foreach ($staff_members as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>"
                                    <?php echo ($selected_staff_id == $staff['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($staff['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control"
                               value="<?php echo $start_date ?? ''; ?>">
                    </div>
                    
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control"
                               value="<?php echo $end_date ?? ''; ?>">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-chart-line"></i> Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($report_data): ?>
        <div class="card">
            <div class="card-header flex-between">
                <h3>Performance Report: <?php echo htmlspecialchars($report_data['staff']['full_name']); ?></h3>
                <button onclick="window.print()" class="btn btn-sm btn-secondary">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
            <div class="card-body">
                <!-- Staff Information -->
                <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <h4 style="margin-bottom: 15px;">Staff Information</h4>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div>
                            <strong>Name:</strong> <?php echo htmlspecialchars($report_data['staff']['full_name']); ?>
                        </div>
                        <div>
                            <strong>Username:</strong> <?php echo htmlspecialchars($report_data['staff']['username']); ?>
                        </div>
                        <div>
                            <strong>Shop:</strong> <?php echo htmlspecialchars($report_data['staff']['shop_name'] ?? 'Not assigned'); ?>
                        </div>
                        <div>
                            <strong>Email:</strong> <?php echo htmlspecialchars($report_data['staff']['email'] ?? '-'); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Statistics -->
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card" style="border-left-color: #2563eb;">
                        <h4>Total Operations</h4>
                        <div class="stat-value"><?php echo $report_data['summary']['operations_count']; ?></div>
                    </div>
                    
                    <div class="stat-card" style="border-left-color: #10b981;">
                        <h4>Total Sales</h4>
                        <div class="stat-value">$<?php echo format_money($report_data['summary']['total_sales']); ?></div>
                    </div>
                    
                    <div class="stat-card" style="border-left-color: #ef4444;">
                        <h4>Total Expenses</h4>
                        <div class="stat-value">$<?php echo format_money($report_data['summary']['total_expenses']); ?></div>
                    </div>
                    
                    <div class="stat-card" style="border-left-color: #8b5cf6;">
                        <h4>Net Amount</h4>
                        <div class="stat-value">$<?php echo format_money($report_data['summary']['net']); ?></div>
                    </div>
                </div>
                
                <!-- Operations Table -->
                <h4 style="margin-bottom: 15px;">Daily Operations</h4>
                <?php if (empty($report_data['operations'])): ?>
                    <p style="text-align: center; padding: 40px; color: #64748b;">
                        No operations found for the selected period.
                    </p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Opening Balance</th>
                                <th>Sales</th>
                                <th>Expenses</th>
                                <th>Closing Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['operations'] as $operation): ?>
                            <tr>
                                <td><?php echo format_date($operation['operation_date']); ?></td>
                                <td>$<?php echo format_money($operation['opening_balance']); ?></td>
                                <td>$<?php echo format_money($operation['total_sales']); ?></td>
                                <td>$<?php echo format_money($operation['total_expenses']); ?></td>
                                <td>$<?php echo format_money($operation['closing_balance']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>