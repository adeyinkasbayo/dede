<?php
$page_title = 'Edit Shop';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/shop.php';
require_once __DIR__ . '/src/controllers/user.php';
require_permission(['admin']);

$current_user = get_current_user();
$shop_controller = new ShopController($pdo);
$user_controller = new UserController($pdo);

$shop_id = $_GET['id'] ?? null;
if (!$shop_id) {
    set_message('Shop not found', 'danger');
    redirect('shop_list.php');
}

$shop = $shop_controller->get_by_id($shop_id);
if (!$shop) {
    set_message('Shop not found', 'danger');
    redirect('shop_list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize_input($_POST['name'] ?? ''),
        'code' => sanitize_input($_POST['code'] ?? ''),
        'location' => sanitize_input($_POST['location'] ?? ''),
        'address' => sanitize_input($_POST['address'] ?? ''),
        'phone' => sanitize_input($_POST['phone'] ?? ''),
        'manager_id' => $_POST['manager_id'] ?? null,
        'status' => $_POST['status'] ?? 'active'
    ];
    
    $result = $shop_controller->update($shop_id, $data);
    
    if ($result['success']) {
        set_message($result['message'], 'success');
        redirect('shop_list.php');
    } else {
        set_message($result['message'], 'danger');
    }
}

// Get managers for dropdown
$managers = $user_controller->get_all('manager');

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-store"></i> Edit Shop</h1>
        </div>
        <div class="header-right">
            <a href="shop_list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <div class="card-header">
                <h3>Edit Shop Information</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Shop Name *</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               placeholder="Enter shop name" required
                               value="<?php echo htmlspecialchars($shop['name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="code">Shop Code *</label>
                        <input type="text" id="code" name="code" class="form-control" 
                               placeholder="Enter unique shop code" required
                               value="<?php echo htmlspecialchars($shop['code']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-control" 
                               placeholder="Enter location"
                               value="<?php echo htmlspecialchars($shop['location'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" 
                                  placeholder="Enter full address"><?php echo htmlspecialchars($shop['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               placeholder="Enter phone number"
                               value="<?php echo htmlspecialchars($shop['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="manager_id">Manager</label>
                        <select id="manager_id" name="manager_id" class="form-control">
                            <option value="">-- Select Manager --</option>
                            <?php foreach ($managers as $manager): ?>
                                <option value="<?php echo $manager['id']; ?>"
                                    <?php echo ($shop['manager_id'] == $manager['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($manager['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="active" <?php echo ($shop['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($shop['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Shop
                        </button>
                        <a href="shop_list.php" class="btn btn-secondary">
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