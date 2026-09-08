<?php
/**
 * TokenFlow Pro — Admin: Audit Logs
 */
$pageTitle = 'Audit Logs';
$pageBackground = 'admin-dashboard';
$currentPage = 'audit-logs';
$pageRole = 'admin';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('admin');

$db = Database::getInstance();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$total = (int)$db->fetchColumn("SELECT COUNT(*) FROM audit_logs");
$totalPages = max(1, ceil($total / $perPage));

$logs = $db->fetchAll(
    "SELECT a.*, u.full_name, u.email FROM audit_logs a
     LEFT JOIN users u ON a.user_id = u.id
     ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset"
);

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <h1 class="page-header-title"><i class="bi bi-shield-check" style="color:var(--accent-primary);"></i> Audit Logs</h1>
        <span class="text-secondary"><?= number_format($total) ?> total entries</span>
    </div>
</div>

<div class="glass-surface card-tf animate-fade-up">
    <div class="table-container" style="max-height:600px;overflow-y:auto;">
        <table class="table-tf">
            <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th></tr></thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="text-xs text-muted" style="white-space:nowrap;"><?= date('M d g:i:s A', strtotime($log['created_at'])) ?></td>
                    <td class="text-sm"><?= e($log['full_name'] ?? 'System') ?></td>
                    <td><span class="badge-tf badge-<?= strpos($log['action'], 'delete') !== false ? 'danger' : (strpos($log['action'], 'create') !== false ? 'success' : 'info') ?>"><?= e($log['action']) ?></span></td>
                    <td class="text-sm"><?= e($log['entity_type'] ?? '-') ?><?= $log['entity_id'] ? ' #'.$log['entity_id'] : '' ?></td>
                    <td class="text-xs text-secondary truncate" style="max-width:200px;"><?= e($log['details'] ?? '-') ?></td>
                    <td class="text-xs text-mono text-muted"><?= e($log['ip_address'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?><tr><td colspan="6" class="text-center text-secondary p-6">No logs.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    
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
