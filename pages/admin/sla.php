<?php
/**
 * TokenFlow Pro — Admin SLA Rules & Monitoring
 */
$pageTitle = 'SLA Monitoring';
$pageBackground = 'admin-dashboard';
$currentPage = 'sla';
$pageRole = 'admin';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('admin');

$db = Database::getInstance();

$slaRules = $db->fetchAll(
    "SELECT sr.*, s.name as service_name, d.name as dept_name
     FROM sla_rules sr JOIN services s ON sr.service_id = s.id JOIN departments d ON s.department_id = d.id
     ORDER BY d.name, s.name"
);

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <h1 class="page-header-title"><i class="bi bi-speedometer" style="color:var(--accent-warning);"></i> Service Level Agreements (SLA)</h1>
</div>

<div class="glass-surface card-tf animate-fade-up">
    <div class="table-container">
        <table class="table-tf">
            <thead><tr><th>Service</th><th>Department</th><th>Max Wait</th><th>Max Service</th><th>Target Compliance</th></tr></thead>
            <tbody>
                <?php foreach ($slaRules as $rule): ?>
                <tr>
                    <td><strong><?= e($rule['service_name']) ?></strong></td>
                    <td><?= e($rule['dept_name']) ?></td>
                    <td><span class="badge-tf badge-warning"><?= $rule['max_wait_minutes'] ?> min</span></td>
                    <td><span class="badge-tf badge-info"><?= $rule['max_service_minutes'] ?> min</span></td>
                    <td><span class="badge-tf badge-success"><?= $rule['target_compliance_pct'] ?>%</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
