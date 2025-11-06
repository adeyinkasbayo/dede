<?php
$page_title = 'Debt Management';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/debt.php';
require_once __DIR__ . '/src/controllers/user.php';
require_permission(['admin', 'manager']);

$current_user = get_logged_user();
$debt_controller = new DebtController($pdo);
$user_controller = new UserController($pdo);

$staff_id = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : null;
$status_filter = isset($_GET['status']) ? $_GET['status'] : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;

$shop_id = is_admin() ? null : $current_user['shop_id'];
$debts = $debt_controller->get_all($staff_id, $shop_id, $status_filter, $date_from, $date_to);

$staff_list = $user_controller->get_all('staff', $shop_id);

$total_debt = 0;
$total_paid = 0;
$total_balance = 0;
foreach ($debts as $debt) {
    $total_debt += $debt['amount'];
    $total_paid += $debt['total_paid'];
    $total_balance += $debt['balance'];
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-money-bill-wave"></i> Debt Management</h1>
        </div>
        <div class="header-right">
            <a href="debt_create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Debt
            </a>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-body">
                <form method="GET" action="" class="flex gap-10" style="flex-wrap: wrap; align-items: flex-end;">
                    <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                        <label for="staff_id">Filter by Staff</label>
                        <select id="staff_id" name="staff_id" class="form-control">
                            <option value="">All Staff</option>
                            <?php foreach ($staff_list as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>" <?php echo ($staff_id == $staff['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($staff['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                        <label for="date_from">From Date</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" 
                               value="<?php echo htmlspecialchars($date_from ?? ''); ?>">
                    </div>
                    
                    <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                        <label for="date_to">To Date</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" 
                               value="<?php echo htmlspecialchars($date_to ?? ''); ?>">
                    </div>
                    
                    <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="unpaid" <?php echo ($status_filter === 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                            <option value="partially_paid" <?php echo ($status_filter === 'partially_paid') ? 'selected' : ''; ?>>Partially Paid</option>
                            <option value="paid" <?php echo ($status_filter === 'paid') ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="debt_list.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="stats-grid" style="margin-bottom: 20px;">
            <div class="stat-card" style="border-left-color: #ef4444;">
                <h4>Total Debt</h4>
                <div class="stat-value">$<?php echo format_money($total_debt); ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #10b981;">
                <h4>Total Paid</h4>
                <div class="stat-value">$<?php echo format_money($total_paid); ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #f59e0b;">
                <h4>Total Balance</h4>
                <div class="stat-value">$<?php echo format_money($total_balance); ?></div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Debts List</h3>
            </div>
            <div class="card-body">
                <?php if (empty($debts)): ?>
                    <p style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fas fa-money-bill-wave" style="font-size: 48px; opacity: 0.5;"></i><br><br>
                        No debts found.
                    </p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Staff</th>
                                <th>Shop</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($debts as $debt): ?>
                            <tr>
                                <td><?php echo format_date($debt['debt_date']); ?></td>
                                <td><strong><?php echo htmlspecialchars($debt['staff_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($debt['shop_name']); ?></td>
                                <td>$<?php echo format_money($debt['amount']); ?></td>
                                <td style="color: var(--success-color);">$<?php echo format_money($debt['total_paid']); ?></td>
                                <td style="color: var(--danger-color);"><strong>$<?php echo format_money($debt['balance']); ?></strong></td>
                                <td>
                                    <?php 
                                    $badge = 'danger';
                                    if ($debt['status'] === 'paid') $badge = 'success';
                                    elseif ($debt['status'] === 'partially_paid') $badge = 'warning';
                                    ?>
                                    <span class="badge badge-<?php echo $badge; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $debt['status'])); ?>
                                    </span>
                                </td>
                                <td class="table-actions">
                                    <?php if ($debt['status'] !== 'paid'): ?>
                                        <a href="debt_payment.php?id=<?php echo $debt['id']; ?>" 
                                           class="btn btn-sm btn-success" title="Record Payment">
                                            <i class="fas fa-dollar-sign"></i> Pay
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
