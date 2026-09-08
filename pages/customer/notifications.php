<?php
/**
 * TokenFlow Pro — Notifications Center
 */
$pageTitle = 'Notifications';
$pageBackground = 'customer-dashboard';
$currentPage = 'notifications';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireLogin();

$user = Auth::getCurrentUser();
$pageRole = $user['role'];
$userId = $user['id'];
$db = Database::getInstance();

// Handle mark read
if (isset($_GET['mark_all_read'])) {
    NotificationEngine::markAllRead($userId);
    header('Location: notifications.php');
    exit;
}

$notifications = NotificationEngine::getForUser($userId, null, 50)['notifications'] ?? [];

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <h1 class="page-header-title"><i class="bi bi-bell" style="color:var(--accent-warning);"></i> Notification Center</h1>
        <?php if (!empty($notifications)): ?>
        <a href="?mark_all_read=1" class="btn-tf btn-secondary"><i class="bi bi-check-all"></i> Mark All as Read</a>
        <?php endif; ?>
    </div>
</div>

<div class="glass-surface card-tf animate-fade-up">
    <?php if (empty($notifications)): ?>
    <div class="text-center p-12">
        <div class="empty-state-icon" style="margin:0 auto var(--space-4);"><i class="bi bi-bell-slash"></i></div>
        <h3 class="text-heading mb-2">No Notifications</h3>
        <p class="text-secondary">You're all caught up! Check back later for updates.</p>
    </div>
    <?php else: ?>
    <div class="queue-list">
        <?php foreach ($notifications as $n): ?>
        <div class="queue-list-item <?= !$n['is_read'] ? 'unread' : '' ?>" style="padding:var(--space-4);">
            <div class="qi-item-icon" style="background:rgba(var(--accent-primary-rgb),0.1);color:var(--accent-primary);">
                <i class="bi <?= e($n['icon'] ?? 'bi-bell') ?>"></i>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <strong class="text-sm"><?= e($n['title']) ?></strong>
                    <?php if (!$n['is_read']): ?>
                    <span class="badge-tf badge-primary" style="font-size:9px;">NEW</span>
                    <?php endif; ?>
                </div>
                <div class="text-sm text-secondary mt-1"><?= e($n['message']) ?></div>
                <div class="text-xs text-muted mt-1"><?= timeAgo($n['created_at']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
