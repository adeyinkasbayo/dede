<?php
/**
 * Authentication Functions
 */

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function get_logged_user() {
    if (is_logged_in()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'shop_id' => $_SESSION['shop_id'] ?? null
        ];
    }
    return null;
}

function has_role($roles) {
    if (!is_logged_in()) {
        return false;
    }
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION['role'], $roles);
}

function is_admin() {
    return has_role('admin');
}

function is_manager() {
    return has_role(['admin', 'manager']);
}

function login_user($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['shop_id'] = $user['shop_id'];
    $_SESSION['last_activity'] = time();
}

function logout_user() {
    session_unset();
    session_destroy();
}

function check_session_timeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        logout_user();
        return true;
    }
    $_SESSION['last_activity'] = time();
    return false;
}
?>