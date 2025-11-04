<?php
/**
 * Permission System
 */

function require_permission($required_roles) {
    require_login();
    
    if (!has_role($required_roles)) {
        set_message('You do not have permission to access this page', 'danger');
        redirect('index.php');
    }
}

function can_edit_shop($shop_id) {
    if (is_admin()) {
        return true;
    }
    
    $current_user = get_current_user();
    return $current_user['shop_id'] == $shop_id;
}

function can_manage_user($user_role) {
    if (is_admin()) {
        return true;
    }
    
    // Managers can only manage staff
    if (has_role('manager') && $user_role === 'staff') {
        return true;
    }
    
    return false;
}

function get_accessible_shops($pdo) {
    $current_user = get_current_user();
    
    if (is_admin()) {
        // Admin can access all shops
        $stmt = $pdo->query("SELECT * FROM shops WHERE status = 'active' ORDER BY name");
        return $stmt->fetchAll();
    } elseif (has_role('manager')) {
        // Manager can access their assigned shop
        $stmt = $pdo->prepare("SELECT * FROM shops WHERE id = ? AND status = 'active'");
        $stmt->execute([$current_user['shop_id']]);
        return $stmt->fetchAll();
    }
    
    return [];
}
?>