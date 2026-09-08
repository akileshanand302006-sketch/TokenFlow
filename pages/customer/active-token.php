<?php
/**
 * TokenFlow Pro — Active Token Status
 */
$pageTitle = 'Active Token';
$pageBackground = 'customer-dashboard';
$currentPage = 'active-token';
$pageRole = 'customer';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('customer');

$userId = Auth::getUserId();
$activeToken = TokenEngine::getActiveTokenForUser($userId);

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <h1 class="page-header-title"><i class="bi bi-ticket-perforated" style="color:var(--accent-primary);"></i> Active Token Status</h1>
        <a href="<?= PAGES_URL ?>customer/generate-token.php" class="btn-tf btn-primary"><i class="bi bi-plus-circle"></i> Generate New Token</a>
    </div>
</div>

<?php if ($activeToken): ?>
<div class="active-token-hero glass-elevated animate-fade-up">
    <div class="text-overline">CURRENT ACTIVE TOKEN</div>
    
    <div class="active-token-display">
        <div class="token-number token-number-xl text-gradient animate-token-pulse">
            <?= e($activeToken['display_number']) ?>
        </div>
    </div>
    
    <div class="active-token-status <?= $activeToken['status'] ?>">
        <span class="status-dot <?= $activeToken['status'] === 'serving' ? 'online' : 'busy' ?> pulse"></span>
        <?= $activeToken['status'] === 'serving' ? 'Now Serving at Counter ' . e($activeToken['counter_number'] ?? '') : 'Waiting in Queue' ?>
    </div>
    
    <div class="active-token-meta mt-6">
        <?php if ($activeToken['status'] === 'waiting'): ?>
        <div class="active-token-meta-item">
            <div class="active-token-meta-value"><?= (int)($activeToken['people_ahead'] ?? 0) ?></div>
            <div class="active-token-meta-label">People Ahead</div>
        </div>
        <div class="active-token-meta-item">
            <div class="active-token-meta-value">~<?= (int)($activeToken['estimated_wait_minutes'] ?? 0) ?> min</div>
            <div class="active-token-meta-label">Est. Wait Time</div>
        </div>
        <?php else: ?>
        <div class="active-token-meta-item">
            <div class="active-token-meta-value">Counter <?= e($activeToken['counter_number'] ?? '-') ?></div>
            <div class="active-token-meta-label">Counter</div>
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
    
    <div class="flex justify-center gap-4 mt-8 flex-wrap">
        <a href="<?= PAGES_URL ?>customer/live-queue.php" class="btn-tf btn-primary"><i class="bi bi-broadcast"></i> Track Live Queue</a>
        <button class="btn-tf btn-secondary" onclick="document.getElementById('qr-modal').classList.add('active');"><i class="bi bi-qr-code"></i> Show QR Code</button>
        <button class="btn-tf btn-ghost btn-danger" onclick="cancelToken(<?= $activeToken['id'] ?>)" <?= $activeToken['status'] !== 'waiting' ? 'disabled' : '' ?>><i class="bi bi-x-circle"></i> Cancel Token</button>
    </div>
</div>
<?php else: ?>
<div class="glass-elevated card-tf text-center p-12 animate-fade-up">
    <div class="empty-state-icon" style="margin:0 auto var(--space-4);"><i class="bi bi-ticket-perforated"></i></div>
    <h2 class="text-heading mb-2">No Active Token</h2>
    <p class="text-secondary mb-6">You currently don't have any active tokens in the queue.</p>
    <a href="<?= PAGES_URL ?>customer/generate-token.php" class="btn-tf btn-primary btn-lg"><i class="bi bi-plus-circle"></i> Generate Token</a>
</div>
<?php endif; ?>

<!-- QR Modal -->
<div class="modal-backdrop-tf" id="qr-modal" onclick="this.classList.remove('active');">
    <div class="modal-tf glass-command" onclick="event.stopPropagation();" style="text-align:center;">
        <div class="modal-title-tf">Token QR Code</div>
        <div style="background:white;padding:var(--space-4);border-radius:var(--radius-lg);display:inline-block;margin:var(--space-4) 0;">
            <canvas id="qr-canvas"></canvas>
        </div>
        <p class="text-sm text-secondary mb-4">Show this QR code at the service counter.</p>
        <button class="btn-tf btn-secondary" onclick="document.getElementById('qr-modal').classList.remove('active');">Close</button>
    </div>
</div>

<script>
<?php if ($activeToken): ?>
document.addEventListener('DOMContentLoaded', () => {
    renderQR('qr-canvas', <?= json_encode($activeToken['qr_code'] ?? 'TF-TOKEN-'.$activeToken['display_number']) ?>, 200);
});
async function cancelToken(tokenId) {
    if (!confirm('Are you sure you want to cancel this token?')) return;
    const result = await API.post('tokens/status.php', { action: 'cancel', token_id: tokenId });
    if (result.success) {
        Toast.success('Cancelled', 'Token cancelled.');
        setTimeout(() => location.reload(), 1000);
    } else {
        Toast.error('Error', result.message || 'Failed to cancel.');
    }
}
<?php endif; ?>
</script>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
