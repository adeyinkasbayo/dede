<?php
$page_title = 'Expenses';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/expenses.php';
require_login();

$current_user = get_current_user();
$expense_controller = new ExpenseController($pdo);

// Handle approve
if (isset($_GET['approve']) && is_manager()) {
    $result = $expense_controller->approve($_GET['approve'], $current_user['id']);
    set_message($result['message'], $result['success'] ? 'success' : 'danger');
    redirect('expenses_list.php');
}

// Handle delete
if (isset($_GET['delete']) && is_manager()) {
    $result = $expense_controller->delete($_GET['delete']);
    set_message($result['message'], $result['success'] ? 'success' : 'danger');
    redirect('expenses_list.php');
}

$shop_id = is_admin() ? null : $current_user['shop_id'];
$expenses = $expense_controller->get_all($shop_id);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-money-bill-wave"></i> Expenses</h1>
        </div>
        <div class="header-right">
            <a href="expenses_create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Expense
            </a>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>All Expenses</h3>
            </div>
            <div class="card-body">
                <?php if (empty($expenses)): ?>
                    <p style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fas fa-money-bill-wave" style="font-size: 48px; opacity: 0.5;"></i><br><br>
                        No expenses recorded. <a href="expenses_create.php">Add your first expense</a>
                    </p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Shop</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?php echo format_date($expense['expense_date']); ?></td>
                                <td><?php echo htmlspecialchars($expense['shop_name']); ?></td>
                                <td><strong><?php echo htmlspecialchars($expense['category']); ?></strong></td>
                                <td><?php echo htmlspecialchars(substr($expense['description'], 0, 50)) . '...'; ?></td>
                                <td><strong>$<?php echo format_money($expense['amount']); ?></strong></td>
                                <td>
                                    <?php 
                                    $status_badge = 'secondary';
                                    if ($expense['status'] === 'approved') $status_badge = 'success';
                                    elseif ($expense['status'] === 'pending') $status_badge = 'warning';
                                    elseif ($expense['status'] === 'rejected') $status_badge = 'danger';
                                    ?>
                                    <span class="badge badge-<?php echo $status_badge; ?>">
                                        <?php echo ucfirst($expense['status']); ?>
                                    </span>
                                </td>
                                <td class="table-actions">
                                    <a href="expenses_edit.php?id=<?php echo $expense['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if (is_manager() && $expense['status'] === 'pending'): ?>
                                        <a href="?approve=<?php echo $expense['id']; ?>" 
                                           class="btn btn-sm btn-success" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (is_manager()): ?>
                                        <a href="?delete=<?php echo $expense['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirmDelete('Delete this expense?')" title="Delete">
                                            <i class="fas fa-trash"></i>
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