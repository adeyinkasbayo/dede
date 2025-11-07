<?php
$page_title = 'Record Debt Payment';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/debt.php';
require_permission(['admin', 'manager']);

$current_user = get_logged_user();
$debt_controller = new DebtController($pdo);

$debt_id = $_GET['id'] ?? null;
if (!$debt_id) {
    set_message('Debt not found', 'danger');
    redirect('debt_list.php');
}

$debt = $debt_controller->get_by_id($debt_id);
if (!$debt) {
    set_message('Debt not found', 'danger');
    redirect('debt_list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? 0;
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $notes = sanitize_input($_POST['notes'] ?? '');
    
    if ($amount <= 0) {
        set_message('Payment amount must be greater than zero', 'danger');
    } elseif ($amount > $debt['balance']) {
        set_message('Payment amount cannot exceed debt balance', 'danger');
    } else {
        $result = $debt_controller->record_payment($debt_id, $amount, $payment_date, $notes, $current_user['id']);
        
        if ($result['success']) {
            set_message($result['message'], 'success');
            redirect('debt_list.php');
        } else {
            set_message($result['message'], 'danger');
        }
    }
}

$payments = $debt_controller->get_payments($debt_id);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-dollar-sign"></i> Record Debt Payment</h1>
        </div>
        <div class="header-right">
            <a href="debt_list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h3>Debt Information</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div>
                        <strong>Staff:</strong> <?php echo htmlspecialchars($debt['staff_name']); ?>
                    </div>
                    <div>
                        <strong>Shop:</strong> <?php echo htmlspecialchars($debt['shop_name']); ?>
                    </div>
                    <div>
                        <strong>Date:</strong> <?php echo format_date($debt['debt_date']); ?>
                    </div>
                    <div>
                        <strong>Original Amount:</strong> <span style="color: var(--danger-color);">₦<?php echo format_money($debt['amount']); ?></span>
                    </div>
                    <div>
                        <strong>Total Paid:</strong> <span style="color: var(--success-color);">₦<?php echo format_money($debt['total_paid']); ?></span>
                    </div>
                    <div>
                        <strong>Balance:</strong> <span style="color: var(--warning-color); font-size: 18px; font-weight: bold;">₦<?php echo format_money($debt['balance']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($debt['status'] !== 'paid'): ?>
        <div class="card" style="max-width: 600px; margin: 0 auto 20px;">
            <div class="card-header">
                <h3>Record Payment</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="payment_date">Payment Date *</label>
                        <input type="date" id="payment_date" name="payment_date" class="form-control" 
                               required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Amount *</label>
                        <input type="number" id="amount" name="amount" class="form-control" 
                               step="0.01" placeholder="₦0.00" required 
                               max="<?php echo $debt['balance']; ?>">
                        <small style="color: #64748b;">Maximum: ₦<?php echo format_money($debt['balance']); ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" 
                                  placeholder="Payment notes"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Record Payment
                        </button>
                        <a href="debt_list.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($payments)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Payment History</h3>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Recorded By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo format_date($payment['payment_date']); ?></td>
                            <td style="color: var(--success-color); font-weight: bold;">₦<?php echo format_money($payment['amount']); ?></td>
                            <td><?php echo htmlspecialchars($payment['recorded_by_name']); ?></td>
                            <td><?php echo htmlspecialchars($payment['notes'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
