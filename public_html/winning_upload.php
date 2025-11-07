<?php
$page_title = 'Upload Winning Receipt';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/winnings.php';
require_login();

$current_user = get_logged_user();
$winning_controller = new WinningController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receipt_filename = null;
    
    // Handle file upload
    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
        $validation = validate_file_upload($_FILES['receipt_image'], ALLOWED_IMAGE_TYPES);
        
        if ($validation['success']) {
            $upload_result = upload_file($_FILES['receipt_image'], UPLOAD_PATH . 'winnings/');
            
            if ($upload_result['success']) {
                $receipt_filename = $upload_result['filename'];
            } else {
                set_message($upload_result['message'], 'danger');
            }
        } else {
            set_message($validation['message'], 'danger');
        }
    }
    
    // Prepare data
    $shop_id = isset($_POST['shop_id']) ? $_POST['shop_id'] : $current_user['shop_id'];
    $staff_id = isset($_POST['staff_id']) ? $_POST['staff_id'] : $current_user['id'];
    $amount = isset($_POST['amount']) ? $_POST['amount'] : 0;
    $winning_date = isset($_POST['winning_date']) ? $_POST['winning_date'] : date('Y-m-d');
    
    // Validate required fields
    if (empty($shop_id) || empty($staff_id) || empty($amount) || empty($winning_date)) {
        set_message('Shop, staff, amount, and date are required', 'danger');
    } else {
        $data = [
            'shop_id' => $shop_id,
            'staff_id' => $staff_id,
            'customer_name' => sanitize_input($_POST['customer_name'] ?? ''),
            'ticket_number' => sanitize_input($_POST['ticket_number'] ?? ''),
            'amount' => $amount,
            'winning_date' => $winning_date,
            'receipt_image' => $receipt_filename,
            'notes' => sanitize_input($_POST['notes'] ?? ''),
            'status' => 'pending',
            'created_by' => $current_user['id']
        ];
        
        $result = $winning_controller->create($data);
        
        if ($result['success']) {
            set_message($result['message'], 'success');
            redirect('winning_upload.php');
        } else {
            set_message($result['message'], 'danger');
        }
    }
}

// Get shops based on role
if (is_manager()) {
    // Admin/Manager can see all shops
    $shops = get_accessible_shops($pdo);
    // Get all staff for selection
    $staff_list = $user_controller->get_all('staff');
} else {
    // Staff can only see their assigned shops
    $shops = $assignment_controller->get_assigned_shops_for_staff($current_user['id']);
    $staff_list = []; // Staff can't select other staff members
}

// Get recent winnings
$shop_id = is_manager() ? null : $current_user['shop_id'];
$winnings = $winning_controller->get_all($shop_id);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-trophy"></i> Winning Receipts</h1>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card" style="max-width: 800px; margin: 0 auto 30px;">
            <div class="card-header">
                <h3>Upload Winning Receipt</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
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
                        <label for="winning_date">Winning Date *</label>
                        <input type="date" id="winning_date" name="winning_date" class="form-control" 
                               required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_name">Customer Name</label>
                        <input type="text" id="customer_name" name="customer_name" class="form-control" 
                               placeholder="Enter customer name">
                    </div>
                    
                    <div class="form-group">
                        <label for="ticket_number">Ticket Number</label>
                        <input type="text" id="ticket_number" name="ticket_number" class="form-control" 
                               placeholder="Enter ticket number">
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Winning Amount *</label>
                        <input type="number" id="amount" name="amount" class="form-control" 
                               step="0.01" placeholder="0.00" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="receipt_image">Receipt Image</label>
                        <input type="file" id="receipt_image" name="receipt_image" class="form-control" 
                               accept="image/*" onchange="previewImage(this, 'preview')">
                        <small style="color: #64748b;">Allowed formats: JPG, JPEG, PNG, GIF (Max 5MB)</small>
                    </div>
                    
                    <div id="preview" class="upload-preview"></div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" 
                                  placeholder="Any additional notes"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Winning
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Recent Winnings</h3>
            </div>
            <div class="card-body">
                <?php if (empty($winnings)): ?>
                    <p style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fas fa-trophy" style="font-size: 48px; opacity: 0.5;"></i><br><br>
                        No winnings recorded yet.
                    </p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Shop</th>
                                <th>Customer</th>
                                <th>Ticket</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($winnings as $winning): ?>
                            <tr>
                                <td><?php echo format_date($winning['winning_date']); ?></td>
                                <td><?php echo htmlspecialchars($winning['shop_name']); ?></td>
                                <td><?php echo htmlspecialchars($winning['customer_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($winning['ticket_number'] ?? '-'); ?></td>
                                <td><strong>$<?php echo format_money($winning['amount']); ?></strong></td>
                                <td>
                                    <?php 
                                    $status_badge = 'secondary';
                                    if ($winning['status'] === 'verified') $status_badge = 'success';
                                    elseif ($winning['status'] === 'paid') $status_badge = 'info';
                                    elseif ($winning['status'] === 'pending') $status_badge = 'warning';
                                    ?>
                                    <span class="badge badge-<?php echo $status_badge; ?>">
                                        <?php echo ucfirst($winning['status']); ?>
                                    </span>
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