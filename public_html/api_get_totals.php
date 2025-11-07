<?php
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/winnings.php';
require_once __DIR__ . '/src/controllers/expenses.php';
require_login();

header('Content-Type: application/json');

$current_user = get_logged_user();

// Get parameters
$staff_id = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : null;
$shop_id = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : null;

// Validate required parameters
if (!$staff_id || !$shop_id || !$date) {
    echo json_encode([
        'success' => false,
        'message' => 'Staff ID, Shop ID, and Date are required',
        'total_winnings' => 0,
        'total_expenses' => 0
    ]);
    exit;
}

// Initialize controllers
$winning_controller = new WinningController($pdo);
$expense_controller = new ExpenseController($pdo);

// Get totals
$total_winnings = $winning_controller->get_total_by_staff_shop_date($staff_id, $shop_id, $date);
$total_expenses = $expense_controller->get_total_by_staff_shop_date($staff_id, $shop_id, $date);

// Return response
echo json_encode([
    'success' => true,
    'total_winnings' => number_format($total_winnings, 2, '.', ''),
    'total_expenses' => number_format($total_expenses, 2, '.', '')
]);
?>
