<?php
/**
 * TokenFlow Pro — Token Generation Wizard
 * Multi-step visual wizard for generating tokens
 */
$pageTitle = 'Generate Token';
$pageBackground = 'token-generation';
$currentPage = 'generate-token';
$pageRole = 'customer';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('customer');

$db = Database::getInstance();

// Get branches
$branches = $db->fetchAll("SELECT * FROM branches WHERE org_id = ? AND is_active = 1 ORDER BY name", [$_SESSION['org_id'] ?? 1]);

$pageScripts = ['token-wizard.js'];
include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="wizard-container">
    <!-- Wizard Steps Indicator -->
    <div class="wizard-steps glass-surface animate-fade-up">
        <div class="wizard-step-indicator active" data-step="1">
            <div class="wizard-step-number">1</div>
            <span class="wizard-step-label">Branch</span>
        </div>
        <div class="wizard-connector"></div>
        <div class="wizard-step-indicator" data-step="2">
            <div class="wizard-step-number">2</div>
            <span class="wizard-step-label">Department</span>
        </div>
        <div class="wizard-connector"></div>
        <div class="wizard-step-indicator" data-step="3">
            <div class="wizard-step-number">3</div>
            <span class="wizard-step-label">Service</span>
        </div>
        <div class="wizard-connector"></div>
        <div class="wizard-step-indicator" data-step="4">
            <div class="wizard-step-number">4</div>
            <span class="wizard-step-label">Type</span>
        </div>
        <div class="wizard-connector"></div>
        <div class="wizard-step-indicator" data-step="5">
            <div class="wizard-step-number">5</div>
            <span class="wizard-step-label">Confirm</span>
        </div>
    </div>

    <!-- Step 1: Branch -->
    <div class="wizard-step wizard-step-active" id="step-1">
        <div class="page-header" style="text-align:center;">
            <h2 class="text-title mb-2">Select Branch</h2>
            <p class="text-secondary">Choose the branch you want to visit.</p>
        </div>
        <div class="service-cards">
            <?php foreach ($branches as $branch): ?>
            <div class="service-card glass-elevated hover-lift" data-branch-id="<?= $branch['id'] ?>" data-branch-name="<?= e($branch['name']) ?>">
                <div class="service-card-icon" style="background: rgba(var(--accent-primary-rgb), 0.1); color: var(--accent-primary);">
                    <i class="bi bi-building"></i>
                </div>
                <div class="service-card-name"><?= e($branch['name']) ?></div>
                <div class="service-card-desc"><?= e($branch['address'] ?? $branch['city'] ?? '') ?></div>
                <div class="service-card-meta">
                    <span><i class="bi bi-clock"></i> <?= date('g:i A', strtotime($branch['opening_time'])) ?> - <?= date('g:i A', strtotime($branch['closing_time'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Step 2: Department -->
    <div class="wizard-step" id="step-2" style="display:none;">
        <div class="page-header" style="text-align:center;">
            <h2 class="text-title mb-2">Select Department</h2>
            <p class="text-secondary">Choose the department for your visit.</p>
        </div>
        <div class="service-cards" id="department-cards">
            <!-- Loaded via AJAX -->
        </div>
    </div>

    <!-- Step 3: Service -->
    <div class="wizard-step" id="step-3" style="display:none;">
        <div class="page-header" style="text-align:center;">
            <h2 class="text-title mb-2">Select Service</h2>
            <p class="text-secondary">Choose the service you need.</p>
        </div>
        <div class="service-cards" id="service-cards">
            <!-- Loaded via AJAX -->
        </div>
    </div>

    <!-- Step 4: Token Type -->
    <div class="wizard-step" id="step-4" style="display:none;">
        <div class="page-header" style="text-align:center;">
            <h2 class="text-title mb-2">Token Type</h2>
            <p class="text-secondary">Select your queue priority.</p>
        </div>
        <div class="service-cards" style="max-width: 600px; margin: 0 auto;">
            <div class="service-card glass-elevated hover-lift selected" data-type="normal">
                <div class="service-card-icon" style="background: rgba(var(--accent-primary-rgb), 0.1); color: var(--accent-primary);">
                    <i class="bi bi-person"></i>
                </div>
                <div class="service-card-name">Normal</div>
                <div class="service-card-desc">Standard queue priority</div>
            </div>
            <div class="service-card glass-elevated hover-lift" data-type="priority">
                <div class="service-card-icon" style="background: rgba(var(--accent-warning-rgb), 0.1); color: var(--accent-warning);">
                    <i class="bi bi-star"></i>
                </div>
                <div class="service-card-name">Priority</div>
                <div class="service-card-desc">Elderly, disabled, or pregnant</div>
            </div>
        </div>
    </div>

    <!-- Step 5: Confirmation -->
    <div class="wizard-step" id="step-5" style="display:none;">
        <div class="page-header" style="text-align:center;">
            <h2 class="text-title mb-2">Confirm & Generate</h2>
            <p class="text-secondary">Review your selection before generating the token.</p>
        </div>
        <div class="glass-elevated" style="max-width: 500px; margin: 0 auto; padding: var(--space-8);">
            <div class="qi-items" id="confirmation-details">
                <!-- Populated by JS -->
            </div>
            <div style="text-align:center; margin-top: var(--space-8);">
                <button class="btn-tf btn-primary btn-xl" id="generate-btn">
                    <i class="bi bi-ticket-perforated"></i> Generate Token
                </button>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="flex justify-between mt-8" id="wizard-nav">
        <button class="btn-tf btn-secondary" id="prev-btn" style="display:none;">
            <i class="bi bi-arrow-left"></i> Back
        </button>
        <div></div>
    </div>
</div>

<!-- Token Success Screen -->
<div id="token-success-screen" style="display:none;">
    <div class="token-success">
        <div class="token-success-content animate-scale-bounce">
            <div class="token-success-check animate-success-ring">
                <i class="bi bi-check-lg"></i>
            </div>
            <div class="text-overline mb-2">TOKEN GENERATED</div>
            <div class="token-success-number">
                <div class="token-number token-number-xl text-gradient" id="success-token-number"></div>
            </div>
            <p class="text-secondary mb-2">You're in the queue.</p>
            <div class="flex justify-center gap-8 mb-6">
                <div class="text-center">
                    <div class="metric-value" id="success-people-ahead">0</div>
                    <div class="metric-label">People Ahead</div>
                </div>
                <div class="text-center">
                    <div class="metric-value" id="success-wait-time">0 min</div>
                    <div class="metric-label">Est. Wait</div>
                </div>
            </div>
            <div id="success-qr" class="token-success-qr animate-qr">
                <canvas id="success-qr-canvas"></canvas>
            </div>
            <div class="flex justify-center gap-3 mt-6 flex-wrap">
                <a href="<?= PAGES_URL ?>customer/live-queue.php" class="btn-tf btn-primary">
                    <i class="bi bi-broadcast"></i> Track Queue
                </a>
                <a href="<?= PAGES_URL ?>customer/dashboard.php" class="btn-tf btn-secondary">
                    <i class="bi bi-grid-1x2"></i> Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
