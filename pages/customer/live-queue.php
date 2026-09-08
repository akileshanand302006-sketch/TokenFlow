<?php
/**
 * TokenFlow Pro — Live Queue Display
 */
$pageTitle = 'Live Queue';
$pageBackground = 'live-queue';
$currentPage = 'live-queue';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireLogin();

$user = Auth::getCurrentUser();
$pageRole = $user['role'];

$db = Database::getInstance();
$branches = $db->fetchAll("SELECT * FROM branches WHERE org_id = ? AND is_active = 1 ORDER BY name", [$_SESSION['org_id'] ?? 1]);
$defaultBranch = $branches[0]['id'] ?? 1;

$pageScripts = ['live-queue.js'];
include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <div>
            <h1 class="page-header-title"><i class="bi bi-broadcast" style="color:var(--accent-success);"></i> Live Queue</h1>
            <p class="page-header-subtitle">Real-time queue status across all departments.</p>
        </div>
        <div class="page-header-actions">
            <select class="form-select" id="branch-filter" style="width:200px;">
                <?php foreach ($branches as $b): ?>
                <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="flex items-center gap-2">
                <span class="status-dot online pulse"></span>
                <span class="text-sm text-success" id="live-status">Live</span>
            </div>
        </div>
    </div>
</div>

<!-- Currently Serving -->
<div class="section-tf animate-fade-up">
    <div class="section-header-tf">
        <h2 class="section-title-tf"><i class="bi bi-play-circle" style="color:var(--accent-success);"></i> Now Serving</h2>
    </div>
    <div class="metrics-row" id="serving-cards">
        <div class="skeleton skeleton-card"></div>
        <div class="skeleton skeleton-card"></div>
    </div>
</div>

<!-- Queue by Department -->
<div class="section-tf animate-fade-up delay-2">
    <div class="section-header-tf">
        <h2 class="section-title-tf"><i class="bi bi-people" style="color:var(--accent-warning);"></i> Queue Status</h2>
    </div>
    <div class="grid-2" id="dept-queues">
        <div class="skeleton skeleton-card"></div>
        <div class="skeleton skeleton-card"></div>
    </div>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
