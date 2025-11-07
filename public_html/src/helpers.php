<?php
/**
 * Helper Functions
 */

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function set_message($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function get_message() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'success';
        unset($_SESSION['message'], $_SESSION['message_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function generate_unique_filename($original_filename) {
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

function format_date($date) {
    return date('M d, Y', strtotime($date));
}

function format_datetime($datetime) {
    return date('M d, Y H:i', strtotime($datetime));
}

function format_money($amount, $include_symbol = false) {
    $formatted = number_format($amount, 2);
    return $include_symbol ? '₦' . $formatted : $formatted;
}

function get_user_role_name($role) {
    $roles = [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'staff' => 'Staff Member'
    ];
    return $roles[$role] ?? 'Unknown';
}

function validate_file_upload($file, $allowed_types, $max_size = MAX_FILE_SIZE) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred'];
    }

    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size exceeds limit'];
    }

    // Try to detect MIME type with fallback methods
    $mime_type = null;
    
    // Method 1: Use finfo if available
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
    }
    // Method 2: Use mime_content_type if available
    elseif (function_exists('mime_content_type')) {
        $mime_type = mime_content_type($file['tmp_name']);
    }
    // Method 3: Fallback to file extension check
    else {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime_map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf'
        ];
        $mime_type = $mime_map[$extension] ?? 'application/octet-stream';
    }

    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    return ['success' => true];
}

function upload_file($file, $destination_path) {
    $filename = generate_unique_filename($file['name']);
    $destination = $destination_path . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

function delete_file($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}
?>