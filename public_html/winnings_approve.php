<?php
$page_title = 'Approve Winnings';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/winnings.php';
require_permission(['admin', 'manager']);

$current_user = get_logged_user();
$winning_controller = new WinningController($pdo);

// Handle single approve/decline
if (isset($_GET['action']) && isset($_GET['id'])) {
    $winning_id = (int)$_GET['id'];
    
    if ($_GET['action'] === 'approve') {
        $result = $winning_controller->approve($winning_id, $current_user['id']);
        set_message($result['message'], $result['success'] ? 'success' : 'danger');
        redirect('winnings_approve.php');
    } elseif ($_GET['action'] === 'decline') {
        $result = $winning_controller->decline($winning_id);
        set_message($result['message'], $result['success'] ? 'success' : 'danger');
        redirect('winnings_approve.php');
    }
}

// Handle bulk approve
if (isset($_POST['bulk_approve']) && isset($_POST['winning_ids'])) {
    $success_count = 0;
    $fail_count = 0;
    
    foreach ($_POST['winning_ids'] as $id) {
        $result = $winning_controller->approve((int)$id, $current_user['id']);
        if ($result['success']) {
            $success_count++;
        } else {
            $fail_count++;
        }
    }
    
    if ($success_count > 0) {
        set_message("Successfully approved $success_count winning(s)", 'success');
    }
    if ($fail_count > 0) {
        set_message("Failed to approve $fail_count winning(s)", 'danger');
    }
    redirect('winnings_approve.php');
}

// Handle bulk decline
if (isset($_POST['bulk_decline']) && isset($_POST['winning_ids'])) {
    $success_count = 0;
    $fail_count = 0;
    
    foreach ($_POST['winning_ids'] as $id) {
        $result = $winning_controller->decline((int)$id);
        if ($result['success']) {
            $success_count++;
        } else {
            $fail_count++;
        }
    }
    
    if ($success_count > 0) {
        set_message("Successfully declined $success_count winning(s)", 'success');
    }
    if ($fail_count > 0) {
        set_message("Failed to decline $fail_count winning(s)", 'danger');
    }
    redirect('winnings_approve.php');
}

// Get today's pending winnings only (all staff)
$shop_id = null; // Don't filter by shop - get all shops
$today = date('Y-m-d');
$pending_winnings = $winning_controller->get_all($shop_id, 'pending', null, $today, $today, null, 100, 0);

// Calculate total pending amount
$total_pending = array_sum(array_column($pending_winnings, 'amount'));

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-check-circle"></i> Approve Today's Winnings</h1>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #64748b;">
                <?php echo date('l, F d, Y'); ?>
            </p>
        </div>
        <div class="header-right">
            <a href="winnings_list.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> All Winnings
            </a>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <!-- Summary Cards -->
        <div class="stats-grid" style="margin-bottom: 20px;">
            <div class="stat-card" style="border-left-color: #f59e0b;">
                <h4>Pending Winnings</h4>
                <div class="stat-value"><?php echo count($pending_winnings); ?></div>
            </div>
            
            <div class="stat-card" style="border-left-color: #ef4444;">
                <h4>Total Amount Pending</h4>
                <div class="stat-value">$<?php echo format_money($total_pending); ?></div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Today's Pending Winnings from All Staff</h3>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #64748b;">
                    Review and approve or decline today's winning submissions from all staff members
                </p>
            </div>
            <div class="card-body">
                <?php if (empty($pending_winnings)): ?>
                    <p style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fas fa-check-circle" style="font-size: 48px; opacity: 0.5; color: #10b981;"></i><br><br>
                        No pending winnings to approve. All caught up!
                    </p>
                <?php else: ?>
                    <form method="POST" action="" id="bulkForm">
                        <div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
                            <button type="button" onclick="selectAll()" class="btn btn-sm btn-secondary">
                                <i class="fas fa-check-square"></i> Select All
                            </button>
                            <button type="button" onclick="deselectAll()" class="btn btn-sm btn-secondary">
                                <i class="fas fa-square"></i> Deselect All
                            </button>
                            <button type="submit" name="bulk_approve" class="btn btn-sm btn-success" 
                                    onclick="return confirm('Approve selected winnings?')">
                                <i class="fas fa-check"></i> Approve Selected
                            </button>
                            <button type="submit" name="bulk_decline" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Decline selected winnings?')">
                                <i class="fas fa-times"></i> Decline Selected
                            </button>
                        </div>
                        
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="40"><input type="checkbox" id="selectAllCheckbox" onchange="toggleAll(this)"></th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Ticket Number</th>
                                        <th>Shop</th>
                                        <th>Staff</th>
                                        <th>Amount</th>
                                        <th>Receipt</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_winnings as $winning): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="winning_ids[]" value="<?php echo $winning['id']; ?>" class="winning-checkbox">
                                        </td>
                                        <td><?php echo format_date($winning['winning_date']); ?></td>
                                        <td><?php echo date('h:i A', strtotime($winning['created_at'])); ?></td>
                                        <td>
                                            <strong style="color: #3b82f6;">
                                                <?php echo htmlspecialchars($winning['ticket_number']); ?>
                                            </strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($winning['shop_name']); ?></td>
                                        <td><?php echo htmlspecialchars($winning['staff_name']); ?></td>
                                        <td><strong style="color: #10b981;">$<?php echo format_money($winning['amount']); ?></strong></td>
                                        <td>
                                            <?php if ($winning['receipt_image']): ?>
                                                <a href="uploads/winnings/<?php echo htmlspecialchars($winning['receipt_image']); ?>" 
                                                   target="_blank" class="btn btn-sm btn-info" title="View Receipt">
                                                    <i class="fas fa-image"></i> View
                                                </a>
                                            <?php else: ?>
                                                <span style="color: #94a3b8;">No receipt</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size: 13px; color: #64748b; max-width: 150px;">
                                            <?php echo htmlspecialchars(substr($winning['notes'] ?? 'N/A', 0, 40)) . (strlen($winning['notes'] ?? '') > 40 ? '...' : ''); ?>
                                        </td>
                                        <td class="table-actions">
                                            <a href="?action=approve&id=<?php echo $winning['id']; ?>" 
                                               class="btn btn-sm btn-success" 
                                               onclick="return confirm('Approve this winning?')" 
                                               title="Approve">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="?action=decline&id=<?php echo $winning['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Decline this winning?')" 
                                               title="Decline">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background: #f8fafc; font-weight: bold;">
                                        <td colspan="6" style="text-align: right;">Total Pending:</td>
                                        <td><strong style="color: #ef4444;">$<?php echo format_money($total_pending); ?></strong></td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAll(checkbox) {
    const checkboxes = document.querySelectorAll('.winning-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.winning-checkbox');
    checkboxes.forEach(cb => cb.checked = true);
    document.getElementById('selectAllCheckbox').checked = true;
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('.winning-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    document.getElementById('selectAllCheckbox').checked = false;
}
</script>

<script src="assets/js/app.js"></script>
</body>
</html>
