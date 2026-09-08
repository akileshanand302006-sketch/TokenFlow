<?php
/**
 * TokenFlow Pro — Admin Command Center
 * System-wide operations dashboard
 */
$pageTitle = 'Command Center';
$pageBackground = 'admin-dashboard';
$currentPage = 'dashboard';
$pageRole = 'admin';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('admin');

$db = Database::getInstance();

// System-wide stats
$todayTokens = (int)$db->fetchColumn("SELECT COUNT(*) FROM tokens WHERE date = CURDATE()");
$todayCompleted = (int)$db->fetchColumn("SELECT COUNT(*) FROM tokens WHERE date = CURDATE() AND status = 'completed'");
$todayWaiting = (int)$db->fetchColumn("SELECT COUNT(*) FROM tokens WHERE date = CURDATE() AND status = 'waiting'");
$todayServing = (int)$db->fetchColumn("SELECT COUNT(*) FROM tokens WHERE date = CURDATE() AND status = 'serving'");
$avgWait = round((float)$db->fetchColumn("SELECT AVG(actual_wait_minutes) FROM tokens WHERE date = CURDATE() AND status = 'completed' AND actual_wait_minutes IS NOT NULL"));
$avgService = round((float)$db->fetchColumn("SELECT AVG(actual_service_minutes) FROM tokens WHERE date = CURDATE() AND status = 'completed' AND actual_service_minutes IS NOT NULL"));

// Counters
$totalCounters = (int)$db->fetchColumn("SELECT COUNT(*) FROM counters");
$openCounters = (int)$db->fetchColumn("SELECT COUNT(*) FROM counters WHERE status = 'open'");

// Staff online
$onlineStaff = (int)$db->fetchColumn("SELECT COUNT(*) FROM staff_assignments WHERE is_online = 1");

// Customers
$totalCustomers = (int)$db->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'customer'");

// SLA compliance
$slaCompliant = (int)$db->fetchColumn(
    "SELECT COUNT(*) FROM tokens t JOIN sla_rules sr ON t.service_id = sr.service_id 
     WHERE t.date = CURDATE() AND t.status = 'completed' AND t.actual_wait_minutes <= sr.max_wait_minutes"
);
$slaPct = $todayCompleted > 0 ? round(($slaCompliant / $todayCompleted) * 100) : 100;

// Department breakdown
$departments = $db->fetchAll(
    "SELECT d.id, d.name, d.icon, d.color,
            (SELECT COUNT(*) FROM tokens t WHERE t.department_id = d.id AND t.date = CURDATE() AND t.status = 'waiting') as waiting,
            (SELECT COUNT(*) FROM tokens t WHERE t.department_id = d.id AND t.date = CURDATE() AND t.status = 'completed') as completed,
            (SELECT COUNT(*) FROM tokens t WHERE t.department_id = d.id AND t.date = CURDATE()) as total
     FROM departments d WHERE d.is_active = 1 ORDER BY d.sort_order"
);

// Hourly flow (last 8 hours)
$hourly = $db->fetchAll(
    "SELECT HOUR(created_at) as hr, COUNT(*) as cnt 
     FROM tokens WHERE date = CURDATE() GROUP BY HOUR(created_at) ORDER BY hr"
);

// Recent feedback
$recentFeedback = $db->fetchAll(
    "SELECT f.*, u.full_name, d.name as dept_name FROM feedback f
     JOIN users u ON f.user_id = u.id LEFT JOIN departments d ON f.department_id = d.id
     ORDER BY f.created_at DESC LIMIT 5"
);

$avgRating = round((float)$db->fetchColumn("SELECT AVG(rating) FROM feedback WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"), 1);

$pageScripts = ['charts.js'];
include COMPONENTS_PATH . 'app-shell.php';
?>

<!-- Status Header -->
<div class="command-center-header animate-fade-up">
    <div>
        <div class="cc-title">Command Center</div>
        <div class="cc-date"><?= date('l, F j, Y') ?></div>
    </div>
    <div class="cc-status operational">
        <span class="status-dot online pulse"></span>
        All Systems Operational
    </div>
</div>

<!-- Primary Metrics -->
<div class="metrics-row animate-fade-up delay-1">
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-primary);">
        <div class="metric-card-icon"><i class="bi bi-ticket-perforated"></i></div>
        <div><div class="metric-value" data-count-to="<?= $todayTokens ?>"><?= $todayTokens ?></div><div class="metric-label">Today's Tokens</div></div>
    </div>
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-warning);">
        <div class="metric-card-icon"><i class="bi bi-people"></i></div>
        <div>
            <div class="metric-value"><?= $todayWaiting ?></div>
            <div class="metric-label">Currently Waiting</div>
            <?php if ($todayWaiting > 15): ?><div class="metric-change negative"><i class="bi bi-exclamation-triangle"></i> High</div><?php endif; ?>
        </div>
    </div>
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-success);">
        <div class="metric-card-icon"><i class="bi bi-play-circle"></i></div>
        <div><div class="metric-value"><?= $todayServing ?></div><div class="metric-label">Being Served</div></div>
    </div>
    <div class="metric-card glass-elevated" style="--metric-accent:var(--accent-info);">
        <div class="metric-card-icon"><i class="bi bi-clock"></i></div>
        <div><div class="metric-value"><?= $avgWait ?> min</div><div class="metric-label">Avg Wait</div></div>
    </div>
    <div class="metric-card glass-elevated" style="--metric-accent: <?= $slaPct >= 90 ? 'var(--accent-success)' : ($slaPct >= 70 ? 'var(--accent-warning)' : 'var(--accent-danger)') ?>;">
        <div class="metric-card-icon"><i class="bi bi-speedometer"></i></div>
        <div><div class="metric-value"><?= $slaPct ?>%</div><div class="metric-label">SLA Compliance</div></div>
    </div>
