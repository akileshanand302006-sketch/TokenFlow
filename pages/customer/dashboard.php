<?php
/**
 * TokenFlow Pro — Customer Dashboard
 * Personalized command center with active token, queue intelligence, and activity
 */
$pageTitle = 'Dashboard';
$pageBackground = 'customer-dashboard';
$currentPage = 'dashboard';
$pageRole = 'customer';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();

require_once INCLUDES_PATH . 'TokenEngine.php';
require_once INCLUDES_PATH . 'QueueEngine.php';
require_once INCLUDES_PATH . 'QueueIntelligence.php';
require_once INCLUDES_PATH . 'NotificationEngine.php';

Auth::requireRole('customer');

$user = Auth::getCurrentUser();
$userId = $user['id'];

// Get active token
$activeToken = TokenEngine::getActiveTokenForUser($userId);

// Get recent history
$historyResult = TokenEngine::getUserHistory($userId, 1, 5);
$recentTokens = $historyResult['tokens'];

// Get upcoming appointments
$db = Database::getInstance();
$appointments = $db->fetchAll(
    "SELECT a.*, s.name as service_name, d.name as department_name, b.name as branch_name
     FROM appointments a
     JOIN services s ON a.service_id = s.id
     JOIN departments d ON a.department_id = d.id
     JOIN branches b ON a.branch_id = b.id
     WHERE a.user_id = ? AND a.appointment_date >= CURDATE() AND a.status IN ('scheduled','confirmed')
     ORDER BY a.appointment_date, a.appointment_time LIMIT 3",
    [$userId]
);

// Get notifications
$notifData = NotificationEngine::getForUser($userId, null, 5);

// Stats
$totalTokens = $db->fetchColumn("SELECT COUNT(*) FROM tokens WHERE user_id = ?", [$userId]);
$completedTokens = $db->fetchColumn("SELECT COUNT(*) FROM tokens WHERE user_id = ? AND status = 'completed'", [$userId]);
$avgWait = $db->fetchColumn("SELECT AVG(actual_wait_minutes) FROM tokens WHERE user_id = ? AND status = 'completed' AND actual_wait_minutes IS NOT NULL", [$userId]);

$pageScripts = ['live-queue.js', 'charts.js'];
include COMPONENTS_PATH . 'app-shell.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="dashboard-greeting animate-fade-up">
        <div class="greeting-name"><?= e(getGreeting()) ?>, <?= e(explode(' ', $user['name'])[0]) ?></div>
        <div class="greeting-text">Here's your queue status for today.</div>
    </div>
</div>

