<?php
/**
 * TokenFlow Pro — Admin Customers Management
 */
$pageTitle = 'Manage Customers';
$pageBackground = 'admin-dashboard';
$currentPage = 'customers';
$pageRole = 'admin';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('admin');

$db = Database::getInstance();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$total = (int)$db->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'customer'");
$totalPages = max(1, ceil($total / $perPage));

$customers = $db->fetchAll(
    "SELECT u.*, (SELECT COUNT(*) FROM tokens t WHERE t.user_id = u.id) as total_tokens
     FROM users u WHERE u.role = 'customer' ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset"
);

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <h1 class="page-header-title"><i class="bi bi-person-lines-fill" style="color:var(--accent-primary);"></i> Registered Customers</h1>
        <span class="text-secondary"><?= number_format($total) ?> total customers</span>
    </div>
</div>

<div class="glass-surface card-tf animate-fade-up">
    <div class="table-container">
        <table class="table-tf">
            <thead><tr><th>Customer</th><th>Email</th><th>Phone</th><th>Joined</th><th>Total Tokens</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                <tr>
                    <td class="flex items-center gap-2">
                        <div class="avatar avatar-sm"><?= getInitials($c['full_name']) ?></div>
                        <strong><?= e($c['full_name']) ?></strong>
                    </td>
                    <td class="text-sm text-muted"><?= e($c['email']) ?></td>
                    <td class="text-sm"><?= e($c['phone'] ?? '-') ?></td>
                    <td class="text-xs text-muted"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                    <td><span class="badge-tf badge-primary"><?= $c['total_tokens'] ?></span></td>
                    <td><span class="badge-tf <?= $c['is_active'] ? 'badge-success' : 'badge-secondary' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
