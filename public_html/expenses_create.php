<?php
$page_title = 'Add Expense';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/expenses.php';
require_login();

$current_user = get_current_user();
$expense_controller = new ExpenseController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'shop_id' => $_POST['shop_id'] ?? $current_user['shop_id'],
        'category' => sanitize_input($_POST['category'] ?? ''),
        'description' => sanitize_input($_POST['description'] ?? ''),
        'amount' => $_POST['amount'] ?? 0,
        'expense_date' => $_POST['expense_date'] ?? date('Y-m-d'),
        'receipt_number' => sanitize_input($_POST['receipt_number'] ?? ''),
        'paid_to' => sanitize_input($_POST['paid_to'] ?? ''),
        'payment_method' => $_POST['payment_method'] ?? 'cash',
        'status' => 'pending',
        'created_by' => $current_user['id']
    ];
    
    $result = $expense_controller->create($data);
    
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
            <h1><i class="fas fa-plus"></i> Add Expense</h1>
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
                <h3>Expense Details</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php if (is_manager()): ?>
                    <div class="form-group">
                        <label for="shop_id">Shop *</label>
                        <select id="shop_id" name="shop_id" class="form-control" required>
                            <?php foreach ($shops as $shop): ?>
                                <option value="<?php echo $shop['id']; ?>"
                                    <?php echo ($current_user['shop_id'] == $shop['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($shop['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="expense_date">Expense Date *</label>
                        <input type="date" id="expense_date" name="expense_date" class="form-control" 
                               required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="Rent">Rent</option>
                            <option value="Utilities">Utilities</option>
                            <option value="Supplies">Supplies</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Salaries">Salaries</option>
                            <option value="Transport">Transport</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" class="form-control" 
                                  placeholder="Enter expense description" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Amount *</label>
                        <input type="number" id="amount" name="amount" class="form-control" 
                               step="0.01" placeholder="0.00" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="paid_to">Paid To</label>
                        <input type="text" id="paid_to" name="paid_to" class="form-control" 
                               placeholder="Recipient/vendor name">
                    </div>
                    
                    <div class="form-group">
                        <label for="receipt_number">Receipt Number</label>
                        <input type="text" id="receipt_number" name="receipt_number" class="form-control" 
                               placeholder="Receipt/invoice number">
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">Payment Method *</label>
                        <select id="payment_method" name="payment_method" class="form-control" required>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Expense
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