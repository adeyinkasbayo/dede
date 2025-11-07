<?php
// Direct test of API endpoints
require_once __DIR__ . '/public_html/src/init.php';

// Simulate logged in admin user
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "=== Testing API Endpoints ===\n\n";

// Test 1: api_get_shop_id.php
echo "Test 1: api_get_shop_id.php\n";
$_GET['code'] = 'SH001';
ob_start();
include __DIR__ . '/public_html/api_get_shop_id.php';
$output = ob_get_clean();
echo "Response: " . $output . "\n\n";

// Test 2: api_get_totals.php
echo "Test 2: api_get_totals.php\n";
$_GET = ['staff_id' => 1, 'shop_id' => 1, 'date' => date('Y-m-d')];
ob_start();
include __DIR__ . '/public_html/api_get_totals.php';
$output = ob_get_clean();
echo "Response: " . $output . "\n\n";

echo "=== Tests Complete ===\n";
?>
