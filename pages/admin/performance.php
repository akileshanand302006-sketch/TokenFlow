<?php
/**
 * TokenFlow Pro — Admin: Staff Performance Analytics
 */
$pageTitle = 'Staff Performance';
$pageBackground = 'admin-dashboard';
$currentPage = 'performance';
$pageRole = 'admin';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('admin');

$db = Database::getInstance();
$orgId = $_SESSION['org_id'] ?? 1;

// Overview metrics today
$todayStats = $db->fetch(
    "SELECT COUNT(*) as total_tokens,
            SUM(t.status='completed') as completed,
            SUM(t.status='skipped') as skipped,
            AVG(CASE WHEN t.status='completed' THEN t.actual_service_minutes END) as avg_service,
            AVG(CASE WHEN t.status='completed' THEN t.actual_wait_minutes END) as avg_wait
     FROM tokens t WHERE t.date = CURDATE() AND t.served_by IS NOT NULL"
);

// Performance by staff member today
$staffPerf = $db->fetchAll(
    "SELECT u.id, u.full_name, u.email,
            MAX(c.number) as counter_number, MAX(d.name) as dept_name,
            COUNT(t.id) as total_called,
            SUM(t.status='completed') as completed,
            SUM(t.status='skipped') as skipped,
            AVG(CASE WHEN t.status='completed' THEN t.actual_service_minutes END) as avg_service_time
     FROM users u
     LEFT JOIN staff_assignments sa ON sa.user_id = u.id
     LEFT JOIN counters c ON sa.counter_id = c.id
     LEFT JOIN departments d ON sa.department_id = d.id
     LEFT JOIN tokens t ON t.served_by = u.id AND t.date = CURDATE()
     WHERE u.role = 'staff' AND u.org_id = ?
     GROUP BY u.id, u.full_name, u.email ORDER BY completed DESC", [$orgId]
);

$pageScripts = ['charts.js'];
include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <h1 class="page-header-title"><i class="bi bi-trophy" style="color:var(--accent-warning);"></i> Staff Performance Analytics</h1>
        <a href="<?= PAGES_URL ?>admin/reports.php" class="btn-tf btn-secondary"><i class="bi bi-file-earmark-text"></i> Detailed Reports</a>
    </div>
</div>

<!-- Overview Stats -->
<div class="metrics-row animate-fade-up">
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-success);">
        <div class="metric-card-icon"><i class="bi bi-check-circle"></i></div>
        <div>
            <div class="metric-value"><?= number_format($todayStats['completed'] ?? 0) ?></div>
            <div class="metric-label">Completed Today</div>
        </div>
    </div>
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-info);">
        <div class="metric-card-icon"><i class="bi bi-stopwatch"></i></div>
        <div>
            <div class="metric-value"><?= round($todayStats['avg_service'] ?? 0) ?> min</div>
            <div class="metric-label">Avg Service Duration</div>
        </div>
    </div>
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-warning);">
        <div class="metric-card-icon"><i class="bi bi-clock-history"></i></div>
        <div>
            <div class="metric-value"><?= round($todayStats['avg_wait'] ?? 0) ?> min</div>
            <div class="metric-label">Avg Wait Duration</div>
        </div>
    </div>
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-danger);">
        <div class="metric-card-icon"><i class="bi bi-skip-forward"></i></div>
        <div>
            <div class="metric-value"><?= number_format($todayStats['skipped'] ?? 0) ?></div>
            <div class="metric-label">Skipped Today</div>
        </div>
    </div>
</div>

<!-- Staff Performance Table -->
<div class="glass-surface card-tf mt-6 animate-fade-up delay-2">
    <div class="card-header-tf">
        <div class="card-title-tf"><i class="bi bi-people"></i> Today's Staff Breakdown</div>
    </div>
    <div class="table-container">
        <table class="table-tf">
            <thead>
                <tr>
                    <th>Staff Member</th>
                    <th>Department</th>
                    <th>Counter</th>
                    <th>Tokens Served</th>
                    <th>Completed</th>
                    <th>Skipped</th>
                    <th>Avg Service Time</th>
                    <th>Efficiency</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staffPerf as $s): ?>
                <?php $pct = $s['total_called'] > 0 ? round(($s['completed'] / $s['total_called']) * 100) : 0; ?>
                <tr>
                    <td class="flex items-center gap-2">
                        <div class="avatar avatar-sm"><?= getInitials($s['full_name']) ?></div>
                        <strong><?= e($s['full_name']) ?></strong>
                    </td>
                    <td><?= e($s['dept_name'] ?? '-') ?></td>
                    <td><?= $s['counter_number'] ? 'Counter ' . $s['counter_number'] : '-' ?></td>
                    <td><?= $s['total_called'] ?></td>
                    <td><span class="badge-tf badge-success"><?= $s['completed'] ?></span></td>
                    <td><span class="badge-tf badge-danger"><?= $s['skipped'] ?></span></td>
                    <td><?= round($s['avg_service_time'] ?? 0) ?> min</td>
                    <td>
                        <div class="flex items-center gap-2" style="width:140px;">
                            <div class="progress-tf flex-1">
                                <div class="progress-tf-bar <?= $pct >= 85 ? 'success' : ($pct >= 60 ? 'warning' : 'danger') ?>" style="width:<?= $pct ?>%"></div>
                            </div>
                            <span class="text-xs text-muted"><?= $pct ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($staffPerf)): ?>
                <tr><td colspan="8" class="text-center text-secondary p-6">No staff members found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
