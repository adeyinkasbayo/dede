<?php
$page_title = 'Add Daily Operation';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/daily.php';
require_once __DIR__ . '/src/controllers/user.php';
require_login();

$current_user = get_logged_user();
$daily_controller = new DailyController($pdo);
$user_controller = new UserController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'shop_id' => $_POST['shop_id'] ?? $current_user['shop_id'],
        'staff_id' => $_POST['staff_id'] ?? $current_user['id'],
        'operation_date' => $_POST['operation_date'] ?? date('Y-m-d'),
        'opening_balance' => $_POST['opening_balance'] ?? 0,
        'total_sales' => $_POST['total_sales'] ?? 0,
        'total_expenses' => $_POST['total_expenses'] ?? 0,
        'closing_balance' => $_POST['closing_balance'] ?? 0,
        'notes' => sanitize_input($_POST['notes'] ?? ''),
        'created_by' => $current_user['id']
    ];
    
    $result = $daily_controller->create($data);
    
    if ($result['success']) {
        set_message($result['message'], 'success');
        redirect('daily_list.php');
    } else {
        set_message($result['message'], 'danger');
    }
}

// Get shops and staff
$shops = get_accessible_shops($pdo);
$staff = $user_controller->get_all('staff');

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-calendar-plus"></i> Add Daily Operation</h1>
        </div>
        <div class="header-right">
            <a href="daily_list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <div class="card-header">
                <h3>Daily Operation Details</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="operation_date">Operation Date *</label>
                        <input type="date" id="operation_date" name="operation_date" class="form-control" 
                               required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
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
                    
                    <div class="form-group">
                        <label for="staff_id">Staff *</label>
                        <select id="staff_id" name="staff_id" class="form-control" required>
                            <?php foreach ($staff as $member): ?>
                                <option value="<?php echo $member['id']; ?>"
                                    <?php echo ($current_user['id'] == $member['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($member['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="opening_balance">Opening Balance *</label>
                        <input type="number" id="opening_balance" name="opening_balance" class="form-control" 
                               step="0.01" required value="0.00" onchange="calculateDailyTotal()">
                    </div>
                    
                    <div class="form-group">
                        <label for="transfer_to_staff">Transfer to Staff</label>
                        <input type="number" id="transfer_to_staff" name="transfer_to_staff" class="form-control" 
                               step="0.01" value="0.00" onchange="calculateDailyTotal()">
                        <small style="color: #64748b;">Money given to staff during the day</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="total_winnings">Total Winnings *</label>
                        <input type="number" id="total_winnings" name="total_winnings" class="form-control" 
                               step="0.01" required value="0.00" onchange="calculateDailyTotal()">
                    </div>
                    
                    <div class="form-group">
                        <label for="total_expenses">Total Expenses *</label>
                        <input type="number" id="total_expenses" name="total_expenses" class="form-control" 
                               step="0.01" required value="0.00" onchange="calculateDailyTotal()">
                    </div>
                    
                    <div class="form-group">
                        <label for="daily_debt">Daily Debt</label>
                        <input type="number" id="daily_debt" name="daily_debt" class="form-control" 
                               step="0.01" value="0.00" onchange="calculateDailyTotal()">
                        <small style="color: #64748b;">Debt amount for today</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="closing_balance">Closing Balance *</label>
                        <input type="number" id="closing_balance" name="closing_balance" class="form-control" 
                               step="0.01" required value="0.00" onchange="calculateDailyTotal()">
                    </div>
                    
                    <div class="form-group">
                        <label for="total_sales">Total Sales (Optional)</label>
                        <input type="number" id="total_sales" name="total_sales" class="form-control" 
                               step="0.01" value="0.00">
                        <small style="color: #64748b;">For record keeping purposes</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="cash_balance">Cash Balance</label>
                        <input type="number" id="cash_balance" name="cash_balance" class="form-control" 
                               step="0.01" value="0.00" readonly>
                        <small style="color: #64748b;">Auto-calculated: Opening + Transfer - Winnings - Expenses - Daily Debt - Closing</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" 
                                  placeholder="Any additional notes"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Operation
                        </button>
                        <a href="daily_list.php" class="btn btn-secondary">
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