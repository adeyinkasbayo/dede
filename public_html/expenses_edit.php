<?php
$page_title = 'Edit Expense';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/expenses.php';
require_login();

$current_user = get_logged_user();
$expense_controller = new ExpenseController($pdo);

$expense_id = $_GET['id'] ?? null;
if (!$expense_id) {
    set_message('Expense not found', 'danger');
    redirect('expenses_list.php');
}

$expense = $expense_controller->get_by_id($expense_id);
if (!$expense) {
    set_message('Expense not found', 'danger');
    redirect('expenses_list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'shop_id' => $_POST['shop_id'] ?? $expense['shop_id'],
        'category' => sanitize_input($_POST['category'] ?? ''),
        'description' => sanitize_input($_POST['description'] ?? ''),
        'amount' => $_POST['amount'] ?? 0,
        'expense_date' => $_POST['expense_date'] ?? date('Y-m-d'),
        'receipt_number' => sanitize_input($_POST['receipt_number'] ?? ''),
        'paid_to' => sanitize_input($_POST['paid_to'] ?? ''),
        'payment_method' => $_POST['payment_method'] ?? 'cash',
        'status' => $_POST['status'] ?? $expense['status']
    ];
    
    $result = $expense_controller->update($expense_id, $data);
    
    if ($result['success']) {
        set_message($result['message'], 'success');
        redirect('expenses_list.php');
    } else {
        set_message($result['message'], 'danger');
    }
}

$shops = get_accessible_shops($pdo);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-edit"></i> Edit Expense</h1>
        </div>
        <div class="header-right">
            <a href="expenses_list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <div class="card-header">
                <h3>Edit Expense Details</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php if (is_manager()): ?>
                    <div class="form-group">
                        <label for="shop_id">Shop *</label>
                        <select id="shop_id" name="shop_id" class="form-control" required>
                            <?php foreach ($shops as $shop): ?>
                                <option value="<?php echo $shop['id']; ?>"
                                    <?php echo ($expense['shop_id'] == $shop['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($shop['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="expense_date">Expense Date *</label>
                        <input type="date" id="expense_date" name="expense_date" class="form-control" 
                               required value="<?php echo $expense['expense_date']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="Rent" <?php echo ($expense['category'] === 'Rent') ? 'selected' : ''; ?>>Rent</option>
                            <option value="Utilities" <?php echo ($expense['category'] === 'Utilities') ? 'selected' : ''; ?>>Utilities</option>
                            <option value="Supplies" <?php echo ($expense['category'] === 'Supplies') ? 'selected' : ''; ?>>Supplies</option>
                            <option value="Maintenance" <?php echo ($expense['category'] === 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="Salaries" <?php echo ($expense['category'] === 'Salaries') ? 'selected' : ''; ?>>Salaries</option>
                            <option value="Transport" <?php echo ($expense['category'] === 'Transport') ? 'selected' : ''; ?>>Transport</option>
                            <option value="Marketing" <?php echo ($expense['category'] === 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                            <option value="Other" <?php echo ($expense['category'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" class="form-control" 
                                  required><?php echo htmlspecialchars($expense['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Amount *</label>
                        <input type="number" id="amount" name="amount" class="form-control" 
                               step="0.01" required value="<?php echo $expense['amount']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="paid_to">Paid To</label>
                        <input type="text" id="paid_to" name="paid_to" class="form-control" 
                               value="<?php echo htmlspecialchars($expense['paid_to'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="receipt_number">Receipt Number</label>
                        <input type="text" id="receipt_number" name="receipt_number" class="form-control" 
                               value="<?php echo htmlspecialchars($expense['receipt_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">Payment Method *</label>
                        <select id="payment_method" name="payment_method" class="form-control" required>
                            <option value="cash" <?php echo ($expense['payment_method'] === 'cash') ? 'selected' : ''; ?>>Cash</option>
                            <option value="bank" <?php echo ($expense['payment_method'] === 'bank') ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="mobile_money" <?php echo ($expense['payment_method'] === 'mobile_money') ? 'selected' : ''; ?>>Mobile Money</option>
                            <option value="other" <?php echo ($expense['payment_method'] === 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <?php if (is_manager()): ?>
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="pending" <?php echo ($expense['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo ($expense['status'] === 'approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo ($expense['status'] === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Expense
                        </button>
                        <a href="expenses_list.php" class="btn btn-secondary">
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