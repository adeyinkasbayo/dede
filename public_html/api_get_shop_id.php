<?php
require_once __DIR__ . '/src/init.php';
require_login();

header('Content-Type: application/json');

// Get shop code parameter
$code = isset($_GET['code']) ? trim($_GET['code']) : null;

// Validate required parameter
if (!$code) {
    echo json_encode([
        'success' => false,
        'message' => 'Shop code is required'
    ]);
    exit;
}

try {
    // Get shop_id from shop_code
    $stmt = $pdo->prepare("SELECT id, name FROM shops WHERE code = ?");
    $stmt->execute([$code]);
    $shop = $stmt->fetch();
    
    if ($shop) {
        echo json_encode([
            'success' => true,
            'shop_id' => $shop['id'],
            'shop_name' => $shop['name']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Shop not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
