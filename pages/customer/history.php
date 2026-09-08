<?php
/**
 * TokenFlow Pro — Token History
 */
$pageTitle = 'Token History';
$pageBackground = 'customer-dashboard';
$currentPage = 'history';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireLogin();

$user = Auth::getCurrentUser();
$pageRole = $user['role'];
$userId = $user['id'];

$db = Database::getInstance();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;
$status = $_GET['status'] ?? '';

$where = "t.user_id = ?";
$params = [$userId];

if ($status && in_array($status, ['completed', 'skipped', 'cancelled'])) {
    $where .= " AND t.status = ?";
    $params[] = $status;
}

$totalTokens = $db->fetchColumn("SELECT COUNT(*) FROM tokens t WHERE $where", $params);
$totalPages = max(1, ceil($totalTokens / $perPage));

$tokens = $db->fetchAll(
    "SELECT t.*, s.name as service_name, d.name as department_name, b.name as branch_name
     FROM tokens t
     JOIN services s ON t.service_id = s.id
     JOIN departments d ON t.department_id = d.id
     JOIN branches b ON t.branch_id = b.id
     WHERE $where ORDER BY t.created_at DESC LIMIT $perPage OFFSET $offset",
    $params
);

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <h1 class="page-header-title">Token History</h1>
        <div class="page-header-actions">
            <select class="form-select" onchange="location.href='?status='+this.value" style="width:160px;">
                <option value="">All Status</option>
                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="skipped" <?= $status === 'skipped' ? 'selected' : '' ?>>Skipped</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
    </div>
</div>

<div class="glass-surface card-tf animate-fade-up">
    <div class="table-container">
        <table class="table-tf">
            <thead>
                <tr>
                    <th>Token</th>
                    <th>Service</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>Wait</th>
                    <th>Service Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tokens)): ?>
                <tr><td colspan="7" class="text-center text-secondary p-8">No tokens found.</td></tr>
                <?php else: ?>
                <?php foreach ($tokens as $token): ?>
                <tr>
                    <td><span class="token-number" style="font-size:var(--text-sm);"><?= e($token['display_number']) ?></span></td>
                    <td><?= e($token['service_name']) ?></td>
                    <td><?= e($token['department_name']) ?></td>
                    <td><?= date('M d, g:i A', strtotime($token['created_at'])) ?></td>
                    <td><?= $token['actual_wait_minutes'] !== null ? $token['actual_wait_minutes'] . ' min' : '-' ?></td>
                    <td><?= $token['actual_service_minutes'] !== null ? $token['actual_service_minutes'] . ' min' : '-' ?></td>
                    <td><span class="badge-tf <?= getStatusBadgeClass($token['status']) ?>"><?= getStatusLabel($token['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center mt-6">
        <div class="pagination-tf">
            <a href="?page=<?= max(1, $page - 1) ?>&status=<?= e($status) ?>" class="page-btn" <?= $page <= 1 ? 'disabled' : '' ?>>
                <i class="bi bi-chevron-left"></i>
            </a>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="?page=<?= $i ?>&status=<?= e($status) ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a href="?page=<?= min($totalPages, $page + 1) ?>&status=<?= e($status) ?>" class="page-btn" <?= $page >= $totalPages ? 'disabled' : '' ?>>
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
