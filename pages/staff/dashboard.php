<?php
/**
 * TokenFlow Pro — Staff Dashboard
 * Operational command center for counter staff
 */
$pageTitle = 'Staff Dashboard';
$pageBackground = 'staff-dashboard';
$currentPage = 'dashboard';
$pageRole = 'staff';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('staff');

$user = Auth::getCurrentUser();
$userId = $user['id'];
$db = Database::getInstance();

// Staff assignment
$assignment = $db->fetch(
    "SELECT sa.*, c.name as counter_name, c.number as counter_number, c.status as counter_status,
            d.name as department_name, d.id as department_id, d.icon as dept_icon, d.color as dept_color,
            b.name as branch_name
     FROM staff_assignments sa
     LEFT JOIN counters c ON sa.counter_id = c.id
     LEFT JOIN departments d ON sa.department_id = d.id
     LEFT JOIN branches b ON sa.branch_id = b.id
     WHERE sa.user_id = ? LIMIT 1",
    [$userId]
);

$counterId = $assignment['counter_id'] ?? null;
$deptId = $assignment['department_id'] ?? null;

// Current serving token
$currentToken = null;
if ($counterId) {
    $currentToken = $db->fetch(
        "SELECT t.*, s.name as service_name, u.full_name as customer_name, u.phone as customer_phone
         FROM tokens t
         JOIN services s ON t.service_id = s.id
         LEFT JOIN users u ON t.user_id = u.id
         WHERE t.counter_id = ? AND t.status = 'serving' AND t.date = CURDATE()
         ORDER BY t.service_start_time DESC LIMIT 1",
        [$counterId]
    );
}

// Queue stats for this department
$queueWaiting = $deptId ? (int)$db->fetchColumn(
    "SELECT COUNT(*) FROM tokens WHERE department_id = ? AND date = CURDATE() AND status = 'waiting'", [$deptId]
) : 0;

$completedToday = $db->fetchColumn(
    "SELECT COUNT(*) FROM tokens WHERE served_by = ? AND date = CURDATE() AND status = 'completed'", [$userId]
);

$avgServiceTime = $db->fetchColumn(
    "SELECT AVG(actual_service_minutes) FROM tokens WHERE served_by = ? AND date = CURDATE() AND status = 'completed' AND actual_service_minutes IS NOT NULL", [$userId]
);

$skippedToday = $db->fetchColumn(
    "SELECT COUNT(*) FROM tokens WHERE served_by = ? AND date = CURDATE() AND status = 'skipped'", [$userId]
);

// Upcoming queue
$upcomingQueue = $deptId ? $db->fetchAll(
    "SELECT t.id, t.display_number, t.type, t.priority_score, t.estimated_wait_minutes, t.created_at,
            s.name as service_name, u.full_name as customer_name
     FROM tokens t
     JOIN services s ON t.service_id = s.id
     LEFT JOIN users u ON t.user_id = u.id
     WHERE t.department_id = ? AND t.date = CURDATE() AND t.status = 'waiting'
     ORDER BY t.priority_score DESC, t.id ASC LIMIT 15",
    [$deptId]
) : [];

$pageScripts = ['staff-counter.js'];
include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header animate-fade-up">
    <div class="page-header-top">
        <div>
            <h1 class="page-header-title"><i class="bi bi-speedometer2" style="color:var(--accent-primary);"></i> Staff Dashboard</h1>
            <p class="page-header-subtitle">
                <?php if ($assignment): ?>
                    <?= e($assignment['branch_name']) ?> — <?= e($assignment['department_name']) ?> — Counter <?= e($assignment['counter_number']) ?>
                <?php else: ?>
                    No counter assigned. Contact your administrator.
                <?php endif; ?>
            </p>
        </div>
        <?php if ($assignment): ?>
        <div class="page-header-actions">
            <div class="flex items-center gap-2">
                <span class="status-dot <?= ($assignment['counter_status'] ?? '') === 'open' ? 'online pulse' : 'offline' ?>"></span>
                <span class="text-sm"><?= ucfirst($assignment['counter_status'] ?? 'closed') ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Row -->
