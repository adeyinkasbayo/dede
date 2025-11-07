<?php
$page_title = 'Add Debt';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/debt.php';
require_once __DIR__ . '/src/controllers/user.php';
require_permission(['admin', 'manager']);

$current_user = get_logged_user();
$debt_controller = new DebtController($pdo);
$user_controller = new UserController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'staff_id' => $_POST['staff_id'] ?? null,
        'shop_id' => $_POST['shop_id'] ?? $current_user['shop_id'],
        'amount' => $_POST['amount'] ?? 0,
        'debt_date' => $_POST['debt_date'] ?? date('Y-m-d'),
        'description' => sanitize_input($_POST['description'] ?? ''),
        'created_by' => $current_user['id']
    ];
    
    $result = $debt_controller->create($data);
    
    if ($result['success']) {
        set_message($result['message'], 'success');
        redirect('debt_list.php');
    } else {
        set_message($result['message'], 'danger');
    }
}

$shop_id = is_admin() ? null : $current_user['shop_id'];
$staff_list = $user_controller->get_all('staff', $shop_id);
$shops = get_accessible_shops($pdo);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-plus"></i> Add Debt</h1>
        </div>
        <div class="header-right">
            <a href="debt_list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <div class="card-header">
                <h3>Debt Details</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php if (is_admin()): ?>
                    <div class="form-group">
                        <label for="shop_id">Shop *</label>
                        <select id="shop_id" name="shop_id" class="form-control" required>
                            <?php foreach ($shops as $shop): ?>
                                <option value="<?php echo $shop['id']; ?>">
                                    <?php echo htmlspecialchars($shop['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="staff_id">Staff Member *</label>
                        <select id="staff_id" name="staff_id" class="form-control" required>
                            <option value="">-- Select Staff --</option>
                            <?php foreach ($staff_list as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>">
                                    <?php echo htmlspecialchars($staff['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="debt_date">Debt Date *</label>
                        <input type="date" id="debt_date" name="debt_date" class="form-control" 
                               required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Amount *</label>
                        <input type="number" id="amount" name="amount" class="form-control" 
                               step="0.01" placeholder="₦0.00" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" 
                                  placeholder="Enter debt description or reason"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Debt
                        </button>
                        <a href="debt_list.php" class="btn btn-secondary">
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
