<?php
$page_title = 'All Winnings';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/winnings.php';
require_permission(['admin', 'manager']);

$current_user = get_logged_user();
$winning_controller = new WinningController($pdo);

// Handle approve/decline actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $winning_id = (int)$_GET['id'];
    
    if ($_GET['action'] === 'approve') {
        $result = $winning_controller->approve($winning_id, $current_user['id']);
        set_message($result['message'], $result['success'] ? 'success' : 'danger');
        redirect('winnings_list.php');
    } elseif ($_GET['action'] === 'decline') {
        $result = $winning_controller->decline($winning_id);
        set_message($result['message'], $result['success'] ? 'success' : 'danger');
        redirect('winnings_list.php');
    }
}

// Pagination settings
$per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d'); // Default to today
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); // Default to today
$month = isset($_GET['month']) ? $_GET['month'] : null;
$status_filter = isset($_GET['status']) ? $_GET['status'] : null;

// Check if user has cleared filters (show all)
$show_all = isset($_GET['show_all']) && $_GET['show_all'] == '1';
if ($show_all) {
    $date_from = null;
    $date_to = null;
}

// Get shop_id based on role
$shop_id = is_admin() ? null : $current_user['shop_id'];

// Get winnings with filters
$winnings = $winning_controller->get_all($shop_id, $status_filter, $search, $date_from, $date_to, $month, $per_page, $offset);

// Get total count for pagination
$total_records = $winning_controller->count_all($shop_id, $status_filter, $search, $date_from, $date_to, $month);
$total_pages = ceil($total_records / $per_page);

// Calculate totals
$total_amount = 0;
foreach ($winnings as $winning) {
    $total_amount += $winning['amount'];
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-trophy"></i> <?php echo $show_all ? 'All Winnings' : 'Daily Winnings (' . date('M d, Y') . ')'; ?></h1>
        </div>
        <div class="header-right">
            <?php if (!$show_all): ?>
                <a href="winnings_list.php?show_all=1" class="btn btn-secondary" style="margin-right: 10px;">
                    <i class="fas fa-list"></i> View All Winnings
                </a>
            <?php else: ?>
                <a href="winnings_list.php" class="btn btn-secondary" style="margin-right: 10px;">
                    <i class="fas fa-calendar-day"></i> Today's Winnings
                </a>
            <?php endif; ?>
            <a href="winning_upload.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Upload New Winning
            </a>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <!-- Filters -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-body">
                <form method="GET" action="" class="flex gap-10" style="flex-wrap: wrap; align-items: flex-end;">
                    <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                        <label for="search">Search Ticket Number</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               placeholder="Enter ticket number" 
                               value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    </div>
                    
                    <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                        <label for="month">Filter by Month</label>
                        <input type="month" id="month" name="month" class="form-control" 
                               value="<?php echo htmlspecialchars($month ?? ''); ?>">
                    </div>
                    
                    <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                        <label for="date_from">From Date</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" 
                               value="<?php echo htmlspecialchars($date_from ?? ''); ?>">
                    </div>
                    
                    <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                        <label for="date_to">To Date</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" 
                               value="<?php echo htmlspecialchars($date_to ?? ''); ?>">
                    </div>
                    
                    <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="verified" <?php echo ($status_filter === 'verified') ? 'selected' : ''; ?>>Verified</option>
                            <option value="paid" <?php echo ($status_filter === 'paid') ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="winnings_list.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Summary -->
        <div class="card" style="margin-bottom: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div>
                        <h4 style="color: rgba(255,255,255,0.8); font-size: 14px; margin-bottom: 5px;">Total Records</h4>
                        <div style="font-size: 32px; font-weight: bold;"><?php echo number_format($total_records); ?></div>
                    </div>
                    <div>
                        <h4 style="color: rgba(255,255,255,0.8); font-size: 14px; margin-bottom: 5px;">Total Amount (This Page)</h4>
                        <div style="font-size: 32px; font-weight: bold;">$<?php echo format_money($total_amount); ?></div>
                    </div>
                    <div>
                        <h4 style="color: rgba(255,255,255,0.8); font-size: 14px; margin-bottom: 5px;">Page</h4>
                        <div style="font-size: 32px; font-weight: bold;"><?php echo $page; ?> of <?php echo $total_pages; ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Winnings Table -->
        <div class="card">
            <div class="card-header">
                <h3>Winnings List</h3>
            </div>
            <div class="card-body">
                <?php if (empty($winnings)): ?>
                    <p style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fas fa-trophy" style="font-size: 48px; opacity: 0.5;"></i><br><br>
                        No winnings found matching your criteria.
                    </p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Ticket Number</th>
                                    <th>Shop</th>
                                    <th>Staff</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Receipt</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($winnings as $winning): ?>
                                <tr>
                                    <td><?php echo format_date($winning['winning_date']); ?></td>
                                    <td>
                                        <strong style="color: var(--primary-color);">
                                            <?php echo htmlspecialchars($winning['ticket_number'] ?? 'N/A'); ?>
                                        </strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($winning['shop_name']); ?></td>
                                    <td><?php echo htmlspecialchars($winning['staff_name']); ?></td>
                                    <td><strong style="color: var(--success-color);">$<?php echo format_money($winning['amount']); ?></strong></td>
                                    <td>
                                        <?php 
                                        $status_badge = 'secondary';
                                        if ($winning['status'] === 'approved') $status_badge = 'success';
                                        elseif ($winning['status'] === 'declined') $status_badge = 'danger';
                                        elseif ($winning['status'] === 'pending') $status_badge = 'warning';
                                        ?>
                                        <span class="badge badge-<?php echo $status_badge; ?>">
                                            <?php echo ucfirst($winning['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($winning['receipt_image']): ?>
                                            <a href="uploads/winnings/<?php echo htmlspecialchars($winning['receipt_image']); ?>" 
                                               target="_blank" class="btn btn-sm btn-info" title="View Receipt">
                                                <i class="fas fa-image"></i>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="table-actions">
                                        <?php if ($winning['status'] === 'pending'): ?>
                                            <a href="?action=approve&id=<?php echo $winning['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $month ? '&month=' . urlencode($month) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>&page=<?php echo $page; ?>" 
                                               class="btn btn-sm btn-success" 
                                               onclick="return confirm('Approve this winning?')" 
                                               title="Approve">
                                                <i class="fas fa-check"></i> Approve
                                            </a>
                                            <a href="?action=decline&id=<?php echo $winning['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $month ? '&month=' . urlencode($month) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>&page=<?php echo $page; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Decline this winning?')" 
                                               title="Decline">
                                                <i class="fas fa-times"></i> Decline
                                            </a>
                                        <?php elseif ($winning['status'] === 'verified'): ?>
                                            <span class="badge badge-success">Approved</span>
                                        <?php elseif ($winning['status'] === 'rejected'): ?>
                                            <span class="badge badge-danger">Declined</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background: #f8fafc; font-weight: bold;">
                                    <td colspan="5" style="text-align: right;">Page Total:</td>
                                    <td><strong style="color: var(--success-color);">$<?php echo format_money($total_amount); ?></strong></td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px; flex-wrap: wrap;">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $month ? '&month=' . urlencode($month) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>" 
                               class="btn btn-sm btn-secondary">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <span style="padding: 0 15px; color: #64748b;">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $month ? '&month=' . urlencode($month) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>" 
                               class="btn btn-sm btn-secondary">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
