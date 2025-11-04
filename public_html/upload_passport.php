<?php
$page_title = 'Upload Passport Photo';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/user.php';
require_login();

$current_user = get_current_user();
$user_controller = new UserController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] === UPLOAD_ERR_OK) {
        $validation = validate_file_upload($_FILES['passport_photo'], ALLOWED_IMAGE_TYPES);
        
        if ($validation['success']) {
            $upload_result = upload_file($_FILES['passport_photo'], UPLOAD_PATH . 'passports/');
            
            if ($upload_result['success']) {
                $result = $user_controller->update_passport($current_user['id'], $upload_result['filename']);
                
                if ($result['success']) {
                    set_message('Passport photo uploaded successfully', 'success');
                    redirect('upload_passport.php');
                } else {
                    set_message($result['message'], 'danger');
                }
            } else {
                set_message($upload_result['message'], 'danger');
            }
        } else {
            set_message($validation['message'], 'danger');
        }
    } else {
        set_message('Please select a file to upload', 'danger');
    }
}

// Get current passport photo
$user = $user_controller->get_by_id($current_user['id']);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-id-card"></i> Upload Passport Photo</h1>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($current_user['username'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($current_user['username']); ?></span>
            </div>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header">
                <h3>Upload Your Passport Photo</h3>
            </div>
            <div class="card-body">
                <?php if ($user && $user['passport_photo']): ?>
                    <div style="margin-bottom: 20px; text-align: center;">
                        <h4 style="margin-bottom: 10px;">Current Passport Photo:</h4>
                        <img src="uploads/passports/<?php echo htmlspecialchars($user['passport_photo']); ?>" 
                             alt="Passport Photo" 
                             style="max-width: 250px; max-height: 250px; border-radius: 8px; border: 2px solid var(--border-color);">
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="passport_photo">Select Passport Photo *</label>
                        <input type="file" id="passport_photo" name="passport_photo" class="form-control" 
                               accept="image/*" required onchange="previewImage(this, 'preview')">
                        <small style="color: #64748b;">Allowed formats: JPG, JPEG, PNG, GIF (Max 5MB)</small>
                    </div>
                    
                    <div id="preview" class="upload-preview"></div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Photo
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>