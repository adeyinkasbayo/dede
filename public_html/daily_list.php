<?php
$page_title = 'Daily Operations';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/daily.php';
require_login();

$current_user = get_current_user();
$daily_controller = new DailyController($pdo);

$shop_id = is_admin() ? null : $current_user['shop_id'];
$operations = $daily_controller->get_all($shop_id);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-calendar-day"></i> Daily Operations</h1>
        </div>
        <div class="header-right">
            <a href="daily_create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New
            </a>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Daily Operations Records</h3>
            </div>
            <div class="card-body">
                <?php if (empty($operations)): ?>
                    <p style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fas fa-calendar-day" style="font-size: 48px; opacity: 0.5;"></i><br><br>
                        No operations recorded yet. <a href="daily_create.php">Add your first operation</a>
                    </p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Shop</th>
                                <th>Staff</th>
                                <th>Opening</th>
                                <th>Sales</th>
                                <th>Expenses</th>
                                <th>Closing</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($operations as $operation): ?>
                            <tr>
                                <td><?php echo format_date($operation['operation_date']); ?></td>
                                <td><?php echo htmlspecialchars($operation['shop_name']); ?></td>
                                <td><?php echo htmlspecialchars($operation['staff_name']); ?></td>
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
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>