<?php
/**
 * TokenFlow Pro — Customer Profile & Settings
 */
$pageTitle = 'Profile';
$pageBackground = 'settings';
$currentPage = 'profile';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireLogin();

$user = Auth::getCurrentUser();
$pageRole = $user['role'];
$db = Database::getInstance();

// Handle update
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::requireValid();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if ($name) {
            $db->query("UPDATE users SET full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?", [$name, $phone, $user['id']]);
            $message = 'Profile updated successfully.';
            $_SESSION['user_name'] = $name;
            $user['name'] = $name;
            $user['phone'] = $phone;
        }
    }
    
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        $userData = $db->fetch("SELECT password_hash FROM users WHERE id = ?", [$user['id']]);
        
        if (!password_verify($current, $userData['password_hash'])) {
            $message = 'error:Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $message = 'error:New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $message = 'error:New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->query("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?", [$hash, $user['id']]);
            $message = 'Password changed successfully.';
        }
    }
    
    // Refresh user object
    $user = Auth::getCurrentUser();
}

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <h1 class="page-header-title">Profile & Settings</h1>
</div>

<?php if ($message): ?>
<div class="animate-fade-up mb-6" style="padding:var(--space-3) var(--space-4);border-radius:var(--radius-md);
     <?php if (str_starts_with($message, 'error:')): ?>
     background:rgba(248,113,113,0.1);border:1px solid var(--accent-danger);color:var(--accent-danger);">
     <i class="bi bi-exclamation-circle"></i> <?= e(substr($message, 6)) ?>
     <?php else: ?>
     background:rgba(52,211,153,0.1);border:1px solid var(--accent-success);color:var(--accent-success);">
     <i class="bi bi-check-circle"></i> <?= e($message) ?>
     <?php endif; ?>
</div>
<?php endif; ?>

<div class="grid-2">
    <!-- Profile Info -->
    <div class="glass-elevated card-tf animate-fade-up">
        <div class="card-header-tf">
            <div class="card-title-tf"><i class="bi bi-person"></i> Personal Information</div>
        </div>
        
        <div class="text-center mb-6">
            <div class="avatar avatar-xl" style="margin:0 auto;">
                <?= getInitials($user['name']) ?>
            </div>
            <div class="text-heading mt-3"><?= e($user['name']) ?></div>
            <div class="text-caption"><?= e($user['email']) ?></div>
            <div class="badge-tf badge-primary mt-2"><?= ucfirst($user['role']) ?></div>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-input" name="full_name" value="<?= e($user['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" class="form-input" value="<?= e($user['email']) ?>" disabled>
                <div class="form-hint">Email cannot be changed.</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="tel" class="form-input" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="+91-XXXXX-XXXXX">
            </div>
            
            <button type="submit" class="btn-tf btn-primary w-full"><i class="bi bi-check"></i> Save Changes</button>
        </form>
    </div>

    <!-- Security -->
    <div class="glass-surface card-tf animate-fade-up delay-2">
        <div class="card-header-tf">
            <div class="card-title-tf"><i class="bi bi-shield-lock"></i> Security</div>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
            <input type="hidden" name="action" value="change_password">
            
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" class="form-input" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" class="form-input" name="new_password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" class="form-input" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn-tf btn-secondary w-full"><i class="bi bi-lock"></i> Change Password</button>
        </form>
        
        <!-- Personal Customer QR Pass Card -->
        <div style="border-top:1px solid var(--border-glass);margin-top:var(--space-6);padding-top:var(--space-6);text-align:center;">
            <div class="card-title-tf mb-2" style="justify-content:center;"><i class="bi bi-qr-code-scan" style="color:var(--accent-primary);"></i> Personal Customer QR Pass</div>
            <p class="text-caption mb-4">Scan this QR pass at any counter for instant identification & check-in.</p>
            <div style="background:white;padding:var(--space-4);border-radius:var(--radius-lg);display:inline-block;box-shadow:var(--shadow-md);">
                <canvas id="customer-pass-qr"></canvas>
            </div>
            <div class="text-mono text-xs text-muted mt-2">ID: TF-CUST-<?= $user['id'] ?>-<?= strtoupper(substr(md5($user['email']), 0, 6)) ?></div>
        </div>
        
        <div style="border-top:1px solid var(--border-glass);margin-top:var(--space-6);padding-top:var(--space-6);">
            <div class="text-heading mb-4">Account Stats</div>
            <div class="qi-items">
                <div class="qi-item">
                    <div class="qi-item-icon" style="background:rgba(var(--accent-primary-rgb),0.1);color:var(--accent-primary);"><i class="bi bi-calendar3"></i></div>
                    <span class="qi-item-text">Member since</span>
                    <span class="qi-item-value"><?= date('M Y', strtotime($user['created_at'])) ?></span>
                </div>
                <div class="qi-item">
                    <div class="qi-item-icon" style="background:rgba(var(--accent-success-rgb),0.1);color:var(--accent-success);"><i class="bi bi-shield-check"></i></div>
                    <span class="qi-item-text">Account status</span>
                    <span class="qi-item-value text-success">Active</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    renderQR('customer-pass-qr', 'TF-CUST-<?= $user['id'] ?>-<?= strtoupper(substr(md5($user['email']), 0, 6)) ?>', 160);
});
</script>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
