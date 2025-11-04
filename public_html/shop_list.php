<?php
$page_title = 'Shop Management';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/shop.php';
require_permission(['admin']);

$current_user = get_current_user();
$shop_controller = new ShopController($pdo);

// Handle delete
if (isset($_GET['delete'])) {
    $result = $shop_controller->delete($_GET['delete']);
    set_message($result['message'], $result['success'] ? 'success' : 'danger');
    redirect('shop_list.php');
}

$shops = $shop_controller->get_all();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-store"></i> Shop Management</h1>
        </div>
        <div class="header-right">
            <a href="shop_create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Shop
            </a>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>All Shops</h3>
                <input type="text" id="searchInput" class="form-control" style="max-width: 300px;" 
                       placeholder="Search shops..." onkeyup="filterTable('searchInput', 'shopsTable')">
            </div>
            <div class="card-body">
                <?php if (empty($shops)): ?>
                    <p style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fas fa-store" style="font-size: 48px; opacity: 0.5;"></i><br><br>
                        No shops found. <a href="shop_create.php">Create your first shop</a>
                    </p>
                <?php else: ?>
                    <table class="table" id="shopsTable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Shop Name</th>
                                <th>Location</th>
                                <th>Phone</th>
                                <th>Manager</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shops as $shop): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($shop['code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($shop['name']); ?></td>
                                <td><?php echo htmlspecialchars($shop['location'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($shop['phone'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($shop['manager_name'] ?? 'Not assigned'); ?></td>
                                <td>
                                    <?php if ($shop['status'] === 'active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <a href="shop_edit.php?id=<?php echo $shop['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $shop['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirmDelete('Delete this shop?')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
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