<?php
/**
 * TokenFlow Pro — Staff Performance
 */
$pageTitle = 'My Performance';
$pageBackground = 'staff-dashboard';
$currentPage = 'performance';
$pageRole = 'staff';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('staff');

$userId = Auth::getUserId();
$db = Database::getInstance();

// Today stats
$today = $db->fetch(
    "SELECT COUNT(*) as total, 
            SUM(status='completed') as completed, SUM(status='skipped') as skipped,
            AVG(CASE WHEN status='completed' THEN actual_service_minutes END) as avg_service,
            AVG(CASE WHEN status='completed' THEN actual_wait_minutes END) as avg_wait
     FROM tokens WHERE served_by = ? AND date = CURDATE()", [$userId]
);

// This week
$week = $db->fetch(
    "SELECT COUNT(*) as total, SUM(status='completed') as completed,
            AVG(CASE WHEN status='completed' THEN actual_service_minutes END) as avg_service
     FROM tokens WHERE served_by = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)", [$userId]
);

// Daily breakdown (last 7 days)
$daily = $db->fetchAll(
    "SELECT date, COUNT(*) as total, SUM(status='completed') as completed
     FROM tokens WHERE served_by = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY date ORDER BY date", [$userId]
);

$pageScripts = ['charts.js'];
include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <h1 class="page-header-title"><i class="bi bi-graph-up" style="color:var(--accent-success);"></i> My Performance</h1>
</div>

<div class="metrics-row animate-fade-up">
    <div class="metric-card glass-surface" style="--metric-accent:var(--accent-primary);">
        <div class="metric-card-icon"><i class="bi bi-ticket-perforated"></i></div>
        <div><div class="metric-value"><?= $today['total'] ?? 0 ?></div><div class="metric-label">Today Total</div></div>
    </div>
    <div class="metric-card glass-surface" style="--metric-accent:var(--accent-success);">
        <div class="metric-card-icon"><i class="bi bi-check-circle"></i></div>
        <div><div class="metric-value"><?= $today['completed'] ?? 0 ?></div><div class="metric-label">Completed</div></div>
    </div>
    <div class="metric-card glass-surface" style="--metric-accent:var(--accent-info);">
        <div class="metric-card-icon"><i class="bi bi-clock"></i></div>
        <div><div class="metric-value"><?= round($today['avg_service'] ?? 0) ?> min</div><div class="metric-label">Avg Service</div></div>
    </div>
    <div class="metric-card glass-surface" style="--metric-accent:var(--accent-secondary);">
        <div class="metric-card-icon"><i class="bi bi-calendar-week"></i></div>
        <div><div class="metric-value"><?= $week['completed'] ?? 0 ?></div><div class="metric-label">This Week</div></div>
    </div>
</div>

<div class="grid-2 mt-6">
    <div class="glass-surface card-tf chart-card animate-fade-up delay-2">
        <div class="card-title-tf mb-4"><i class="bi bi-bar-chart"></i> Daily Tokens (7 days)</div>
        <canvas id="daily-chart" height="260"></canvas>
    </div>
    <div class="glass-surface card-tf animate-fade-up delay-3">
        <div class="card-title-tf mb-4"><i class="bi bi-trophy"></i> Summary</div>
        <div class="qi-items">
            <div class="qi-item">
                <div class="qi-item-icon" style="background:rgba(var(--accent-success-rgb),0.1);color:var(--accent-success);"><i class="bi bi-check2-all"></i></div>
                <span class="qi-item-text">Completion Rate</span>
                <span class="qi-item-value"><?= $today['total'] > 0 ? round(($today['completed'] / $today['total']) * 100) : 0 ?>%</span>
            </div>
            <div class="qi-item">
                <div class="qi-item-icon" style="background:rgba(var(--accent-warning-rgb),0.1);color:var(--accent-warning);"><i class="bi bi-hourglass-split"></i></div>
                <span class="qi-item-text">Avg Customer Wait</span>
                <span class="qi-item-value"><?= round($today['avg_wait'] ?? 0) ?> min</span>
            </div>
            <div class="qi-item">
                <div class="qi-item-icon" style="background:rgba(var(--accent-danger-rgb),0.1);color:var(--accent-danger);"><i class="bi bi-skip-forward"></i></div>
                <span class="qi-item-text">Skipped Today</span>
                <span class="qi-item-value"><?= $today['skipped'] ?? 0 ?></span>
            </div>
            <div class="qi-item">
                <div class="qi-item-icon" style="background:rgba(var(--accent-info-rgb),0.1);color:var(--accent-info);"><i class="bi bi-clock-history"></i></div>
                <span class="qi-item-text">Week Avg Service</span>
                <span class="qi-item-value"><?= round($week['avg_service'] ?? 0) ?> min</span>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('daily-chart');
    if (!ctx || typeof Chart === 'undefined') return;
    
    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($daily, 'date')) ?>.map(d => new Date(d).toLocaleDateString('en-US', {weekday:'short'})),
            datasets: [{
                label: 'Completed',
                data: <?= json_encode(array_map('intval', array_column($daily, 'completed'))) ?>,
                backgroundColor: 'rgba(52, 211, 153, 0.6)',
                borderColor: 'rgba(52, 211, 153, 1)',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(148,163,184,0.6)' } },
                x: { grid: { display: false }, ticks: { color: 'rgba(148,163,184,0.6)' } }
            }
        }
    });
});
</script>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