</div>

<div class="dashboard-grid mt-6">
    <div class="grid-2">
        <!-- Hourly Flow Chart -->
        <div class="glass-surface card-tf chart-card animate-fade-up delay-2">
            <div class="card-header-tf">
                <div class="card-title-tf"><i class="bi bi-graph-up"></i> Today's Token Flow</div>
            </div>
            <canvas id="hourly-chart" height="250"></canvas>
        </div>

        <!-- Department Queues -->
        <div class="glass-surface card-tf animate-fade-up delay-3">
            <div class="card-header-tf">
                <div class="card-title-tf"><i class="bi bi-diagram-3"></i> Department Status</div>
            </div>
            <div class="flex flex-col gap-3">
                <?php foreach ($departments as $dept): ?>
                <div class="dept-queue-bar">
                    <div class="dept-queue-header">
                        <div class="flex items-center gap-2">
                            <i class="bi <?= e($dept['icon'] ?? 'bi-folder') ?>" style="color:<?= e($dept['color'] ?? 'var(--accent-primary)') ?>;"></i>
                            <span class="dept-queue-name"><?= e($dept['name']) ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="badge-tf <?= $dept['waiting'] > 10 ? 'badge-danger' : ($dept['waiting'] > 5 ? 'badge-warning' : 'badge-success') ?>">
                                <?= $dept['waiting'] ?> waiting
                            </span>
                            <span class="text-xs text-muted"><?= $dept['completed'] ?> done</span>
                        </div>
                    </div>
                    <div class="progress-tf">
                        <?php $pct = $dept['total'] > 0 ? ($dept['completed'] / $dept['total']) * 100 : 0; ?>
                        <div class="progress-tf-bar success" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Secondary Stats -->
    <div class="grid-3 animate-fade-up delay-4">
        <div class="glass-surface card-tf text-center">
            <div class="metric-card-icon" style="margin:0 auto var(--space-3);background:rgba(var(--accent-success-rgb),0.1);color:var(--accent-success);">
                <i class="bi bi-display"></i>
            </div>
            <div class="metric-value"><?= $openCounters ?>/<?= $totalCounters ?></div>
            <div class="metric-label">Counters Open</div>
        </div>
        <div class="glass-surface card-tf text-center">
            <div class="metric-card-icon" style="margin:0 auto var(--space-3);background:rgba(var(--accent-primary-rgb),0.1);color:var(--accent-primary);">
                <i class="bi bi-people"></i>
            </div>
            <div class="metric-value"><?= $onlineStaff ?></div>
            <div class="metric-label">Staff Online</div>
        </div>
        <div class="glass-surface card-tf text-center">
            <div class="metric-card-icon" style="margin:0 auto var(--space-3);background:rgba(var(--accent-warning-rgb),0.1);color:var(--accent-warning);">
                <i class="bi bi-star-fill"></i>
            </div>
            <div class="metric-value"><?= $avgRating ?: '—' ?></div>
            <div class="metric-label">Avg Rating (7d)</div>
        </div>
    </div>

    <!-- Recent Feedback -->
    <div class="glass-surface card-tf animate-fade-up delay-5">
        <div class="card-header-tf">
            <div class="card-title-tf"><i class="bi bi-chat-square-text"></i> Recent Feedback</div>
            <a href="<?= PAGES_URL ?>admin/feedback.php" class="btn-tf btn-ghost btn-sm">View All</a>
        </div>
        <?php if (empty($recentFeedback)): ?>
            <p class="text-secondary text-sm p-4">No feedback yet.</p>
        <?php else: ?>
        <div class="queue-list">
            <?php foreach ($recentFeedback as $fb): ?>
            <div class="queue-list-item">
                <div class="avatar avatar-sm"><?= getInitials($fb['full_name']) ?></div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-weight-600"><?= e($fb['full_name']) ?></span>
                        <span class="text-xs text-muted"><?= timeAgo($fb['created_at']) ?></span>
                    </div>
                    <div class="text-xs text-secondary"><?= e($fb['comment'] ?: ucfirst($fb['category'])) ?></div>
                </div>
                <div>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi <?= $i <= $fb['rating'] ? 'bi-star-fill' : 'bi-star' ?>" style="color:var(--accent-warning);font-size:10px;"></i>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('hourly-chart');
    if (!ctx || typeof Chart === 'undefined') return;
    
    const hourlyData = <?= json_encode($hourly) ?>;
    const labels = [];
    const data = [];
    for (let h = 8; h <= 18; h++) {
        labels.push(h > 12 ? (h-12) + ' PM' : (h === 12 ? '12 PM' : h + ' AM'));
        const found = hourlyData.find(d => parseInt(d.hr) === h);
        data.push(found ? parseInt(found.cnt) : 0);
    }
    
    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Tokens',
                data,
                borderColor: 'rgba(56, 189, 248, 0.9)',
                backgroundColor: 'rgba(56, 189, 248, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(56, 189, 248, 1)',
                pointBorderWidth: 0,
                pointRadius: 3,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(148,163,184,0.6)' } },
                x: { grid: { display: false }, ticks: { color: 'rgba(148,163,184,0.6)', maxTicksLimit: 8 } }
            }
        }
    });
});
</script>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