<div class="metrics-row animate-fade-up delay-1">
    <div class="metric-card glass-surface" style="--metric-accent:var(--accent-warning);">
        <div class="metric-card-icon"><i class="bi bi-people"></i></div>
        <div>
            <div class="metric-value" id="queue-count"><?= $queueWaiting ?></div>
            <div class="metric-label">In Queue</div>
        </div>
    </div>
    <div class="metric-card glass-surface" style="--metric-accent:var(--accent-success);">
        <div class="metric-card-icon"><i class="bi bi-check-circle"></i></div>
        <div>
            <div class="metric-value"><?= $completedToday ?></div>
            <div class="metric-label">Served Today</div>
        </div>
    </div>
    <div class="metric-card glass-surface" style="--metric-accent:var(--accent-info);">
        <div class="metric-card-icon"><i class="bi bi-clock"></i></div>
        <div>
            <div class="metric-value"><?= round($avgServiceTime ?? 0) ?> min</div>
            <div class="metric-label">Avg Service</div>
        </div>
    </div>
    <div class="metric-card glass-surface" style="--metric-accent:var(--accent-danger);">
        <div class="metric-card-icon"><i class="bi bi-skip-forward"></i></div>
        <div>
            <div class="metric-value"><?= $skippedToday ?></div>
            <div class="metric-label">Skipped</div>
        </div>
    </div>
</div>

<div class="grid-2 mt-6">
    <!-- Now Serving -->
    <div class="animate-fade-up delay-2">
        <div class="now-serving-card glass-elevated <?= $currentToken ? 'animate-border-glow' : '' ?>">
            <?php if ($currentToken): ?>
            <div class="now-serving-label">NOW SERVING</div>
            <div class="now-serving-token">
                <div class="token-number token-number-lg text-gradient"><?= e($currentToken['display_number']) ?></div>
            </div>
            <div class="now-serving-customer"><?= e($currentToken['customer_name'] ?? 'Walk-in Customer') ?></div>
            <div class="now-serving-service text-sm"><?= e($currentToken['service_name']) ?></div>
            <div class="now-serving-timer" id="service-timer">
                <i class="bi bi-stopwatch"></i>
                <span id="timer-display">00:00</span>
            </div>
            
            <!-- Staff Actions -->
            <div class="staff-actions mt-6">
                <button class="btn-tf btn-success staff-action-primary" id="complete-btn" onclick="completeToken(<?= $currentToken['id'] ?>)">
                    <i class="bi bi-check-lg"></i> COMPLETE
                </button>
                <div class="staff-action-secondary">
                    <button class="btn-tf btn-warning" onclick="skipToken(<?= $currentToken['id'] ?>)">
                        <i class="bi bi-skip-forward"></i> Skip
                    </button>
                    <button class="btn-tf btn-secondary" onclick="recallToken(<?= $currentToken['id'] ?>)">
                        <i class="bi bi-arrow-repeat"></i> Recall
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state" style="padding: var(--space-8);">
                <div class="empty-state-icon"><i class="bi bi-display"></i></div>
                <h3 class="empty-state-title">No Token Being Served</h3>
                <p class="empty-state-text">Call the next token from the queue.</p>
                <?php if ($queueWaiting > 0): ?>
                <button class="btn-tf btn-primary btn-xl mt-4" id="call-next-btn"
                        onclick="callNextToken(<?= $deptId ?>, <?= $counterId ?>)">
                    <i class="bi bi-megaphone"></i> CALL NEXT
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($currentToken): ?>
        <div class="mt-4">
            <button class="btn-tf btn-primary btn-lg w-full" id="call-next-btn"
                    onclick="callNextToken(<?= $deptId ?>, <?= $counterId ?>)"
                    <?= $queueWaiting === 0 ? 'disabled' : '' ?>>
                <i class="bi bi-megaphone"></i> CALL NEXT (<?= $queueWaiting ?> waiting)
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Upcoming Queue -->
    <div class="glass-surface card-tf animate-fade-up delay-3">
        <div class="card-header-tf">
            <div class="card-title-tf"><i class="bi bi-list-ol"></i> Upcoming Queue</div>
            <span class="badge-tf badge-warning"><?= count($upcomingQueue) ?> waiting</span>
        </div>
        <div class="queue-list" id="upcoming-queue" style="max-height: 500px; overflow-y: auto;">
            <?php if (empty($upcomingQueue)): ?>
            <div class="empty-state" style="padding:var(--space-6);">
                <p class="text-secondary text-sm">Queue is empty. Well done!</p>
            </div>
            <?php else: ?>
            <?php foreach ($upcomingQueue as $i => $token): ?>
            <div class="queue-list-item">
                <div class="queue-list-position"><?= $i + 1 ?></div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="token-number" style="font-size:var(--text-sm);"><?= e($token['display_number']) ?></span>
                        <?php if ($token['type'] === 'priority'): ?>
                        <span class="badge-tf badge-warning" style="font-size:9px;">Priority</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs text-secondary"><?= e($token['service_name']) ?></div>
                    <div class="text-xs text-muted"><?= e($token['customer_name'] ?? 'Walk-in') ?> · <?= timeAgo($token['created_at']) ?></div>
                </div>
                <span class="text-xs text-muted">~<?= $token['estimated_wait_minutes'] ?? '-' ?> min</span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
