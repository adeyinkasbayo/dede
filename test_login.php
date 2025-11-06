<?php
require_once '/app/public_html/src/init.php';
require_once '/app/public_html/src/controllers/auth_controller.php';

echo "Testing login functionality...\n";

// Check if admin user exists
$stmt = $pdo->prepare("SELECT username, status FROM users WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin) {
    echo "✓ Admin user found: " . $admin['username'] . " (status: " . $admin['status'] . ")\n";
} else {
    echo "✗ Admin user not found\n";
    exit(1);
}

// Test login
$auth = new AuthController($pdo);
$result = $auth->login('admin', 'admin123');

if ($result['success']) {
    echo "✓ Login successful\n";
    echo "✓ Message: " . $result['message'] . "\n";
} else {
    echo "✗ Login failed\n";
    echo "✗ Message: " . $result['message'] . "\n";
}

// Check guarantor columns
echo "\nChecking guarantor columns...\n";
$stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'guarantor_%'");
$stmt->execute();
$columns = $stmt->fetchAll();

foreach ($columns as $column) {
    echo "✓ Column: " . $column['Field'] . "\n";
}

// Check guarantor data
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE guarantor_full_name IS NOT NULL");
$stmt->execute();
$count = $stmt->fetch()['count'];
echo "✓ Staff with guarantor info: " . $count . "\n";

echo "\nTest completed.\n";
?>