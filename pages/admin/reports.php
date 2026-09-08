<?php
/**
 * TokenFlow Pro — Admin: Reports
 */
$pageTitle = 'Reports';
$pageBackground = 'analytics';
$currentPage = 'reports';
$pageRole = 'admin';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('admin');

$db = Database::getInstance();

$dateFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['to'] ?? date('Y-m-d');

// Department report
$deptReport = $db->fetchAll(
    "SELECT d.name, COUNT(*) as total, SUM(t.status='completed') as completed, SUM(t.status='skipped') as skipped,
            AVG(CASE WHEN t.status='completed' THEN t.actual_wait_minutes END) as avg_wait,
            AVG(CASE WHEN t.status='completed' THEN t.actual_service_minutes END) as avg_service
     FROM tokens t JOIN departments d ON t.department_id = d.id
     WHERE t.date BETWEEN ? AND ? GROUP BY d.id ORDER BY total DESC", [$dateFrom, $dateTo]
);

// Staff report
$staffReport = $db->fetchAll(
    "SELECT u.full_name, COUNT(*) as total, SUM(t.status='completed') as completed,
            AVG(CASE WHEN t.status='completed' THEN t.actual_service_minutes END) as avg_service,
            AVG(CASE WHEN t.status='completed' THEN t.actual_wait_minutes END) as avg_wait
     FROM tokens t JOIN users u ON t.served_by = u.id
     WHERE t.date BETWEEN ? AND ? AND t.served_by IS NOT NULL
     GROUP BY u.id ORDER BY completed DESC", [$dateFrom, $dateTo]
);

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <h1 class="page-header-title"><i class="bi bi-file-earmark-text" style="color:var(--accent-primary);"></i> Reports</h1>
        <form class="flex items-center gap-3">
            <input type="date" class="form-input" name="from" value="<?= e($dateFrom) ?>" style="width:160px;">
            <span class="text-muted">to</span>
            <input type="date" class="form-input" name="to" value="<?= e($dateTo) ?>" style="width:160px;">
            <button type="submit" class="btn-tf btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        </form>
    </div>
</div>

<!-- Department Report -->
<div class="glass-surface card-tf animate-fade-up mb-6">
    <div class="card-header-tf">
        <div class="card-title-tf"><i class="bi bi-diagram-3"></i> Department Report</div>
        <button class="btn-tf btn-ghost btn-sm" onclick="exportDeptCSV()"><i class="bi bi-download"></i> CSV</button>
    </div>
    <div class="table-container">
        <table class="table-tf" id="dept-report-table">
            <thead><tr><th>Department</th><th>Total</th><th>Completed</th><th>Skipped</th><th>Completion %</th><th>Avg Wait</th><th>Avg Service</th></tr></thead>
            <tbody>
                <?php foreach ($deptReport as $d): ?>
                <tr>
                    <td><strong><?= e($d['name']) ?></strong></td>
                    <td><?= $d['total'] ?></td>
                    <td class="text-success"><?= $d['completed'] ?></td>
                    <td class="text-danger"><?= $d['skipped'] ?></td>
                    <td><span class="badge-tf <?= ($d['total'] > 0 && $d['completed'] / $d['total'] >= 0.9) ? 'badge-success' : 'badge-warning' ?>"><?= $d['total'] > 0 ? round($d['completed'] / $d['total'] * 100) : 0 ?>%</span></td>
                    <td><?= round($d['avg_wait'] ?? 0) ?> min</td>
                    <td><?= round($d['avg_service'] ?? 0) ?> min</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Staff Report -->
<div class="glass-surface card-tf animate-fade-up delay-2">
    <div class="card-header-tf">
        <div class="card-title-tf"><i class="bi bi-people"></i> Staff Report</div>
        <button class="btn-tf btn-ghost btn-sm" onclick="exportStaffCSV()"><i class="bi bi-download"></i> CSV</button>
    </div>
    <div class="table-container">
        <table class="table-tf" id="staff-report-table">
            <thead><tr><th>Staff</th><th>Total Tokens</th><th>Completed</th><th>Completion %</th><th>Avg Service</th><th>Avg Wait</th></tr></thead>
            <tbody>
                <?php foreach ($staffReport as $s): ?>
                <tr>
                    <td class="flex items-center gap-2"><div class="avatar avatar-sm"><?= getInitials($s['full_name']) ?></div> <strong><?= e($s['full_name']) ?></strong></td>
                    <td><?= $s['total'] ?></td>
                    <td class="text-success"><?= $s['completed'] ?></td>
                    <td><?= $s['total'] > 0 ? round($s['completed'] / $s['total'] * 100) : 0 ?>%</td>
                    <td><?= round($s['avg_service'] ?? 0) ?> min</td>
                    <td><?= round($s['avg_wait'] ?? 0) ?> min</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportDeptCSV() {
    const rows = [['Department','Total','Completed','Skipped','Avg Wait','Avg Service']];
    <?php foreach ($deptReport as $d): ?>
    rows.push([<?= json_encode($d['name']) ?>,<?= $d['total'] ?>,<?= $d['completed'] ?>,<?= $d['skipped'] ?>,<?= round($d['avg_wait'] ?? 0) ?>,<?= round($d['avg_service'] ?? 0) ?>]);
    <?php endforeach; ?>
    downloadCSV(rows, 'department-report.csv');
}
function exportStaffCSV() {
    const rows = [['Staff','Total','Completed','Avg Service','Avg Wait']];
    <?php foreach ($staffReport as $s): ?>
    rows.push([<?= json_encode($s['full_name']) ?>,<?= $s['total'] ?>,<?= $s['completed'] ?>,<?= round($s['avg_service'] ?? 0) ?>,<?= round($s['avg_wait'] ?? 0) ?>]);
    <?php endforeach; ?>
    downloadCSV(rows, 'staff-report.csv');
}
</script>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
