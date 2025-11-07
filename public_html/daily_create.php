<?php
$page_title = 'Add Daily Operation';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/daily.php';
require_once __DIR__ . '/src/controllers/user.php';
require_once __DIR__ . '/src/controllers/staff_assignment.php';
require_login();

$current_user = get_logged_user();
$daily_controller = new DailyController($pdo);
$user_controller = new UserController($pdo);
$assignment_controller = new StaffAssignmentController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'shop_code' => $_POST['shop_code'] ?? null,
        'staff_id' => $_POST['staff_id'] ?? $current_user['id'],
        'operation_date' => $_POST['operation_date'] ?? date('Y-m-d'),
        'opening_balance' => $_POST['opening_balance'] ?? 0,
        'transfer_to_staff' => $_POST['transfer_to_staff'] ?? 0,
        'total_winnings' => $_POST['total_winnings'] ?? 0,
        'total_expenses' => $_POST['total_expenses'] ?? 0,
        'daily_debt' => $_POST['daily_debt'] ?? 0,
        'closing_balance' => $_POST['closing_balance'] ?? 0,
        'tips' => $_POST['tips'] ?? 0,
        'total_sales' => $_POST['total_sales'] ?? 0,
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

// Get shops based on role
if (is_manager()) {
    // Admin/Manager can see all shops and all staff
    $shops = get_accessible_shops($pdo);
    $staff = $user_controller->get_all('staff');
} else {
    // Staff can only see their assigned shops
    $shops = $assignment_controller->get_assigned_shops_for_staff($current_user['id']);
    $staff = []; // Staff can't select other staff members
}

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
                <?php if (is_manager()): ?>
                    <p style="margin: 10px 0 0 0; color: #64748b; font-size: 14px;">
                        <i class="fas fa-info-circle"></i> As Admin/Manager, you can create operations for any staff member
                    </p>
                <?php else: ?>
                    <p style="margin: 10px 0 0 0; color: #64748b; font-size: 14px;">
                        <i class="fas fa-info-circle"></i> You can only submit operations for shop codes assigned to you
                    </p>
                <?php endif; ?>
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
                        <label for="staff_id">Staff *</label>
                        <select id="staff_id" name="staff_id" class="form-control" required>
                            <option value="">-- Select Staff --</option>
                            <?php foreach ($staff as $member): ?>
                                <option value="<?php echo $member['id']; ?>">
                                    <?php echo htmlspecialchars($member['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="shop_code">Shop Code * 
                            <?php if (!is_manager()): ?>
                                <small style="color: #64748b;">(Only your assigned shops)</small>
                            <?php endif; ?>
                        </label>
                        <select id="shop_code" name="shop_code" class="form-control" required>
                            <option value="">-- Select Shop Code --</option>
                            <?php if (empty($shops)): ?>
                                <option value="" disabled>No shops assigned. Contact your manager.</option>
                            <?php else: ?>
                                <?php foreach ($shops as $shop): ?>
                                    <option value="<?php echo htmlspecialchars($shop['code']); ?>">
                                        <?php echo htmlspecialchars($shop['code']); ?> - <?php echo htmlspecialchars($shop['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (!is_manager() && empty($shops)): ?>
                            <small style="color: #ef4444;">You have no shop assignments. Please contact your manager to assign you to shops.</small>
                        <?php endif; ?>
                    </div>
                    
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
                        <label for="tips">Tips</label>
                        <input type="number" id="tips" name="tips" class="form-control" 
                               step="0.01" value="0.00" onchange="calculateDailyTotal()">
                        <small style="color: #64748b;">Tips received for this operation</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="tips_calculation" style="color: #10b981; font-weight: bold;">Tips Calculation</label>
                        <input type="number" id="tips_calculation" name="tips_calculation" class="form-control" 
                               step="0.01" value="0.00" readonly style="background: #ecfdf5; border-color: #10b981; font-weight: bold;">
                        <small style="color: #10b981;">Auto-calculated: Cash Balance + Tips</small>
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
<script>
// Auto-fetch totals when staff, shop, or date changes
function fetchTotalsFromDatabase() {
    const staffSelect = document.getElementById('staff_id');
    const shopCodeSelect = document.getElementById('shop_code');
    const dateInput = document.getElementById('operation_date');
    
    // Get current values
    const staffId = staffSelect ? staffSelect.value : <?php echo $current_user['id']; ?>;
    const shopCode = shopCodeSelect ? shopCodeSelect.value : '';
    const date = dateInput ? dateInput.value : '';
    
    // Only fetch if we have all required values
    if (!staffId || !shopCode || !date) {
        return;
    }
    
    // Get shop_id from shop code
    const shopOption = shopCodeSelect.options[shopCodeSelect.selectedIndex];
    if (!shopOption || !shopOption.value) {
        return;
    }
    
    // Parse shop_id from the select option (we'll need to add data attribute)
    // For now, we need to convert shop code to shop_id
    fetch('api_get_shop_id.php?code=' + encodeURIComponent(shopCode))
        .then(response => response.json())
        .then(shopData => {
            if (shopData.success && shopData.shop_id) {
                // Now fetch totals
                const url = 'api_get_totals.php?staff_id=' + staffId + '&shop_id=' + shopData.shop_id + '&date=' + date;
                
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update the fields
                            document.getElementById('total_winnings').value = data.total_winnings;
                            document.getElementById('total_expenses').value = data.total_expenses;
                            
                            // Trigger calculation
                            calculateDailyTotal();
                            
                            // Show info message
                            if (parseFloat(data.total_winnings) > 0 || parseFloat(data.total_expenses) > 0) {
                                console.log('Auto-loaded: Winnings: $' + data.total_winnings + ', Expenses: $' + data.total_expenses);
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching totals:', error));
            }
        })
        .catch(error => console.error('Error fetching shop ID:', error));
}

// Attach event listeners
document.addEventListener('DOMContentLoaded', function() {
    const staffSelect = document.getElementById('staff_id');
    const shopCodeSelect = document.getElementById('shop_code');
    const dateInput = document.getElementById('operation_date');
    
    if (staffSelect) {
        staffSelect.addEventListener('change', fetchTotalsFromDatabase);
    }
    
    if (shopCodeSelect) {
        shopCodeSelect.addEventListener('change', fetchTotalsFromDatabase);
    }
    
    if (dateInput) {
        dateInput.addEventListener('change', fetchTotalsFromDatabase);
    }
    
    // Auto-fetch on page load if values are pre-filled
    if (shopCodeSelect && shopCodeSelect.value && dateInput && dateInput.value) {
        fetchTotalsFromDatabase();
    }
});

// Make total_winnings and total_expenses readonly but still allow manual override if needed
window.addEventListener('load', function() {
    const winningsInput = document.getElementById('total_winnings');
    const expensesInput = document.getElementById('total_expenses');
    
    if (winningsInput) {
        winningsInput.style.backgroundColor = '#f0f9ff';
        winningsInput.style.borderColor = '#3b82f6';
        winningsInput.title = 'Auto-calculated from uploaded winnings. Change date/shop to refresh.';
    }
    
    if (expensesInput) {
        expensesInput.style.backgroundColor = '#f0fdf4';
        expensesInput.style.borderColor = '#10b981';
        expensesInput.title = 'Auto-calculated from recorded expenses. Change date/shop to refresh.';
    }
});
</script>
</body>
</html>