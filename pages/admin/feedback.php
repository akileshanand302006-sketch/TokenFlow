<?php
/**
 * TokenFlow Pro — Admin: Feedback Management
 */
$pageTitle = 'Feedback';
$pageBackground = 'admin-dashboard';
$currentPage = 'feedback';
$pageRole = 'admin';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('admin');

$db = Database::getInstance();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$total = (int)$db->fetchColumn("SELECT COUNT(*) FROM feedback");
$totalPages = max(1, ceil($total / $perPage));

$avgRating = round((float)$db->fetchColumn("SELECT AVG(rating) FROM feedback"), 1);
$totalFeedback = (int)$db->fetchColumn("SELECT COUNT(*) FROM feedback");
$ratingDist = $db->fetchAll("SELECT rating, COUNT(*) as cnt FROM feedback GROUP BY rating ORDER BY rating DESC");

$feedbacks = $db->fetchAll(
    "SELECT f.*, u.full_name, u.email, d.name as dept_name, b.name as branch_name
     FROM feedback f JOIN users u ON f.user_id = u.id 
     LEFT JOIN departments d ON f.department_id = d.id
     LEFT JOIN branches b ON f.branch_id = b.id
     ORDER BY f.created_at DESC LIMIT $perPage OFFSET $offset"
);

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header"><h1 class="page-header-title"><i class="bi bi-chat-square-text" style="color:var(--accent-warning);"></i> Customer Feedback</h1></div>

<!-- Summary -->
<div class="metrics-row animate-fade-up">
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-warning);">
        <div class="metric-card-icon"><i class="bi bi-star-fill"></i></div>
        <div><div class="metric-value"><?= $avgRating ?: '—' ?></div><div class="metric-label">Avg Rating</div></div>
    </div>
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-primary);">
        <div class="metric-card-icon"><i class="bi bi-chat-dots"></i></div>
        <div><div class="metric-value"><?= $totalFeedback ?></div><div class="metric-label">Total Reviews</div></div>
    </div>
    <?php foreach ($ratingDist as $rd): ?>
    <div class="metric-card glass-surface" style="--metric-accent:var(--accent-<?= $rd['rating'] >= 4 ? 'success' : ($rd['rating'] >= 3 ? 'warning' : 'danger') ?>);">
        <div class="metric-card-icon"><i class="bi bi-star-fill"></i></div>
        <div><div class="metric-value"><?= $rd['cnt'] ?></div><div class="metric-label"><?= $rd['rating'] ?> Stars</div></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="glass-surface card-tf mt-6 animate-fade-up delay-2">
    <?php foreach ($feedbacks as $fb): ?>
    <div class="queue-list-item" style="align-items:flex-start;">
        <div class="avatar avatar-sm"><?= getInitials($fb['full_name']) ?></div>
        <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
                <strong class="text-sm"><?= e($fb['full_name']) ?></strong>
                <span class="text-xs text-muted"><?= timeAgo($fb['created_at']) ?></span>
                <span class="badge-tf badge-secondary" style="font-size:9px;"><?= ucfirst($fb['category']) ?></span>
            </div>
            <div>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="bi <?= $i <= $fb['rating'] ? 'bi-star-fill' : 'bi-star' ?>" style="color:var(--accent-warning);font-size:12px;"></i>
                <?php endfor; ?>
            </div>
            <?php if ($fb['comment']): ?>
            <p class="text-sm text-secondary mt-1"><?= e($fb['comment']) ?></p>
            <?php endif; ?>
            <div class="text-xs text-muted mt-1"><?= e($fb['branch_name'] ?? '') ?><?= $fb['dept_name'] ? ' — '.e($fb['dept_name']) : '' ?></div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center mt-4">
        <div class="pagination-tf">
            <a href="?page=<?= max(1, $page - 1) ?>" class="page-btn" <?= $page <= 1 ? 'disabled' : '' ?>><i class="bi bi-chevron-left"></i></a>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="?page=<?= $i ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a href="?page=<?= min($totalPages, $page + 1) ?>" class="page-btn" <?= $page >= $totalPages ? 'disabled' : '' ?>><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
