<?php
require_once __DIR__ . '/src/init.php';
require_permission(['admin', 'manager']);

header('Content-Type: text/plain');

echo "=== WINNINGS DEBUG INFO ===\n\n";

// Check total winnings count
$stmt = $pdo->query("SELECT COUNT(*) as total FROM winnings");
$total = $stmt->fetchColumn();
echo "Total winnings in database: $total\n\n";

// Check today's winnings
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM winnings WHERE winning_date = ?");
$stmt->execute([$today]);
$today_count = $stmt->fetchColumn();
echo "Today's winnings ($today): $today_count\n\n";

// Check pending winnings
$stmt = $pdo->query("SELECT COUNT(*) as count FROM winnings WHERE status = 'pending'");
$pending_count = $stmt->fetchColumn();
echo "Pending winnings: $pending_count\n\n";

// Check today's pending winnings
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM winnings WHERE status = 'pending' AND winning_date = ?");
$stmt->execute([$today]);
$today_pending = $stmt->fetchColumn();
echo "Today's pending winnings: $today_pending\n\n";

// Show last 5 winnings
echo "=== LAST 5 WINNINGS ===\n";
$stmt = $pdo->query("
    SELECT w.id, w.ticket_number, w.amount, w.winning_date, w.status, w.staff_id, 
           u.full_name as staff_name, s.code as shop_code
    FROM winnings w
    LEFT JOIN users u ON w.staff_id = u.id
    LEFT JOIN shops s ON w.shop_id = s.id
    ORDER BY w.id DESC
    LIMIT 5
");
$recent = $stmt->fetchAll();

foreach ($recent as $w) {
    echo "ID: {$w['id']} | Ticket: {$w['ticket_number']} | Amount: ₦{$w['amount']} | ";
    echo "Date: {$w['winning_date']} | Status: {$w['status']} | ";
    echo "Staff: {$w['staff_name']} ({$w['staff_id']}) | Shop: {$w['shop_code']}\n";
}

echo "\n=== TABLE STRUCTURE ===\n";
$stmt = $pdo->query("DESCRIBE winnings");
$columns = $stmt->fetchAll();
foreach ($columns as $col) {
    echo "{$col['Field']} ({$col['Type']}) - {$col['Null']} - {$col['Key']}\n";
}
?>
