<?php
/**
 * TokenFlow Pro — Admin: Analytics Dashboard
 */
$pageTitle = 'Analytics';
$pageBackground = 'analytics';
$currentPage = 'analytics';
$pageRole = 'admin';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('admin');

$db = Database::getInstance();
$range = $_GET['range'] ?? '7';

// Summary
$summary = $db->fetch(
    "SELECT COUNT(*) as total, SUM(status='completed') as completed, SUM(status='skipped') as skipped,
            SUM(status='cancelled') as cancelled,
            AVG(CASE WHEN status='completed' THEN actual_wait_minutes END) as avg_wait,
            AVG(CASE WHEN status='completed' THEN actual_service_minutes END) as avg_service,
            MIN(created_at) as first_token
     FROM tokens WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)", [$range]
);

// Daily trend
$dailyTrend = $db->fetchAll(
    "SELECT date, COUNT(*) as total, SUM(status='completed') as completed, SUM(status='waiting') as waiting,
            AVG(CASE WHEN status='completed' THEN actual_wait_minutes END) as avg_wait
     FROM tokens WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     GROUP BY date ORDER BY date", [$range]
);

// Top services
$topServices = $db->fetchAll(
    "SELECT s.name, COUNT(*) as cnt, AVG(t.actual_wait_minutes) as avg_wait
     FROM tokens t JOIN services s ON t.service_id = s.id
     WHERE t.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     GROUP BY s.id ORDER BY cnt DESC LIMIT 8", [$range]
);

// Hourly distribution
$hourlyDist = $db->fetchAll(
    "SELECT HOUR(created_at) as hr, COUNT(*) as cnt 
     FROM tokens WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     GROUP BY HOUR(created_at) ORDER BY hr", [$range]
);

// Staff leaderboard
$staffBoard = $db->fetchAll(
    "SELECT u.full_name, COUNT(*) as total, SUM(t.status='completed') as completed,
            AVG(CASE WHEN t.status='completed' THEN t.actual_service_minutes END) as avg_service
     FROM tokens t JOIN users u ON t.served_by = u.id
     WHERE t.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND t.served_by IS NOT NULL
     GROUP BY u.id ORDER BY completed DESC LIMIT 10", [$range]
);

$pageScripts = ['charts.js'];
include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <h1 class="page-header-title"><i class="bi bi-bar-chart-line" style="color:var(--accent-primary);"></i> Analytics</h1>
        <div class="page-header-actions">
            <div class="tabs-tf">
                <a href="?range=1" class="tab-tf <?= $range == 1 ? 'active' : '' ?>">Today</a>
                <a href="?range=7" class="tab-tf <?= $range == 7 ? 'active' : '' ?>">7 Days</a>
                <a href="?range=30" class="tab-tf <?= $range == 30 ? 'active' : '' ?>">30 Days</a>
                <a href="?range=90" class="tab-tf <?= $range == 90 ? 'active' : '' ?>">90 Days</a>
            </div>
            <button class="btn-tf btn-secondary" onclick="exportCSV()"><i class="bi bi-download"></i> Export</button>
        </div>
    </div>
</div>

<!-- Summary Metrics -->
<div class="metrics-row animate-fade-up">
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-primary);">
        <div class="metric-card-icon"><i class="bi bi-ticket-perforated"></i></div>
        <div><div class="metric-value"><?= number_format($summary['total'] ?? 0) ?></div><div class="metric-label">Total Tokens</div></div>
    </div>
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-success);">
        <div class="metric-card-icon"><i class="bi bi-check-circle"></i></div>
        <div><div class="metric-value"><?= number_format($summary['completed'] ?? 0) ?></div><div class="metric-label">Completed</div></div>
    </div>
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-info);">
        <div class="metric-card-icon"><i class="bi bi-clock"></i></div>
        <div><div class="metric-value"><?= round($summary['avg_wait'] ?? 0) ?> min</div><div class="metric-label">Avg Wait</div></div>
    </div>
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-secondary);">
        <div class="metric-card-icon"><i class="bi bi-stopwatch"></i></div>
        <div><div class="metric-value"><?= round($summary['avg_service'] ?? 0) ?> min</div><div class="metric-label">Avg Service</div></div>
    </div>
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-danger);">
        <div class="metric-card-icon"><i class="bi bi-x-circle"></i></div>
        <div><div class="metric-value"><?= ($summary['skipped'] ?? 0) + ($summary['cancelled'] ?? 0) ?></div><div class="metric-label">Skipped/Cancelled</div></div>
    </div>
</div>