<div class="dashboard-grid">
    <?php if ($activeToken): ?>
    <!-- Active Token Hero -->
    <div class="active-token-hero glass-elevated animate-fade-up delay-1" id="active-token-section">
        <div class="text-overline">YOUR ACTIVE TOKEN</div>
        
        <div class="active-token-display">
            <div class="token-number token-number-xl text-gradient animate-token-pulse">
                <?= e($activeToken['display_number']) ?>
            </div>
        </div>
        
        <div class="active-token-status <?= $activeToken['status'] ?>">
            <span class="status-dot <?= $activeToken['status'] === 'serving' ? 'online' : 'busy' ?> pulse"></span>
            <?= $activeToken['status'] === 'serving' ? 'Now Serving' : 'Waiting' ?>
        </div>
        
        <div class="active-token-meta">
            <?php if ($activeToken['status'] === 'waiting'): ?>
            <div class="active-token-meta-item">
                <div class="active-token-meta-value"><?= (int)($activeToken['people_ahead'] ?? 0) ?></div>
                <div class="active-token-meta-label">People Ahead</div>
            </div>
            <div class="active-token-meta-item">
                <div class="active-token-meta-value">~<?= (int)($activeToken['estimated_wait_minutes'] ?? 0) ?> min</div>
                <div class="active-token-meta-label">Est. Wait</div>
            </div>
            <?php else: ?>
            <div class="active-token-meta-item">
                <div class="active-token-meta-value">Counter <?= e($activeToken['counter_number'] ?? '-') ?></div>
                <div class="active-token-meta-label">Proceed To</div>
            </div>
            <?php endif; ?>
            <div class="active-token-meta-item">
                <div class="active-token-meta-value"><?= e($activeToken['service_name']) ?></div>
                <div class="active-token-meta-label">Service</div>
            </div>
            <div class="active-token-meta-item">
                <div class="active-token-meta-value"><?= e($activeToken['department_name']) ?></div>
                <div class="active-token-meta-label">Department</div>
            </div>
        </div>
        
        <?php if ($activeToken['status'] === 'waiting'): ?>
        <div style="max-width: 400px; margin: var(--space-4) auto 0;">
            <div class="progress-tf progress-lg">
                <?php 
                $total = ($activeToken['people_ahead'] ?? 0) + 1;
                $progress = max(5, 100 - (($activeToken['people_ahead'] ?? 0) / max(1, $total)) * 100);
                ?>
                <div class="progress-tf-bar primary" style="width: <?= $progress ?>%"></div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="active-token-actions">
            <a href="<?= PAGES_URL ?>customer/live-queue.php" class="btn-tf btn-primary">
                <i class="bi bi-broadcast"></i> View Live Queue
            </a>
            <button class="btn-tf btn-secondary" onclick="showTokenQR()">
                <i class="bi bi-qr-code"></i> QR Code
            </button>
            <button class="btn-tf btn-ghost btn-danger" onclick="cancelToken(<?= $activeToken['id'] ?>)" 
                    <?= $activeToken['status'] !== 'waiting' ? 'disabled' : '' ?>>
                <i class="bi bi-x-circle"></i> Cancel
            </button>
        </div>
    </div>
    <?php else: ?>
    <!-- No Active Token -->
    <div class="glass-elevated animate-fade-up delay-1" style="padding: var(--space-12); text-align: center;">
        <div class="empty-state-icon" style="margin: 0 auto var(--space-6);">
            <i class="bi bi-ticket-perforated"></i>
        </div>
        <h3 class="text-heading mb-2">No Active Token</h3>
        <p class="text-secondary mb-6">Your queue is currently clear. Generate a token to get started.</p>
        <a href="<?= PAGES_URL ?>customer/generate-token.php" class="btn-tf btn-primary btn-lg">
            <i class="bi bi-plus-circle"></i> Generate Token
        </a>
    </div>
    <?php endif; ?>

    <!-- Stats Row -->
    <div class="metrics-row animate-fade-up delay-2">
        <div class="metric-card glass-surface">
            <div class="metric-card-icon" style="--metric-accent: var(--accent-primary); --metric-accent-rgb: var(--accent-primary-rgb);">
                <i class="bi bi-ticket-perforated"></i>
            </div>
            <div>
                <div class="metric-value" data-count-to="<?= $totalTokens ?>"><?= $totalTokens ?></div>
                <div class="metric-label">Total Tokens</div>
            </div>
        </div>
        <div class="metric-card glass-surface">
            <div class="metric-card-icon" style="--metric-accent: var(--accent-success); --metric-accent-rgb: var(--accent-success-rgb);">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <div class="metric-value" data-count-to="<?= $completedTokens ?>"><?= $completedTokens ?></div>
                <div class="metric-label">Completed</div>
            </div>
        </div>
        <div class="metric-card glass-surface">
            <div class="metric-card-icon" style="--metric-accent: var(--accent-warning); --metric-accent-rgb: var(--accent-warning-rgb);">
                <i class="bi bi-clock"></i>
            </div>
            <div>
                <div class="metric-value" data-count-to="<?= round($avgWait ?? 0) ?>" data-count-suffix=" min"><?= round($avgWait ?? 0) ?> min</div>
                <div class="metric-label">Avg Wait</div>
            </div>
        </div>
        <div class="metric-card glass-surface">
            <div class="metric-card-icon" style="--metric-accent: var(--accent-secondary); --metric-accent-rgb: var(--accent-secondary-rgb);">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div>
                <div class="metric-value" data-count-to="<?= count($appointments) ?>"><?= count($appointments) ?></div>
                <div class="metric-label">Appointments</div>
            </div>
        </div>
    </div>

    <?php if ($activeToken && $activeToken['status'] === 'waiting'): ?>
    <!-- Queue Intelligence -->
    <div class="queue-intelligence glass-surface animate-fade-up delay-3">
        <div class="qi-title">
            <i class="bi bi-lightbulb" style="color: var(--accent-warning);"></i>
            Why am I waiting?
        </div>
        <div class="qi-items">
            <div class="qi-item">
                <div class="qi-item-icon" style="background: rgba(var(--accent-primary-rgb), 0.1); color: var(--accent-primary);">
                    <i class="bi bi-people"></i>
                </div>
                <span class="qi-item-text">Normal tokens ahead</span>
                <span class="qi-item-value"><?= max(0, ($activeToken['people_ahead'] ?? 0)) ?></span>
            </div>
            <?php
            $priorityAhead = $db->fetchColumn(
                "SELECT COUNT(*) FROM tokens WHERE department_id = ? AND date = CURDATE() AND status = 'waiting' AND priority_score > 0 AND id < ?",
                [$activeToken['department_id'], $activeToken['id']]
            );
            ?>
            <div class="qi-item">
                <div class="qi-item-icon" style="background: rgba(var(--accent-danger-rgb), 0.1); color: var(--accent-danger);">
                    <i class="bi bi-star"></i>
                </div>
                <span class="qi-item-text">Priority tokens ahead</span>
                <span class="qi-item-value"><?= $priorityAhead ?></span>
            </div>
            <?php
            $activeCounters = $db->fetchColumn(
                "SELECT COUNT(DISTINCT c.id) FROM counters c JOIN staff_assignments sa ON sa.counter_id = c.id WHERE sa.department_id = ? AND c.status = 'open' AND sa.is_online = 1",
                [$activeToken['department_id']]
            );
            ?>
            <div class="qi-item">
                <div class="qi-item-icon" style="background: rgba(var(--accent-success-rgb), 0.1); color: var(--accent-success);">
                    <i class="bi bi-display"></i>
                </div>
                <span class="qi-item-text">Active counters</span>
                <span class="qi-item-value"><?= $activeCounters ?></span>
            </div>
            <?php
            $avgService = $db->fetchColumn(
                "SELECT AVG(avg_service_time) FROM services WHERE department_id = ?",
                [$activeToken['department_id']]
            );
            ?>
            <div class="qi-item">
                <div class="qi-item-icon" style="background: rgba(var(--accent-warning-rgb), 0.1); color: var(--accent-warning);">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <span class="qi-item-text">Avg service time</span>
                <span class="qi-item-value"><?= round($avgService ?? 10) ?> min</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Two-Column: Appointments + Recent Activity -->
    <div class="grid-2">
        <!-- Upcoming Appointments -->
        <div class="glass-surface card-tf animate-fade-up delay-4">
            <div class="card-header-tf">
                <div class="card-title-tf"><i class="bi bi-calendar-check"></i> Upcoming Appointments</div>
                <a href="<?= PAGES_URL ?>customer/appointments.php" class="btn-tf btn-ghost btn-sm">View All</a>
            </div>
            <?php if (empty($appointments)): ?>
                <div class="empty-state" style="padding: var(--space-6);">
                    <p class="text-secondary text-sm">No upcoming appointments.</p>
                    <a href="<?= PAGES_URL ?>customer/appointments.php" class="btn-tf btn-outline-primary btn-sm mt-4">
                        <i class="bi bi-plus"></i> Book Appointment
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($appointments as $appt): ?>
                <div class="queue-list-item">
                    <div class="qi-item-icon" style="background: rgba(var(--accent-info-rgb), 0.1); color: var(--accent-info);">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-weight-600"><?= e($appt['service_name']) ?></div>
                        <div class="text-xs text-secondary">
                            <?= formatDate($appt['appointment_date'], 'M d') ?> at <?= date('g:i A', strtotime($appt['appointment_time'])) ?>
                        </div>
                    </div>
                    <span class="badge-tf badge-<?= $appt['status'] === 'confirmed' ? 'success' : 'info' ?>">
                        <?= ucfirst($appt['status']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="glass-surface card-tf animate-fade-up delay-5">
            <div class="card-header-tf">
                <div class="card-title-tf"><i class="bi bi-clock-history"></i> Recent Activity</div>
                <a href="<?= PAGES_URL ?>customer/history.php" class="btn-tf btn-ghost btn-sm">View All</a>
            </div>
            <?php if (empty($recentTokens)): ?>
                <div class="empty-state" style="padding: var(--space-6);">
                    <p class="text-secondary text-sm">No token history yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($recentTokens as $token): ?>
                <div class="queue-list-item">
                    <div class="token-badge">
                        <span class="token-number" style="font-size: var(--text-sm);"><?= e($token['display_number']) ?></span>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm"><?= e($token['service_name']) ?></div>
                        <div class="text-xs text-muted"><?= timeAgo($token['created_at']) ?></div>
                    </div>
                    <span class="badge-tf <?= getStatusBadgeClass($token['status']) ?>">
                        <?= getStatusLabel($token['status']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- QR Modal (hidden) -->
<div id="qr-modal" class="modal-backdrop-tf">
    <div class="modal-tf glass-command" style="text-align: center;">
        <div class="modal-icon primary"><i class="bi bi-qr-code"></i></div>
        <div class="modal-title-tf">Your Token QR Code</div>
        <div id="qr-code-container" style="display:flex; justify-content:center; margin: var(--space-6) 0;">
            <div style="background: white; padding: var(--space-4); border-radius: var(--radius-lg); display: inline-block;">
                <canvas id="qr-canvas"></canvas>
            </div>
        </div>
        <div class="modal-body-tf">Show this QR code at the counter for verification.</div>
        <div class="modal-actions-tf" style="justify-content: center;">
            <button class="btn-tf btn-secondary" onclick="closeQRModal()">Close</button>
        </div>
    </div>
</div>

<script>
// Show QR code
function showTokenQR() {
    const modal = document.getElementById('qr-modal');
    modal.classList.add('active');
    modal.querySelector('.modal-tf').classList.add('active');
    
    <?php if ($activeToken): ?>
    renderQR('qr-canvas', <?= json_encode($activeToken['qr_code'] ?? 'TF-TOKEN-'.$activeToken['display_number']) ?>, 200);
    <?php endif; ?>
}

function closeQRModal() {
    const modal = document.getElementById('qr-modal');
    modal.classList.remove('active');
    modal.querySelector('.modal-tf').classList.remove('active');
}

// Cancel token
async function cancelToken(tokenId) {
    Modal.confirm('Cancel Token', 'Are you sure you want to cancel this token?', async () => {
        const result = await API.post('tokens/status.php', { action: 'cancel', token_id: tokenId });
        if (result.success) {
            Toast.success('Token Cancelled', 'Your token has been cancelled.');
            setTimeout(() => location.reload(), 1000);
        } else {
            Toast.error('Error', result.message || 'Failed to cancel token.');
        }
    });
}

// Auto-refresh active token status
<?php if ($activeToken): ?>
setInterval(async () => {
    if (document.hidden) return;
    try {
        const result = await API.get('tokens/status.php?id=<?= $activeToken['id'] ?>');
        if (result.success && result.data) {
            const t = result.data;
            if (t.status === 'serving' && '<?= $activeToken['status'] ?>' === 'waiting') {
                Toast.success('Your Turn!', `Please proceed to Counter ${t.counter_number}`);
                announce(`Token ${t.display_number}, please proceed to Counter ${t.counter_number}`);
                setTimeout(() => location.reload(), 2000);
            }
        }
    } catch(e) {}
}, 5000);
<?php endif; ?>
</script>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
