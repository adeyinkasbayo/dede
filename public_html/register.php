<?php
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/auth_controller.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => sanitize_input($_POST['username'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'full_name' => sanitize_input($_POST['full_name'] ?? ''),
        'email' => sanitize_input($_POST['email'] ?? ''),
        'phone' => sanitize_input($_POST['phone'] ?? ''),
        'role' => 'staff' // Default role for registration
    ];
    
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (empty($data['username']) || empty($data['password']) || empty($data['full_name'])) {
        $error = 'Username, password, and full name are required';
    } elseif ($data['password'] !== $password_confirm) {
        $error = 'Passwords do not match';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        $auth = new AuthController($pdo);
        $result = $auth->register($data);
        
        if ($result['success']) {
            $success = 'Registration successful! You can now login.';
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Register</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           placeholder="Enter your full name" required
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="Choose a username" required
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Enter your email"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           placeholder="Enter your phone number"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Choose a password (min. 6 characters)" required>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Confirm Password *</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" 
                           placeholder="Re-enter your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>
            
            <p style="margin-top: 20px; text-align: center; font-size: 14px; color: #64748b;">
                Already have an account? <a href="login.php" style="color: var(--primary-color); text-decoration: none;">Login here</a>
            </p>
        </div>
    </div>
    <script src="assets/js/app.js"></script>
</body>
</html>