<div class="grid-2 mt-6">
    <!-- Token Trend -->
    <div class="glass-surface card-tf chart-card animate-fade-up delay-2">
        <div class="card-title-tf mb-4"><i class="bi bi-graph-up"></i> Token Trend</div>
        <canvas id="trend-chart" height="280"></canvas>
    </div>
    <!-- Hourly Distribution -->
    <div class="glass-surface card-tf chart-card animate-fade-up delay-3">
        <div class="card-title-tf mb-4"><i class="bi bi-clock-history"></i> Hourly Distribution</div>
        <canvas id="hourly-dist-chart" height="280"></canvas>
    </div>
</div>

<div class="grid-2 mt-6">
    <!-- Top Services -->
    <div class="glass-surface card-tf animate-fade-up delay-4">
        <div class="card-title-tf mb-4"><i class="bi bi-trophy"></i> Top Services</div>
        <?php foreach ($topServices as $i => $svc): ?>
        <div class="queue-list-item">
            <div class="queue-list-position"><?= $i + 1 ?></div>
            <span class="text-sm flex-1"><?= e($svc['name']) ?></span>
            <span class="badge-tf badge-primary"><?= $svc['cnt'] ?> tokens</span>
            <span class="text-xs text-muted">~<?= round($svc['avg_wait'] ?? 0) ?> min wait</span>
        </div>
        <?php endforeach; ?>
    </div>
    <!-- Staff Leaderboard -->
    <div class="glass-surface card-tf animate-fade-up delay-5">
        <div class="card-title-tf mb-4"><i class="bi bi-people"></i> Staff Leaderboard</div>
        <?php foreach ($staffBoard as $i => $staff): ?>
        <div class="queue-list-item">
            <div class="queue-list-position"><?= $i + 1 ?></div>
            <div class="avatar avatar-sm"><?= getInitials($staff['full_name']) ?></div>
            <span class="text-sm flex-1"><?= e($staff['full_name']) ?></span>
            <span class="badge-tf badge-success"><?= $staff['completed'] ?> done</span>
            <span class="text-xs text-muted">~<?= round($staff['avg_service'] ?? 0) ?> min</span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Chart === 'undefined') return;
    
    // Trend chart
    const trendCtx = document.getElementById('trend-chart');
    if (trendCtx) {
        const trendData = <?= json_encode($dailyTrend) ?>;
        new Chart(trendCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: trendData.map(d => new Date(d.date).toLocaleDateString('en-US', {month:'short',day:'numeric'})),
                datasets: [{
                    label: 'Total', data: trendData.map(d => d.total),
                    borderColor: 'rgba(56,189,248,0.8)', backgroundColor: 'rgba(56,189,248,0.1)', fill: true, tension: 0.4
                },{
                    label: 'Completed', data: trendData.map(d => d.completed),
                    borderColor: 'rgba(52,211,153,0.8)', backgroundColor: 'rgba(52,211,153,0.1)', fill: true, tension: 0.4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { labels: { color: 'rgba(148,163,184,0.7)' } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(148,163,184,0.6)' } },
                    x: { grid: { display: false }, ticks: { color: 'rgba(148,163,184,0.6)', maxTicksLimit: 10 } }
                }
            }
        });
    }
    
    // Hourly distribution
    const hourlyCtx = document.getElementById('hourly-dist-chart');
    if (hourlyCtx) {
        const hourlyData = <?= json_encode($hourlyDist) ?>;
        const labels = [], data = [];
        for (let h = 7; h <= 19; h++) {
            labels.push(h > 12 ? (h-12)+'PM' : (h===12?'12PM':h+'AM'));
            const f = hourlyData.find(d => parseInt(d.hr) === h);
            data.push(f ? parseInt(f.cnt) : 0);
        }
        new Chart(hourlyCtx.getContext('2d'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Tokens', data, backgroundColor: 'rgba(167,139,250,0.5)', borderColor: 'rgba(167,139,250,1)', borderWidth: 1, borderRadius: 6 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(148,163,184,0.6)' } },
                    x: { grid: { display: false }, ticks: { color: 'rgba(148,163,184,0.6)' } }
                }
            }
        });
    }
});

function exportCSV() {
    const trend = <?= json_encode($dailyTrend) ?>;
    const rows = [['Date','Total','Completed','Avg Wait']];
    trend.forEach(d => rows.push([d.date, d.total, d.completed, Math.round(d.avg_wait||0)]));
    downloadCSV(rows, 'tokenflow-analytics.csv');
    Toast.success('Exported', 'CSV file downloaded.');
}
</script>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
