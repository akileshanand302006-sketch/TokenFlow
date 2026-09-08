<?php
/**
 * TokenFlow Pro — Admin: System Settings
 */
$pageTitle = 'Settings';
$pageBackground = 'settings';
$currentPage = 'settings';
$pageRole = 'admin';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('admin');

$db = Database::getInstance();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::requireValid();
    $settings = $_POST['settings'] ?? [];
    foreach ($settings as $key => $value) {
        $db->query("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
    }
    Audit::log('settings_updated', 'system_settings', null, 'Updated system settings');
    $message = "Settings saved successfully.";
}

$allSettings = $db->fetchAll("SELECT * FROM system_settings ORDER BY category, setting_key");
$grouped = [];
foreach ($allSettings as $s) { $grouped[$s['category']][] = $s; }

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header"><h1 class="page-header-title"><i class="bi bi-gear" style="color:var(--accent-primary);"></i> System Settings</h1></div>

<?php if ($message): ?>
<div class="animate-fade-up mb-4" style="padding:var(--space-3) var(--space-4);border-radius:var(--radius-md);background:rgba(52,211,153,0.1);border:1px solid var(--accent-success);color:var(--accent-success);">
    <i class="bi bi-check-circle"></i> <?= e($message) ?>
</div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
    
    <?php foreach ($grouped as $category => $settings): ?>
    <div class="glass-surface settings-section mb-6 animate-fade-up">
        <div class="settings-section-title"><?= ucfirst($category) ?> Settings</div>
        <div class="settings-section-desc">Configure <?= $category ?> related settings.</div>
        
        <?php foreach ($settings as $s): ?>
        <div class="form-group">
            <label class="form-label"><?= e(ucwords(str_replace('_', ' ', $s['setting_key']))) ?></label>
            <?php if ($s['setting_type'] === 'boolean'): ?>
            <select class="form-select" name="settings[<?= e($s['setting_key']) ?>]" style="width:120px;">
                <option value="true" <?= $s['setting_value'] === 'true' ? 'selected' : '' ?>>Enabled</option>
                <option value="false" <?= $s['setting_value'] === 'false' ? 'selected' : '' ?>>Disabled</option>
            </select>
            <?php elseif ($s['setting_type'] === 'number'): ?>
            <input type="number" class="form-input" name="settings[<?= e($s['setting_key']) ?>]" value="<?= e($s['setting_value']) ?>" style="width:200px;">
            <?php else: ?>
            <input type="text" class="form-input" name="settings[<?= e($s['setting_key']) ?>]" value="<?= e($s['setting_value']) ?>">
            <?php endif; ?>
            <div class="form-hint"><?= e($s['description'] ?? '') ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    
    <div class="flex justify-end">
        <button type="submit" class="btn-tf btn-primary btn-lg"><i class="bi bi-check"></i> Save Settings</button>
    </div>
</form>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
