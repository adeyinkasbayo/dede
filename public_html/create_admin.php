<?php
/**
 * Admin User Creation Script
 * Run this once to create the default admin user
 * Access: http://yourdomain.com/create_admin.php
 */

require_once __DIR__ . '/src/init.php';

// Check if admin already exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
$stmt->execute();
$exists = $stmt->fetchColumn();

if ($exists > 0) {
    die("<h2>Admin user already exists!</h2><p>Please delete this file for security.</p>");
}

// Create admin user
$username = 'admin';
$password = 'admin123';
$hashed_password = hash_password($password);

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, full_name, role, status)
        VALUES (?, ?, ?, 'admin', 'active')
    ");
    $stmt->execute([$username, $hashed_password, 'System Administrator']);
    
    echo "<h2>Success!</h2>";
    echo "<p>Admin user created successfully:</p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "</ul>";
    echo "<p><strong>Important:</strong> Please delete this file (create_admin.php) for security!</p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
} catch (PDOException $e) {
    die("Error creating admin: " . $e->getMessage());
}
?>