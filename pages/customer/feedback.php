<?php
/**
 * TokenFlow Pro — Customer Feedback
 */
$pageTitle = 'Feedback';
$pageBackground = 'customer-dashboard';
$currentPage = 'feedback';
$pageRole = 'customer';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('customer');

$userId = Auth::getUserId();
$db = Database::getInstance();

// Handle form submission
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::requireValid();
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $category = $_POST['category'] ?? 'overall';
    $branchId = (int)($_POST['branch_id'] ?? 1);
    $deptId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    
    if ($rating >= 1 && $rating <= 5) {
        $db->query(
            "INSERT INTO feedback (user_id, branch_id, department_id, rating, comment, category) VALUES (?, ?, ?, ?, ?, ?)",
            [$userId, $branchId, $deptId, $rating, $comment, $category]
        );
        $success = true;
    }
}

$myFeedback = $db->fetchAll(
    "SELECT f.*, b.name as branch_name, d.name as dept_name FROM feedback f
     JOIN branches b ON f.branch_id = b.id LEFT JOIN departments d ON f.department_id = d.id
     WHERE f.user_id = ? ORDER BY f.created_at DESC LIMIT 10",
    [$userId]
);

$branches = $db->fetchAll("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name");
$departments = $db->fetchAll("SELECT id, name, branch_id FROM departments WHERE is_active = 1 ORDER BY name");

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <h1 class="page-header-title">Feedback</h1>
</div>

<div class="grid-2">
    <!-- Submit Feedback -->
    <div class="glass-elevated card-tf animate-fade-up">
        <div class="card-header-tf">
            <div class="card-title-tf"><i class="bi bi-star"></i> Rate Your Experience</div>
        </div>
        
        <?php if ($success): ?>
        <div style="padding:var(--space-4);background:rgba(52,211,153,0.1);border:1px solid var(--accent-success);border-radius:var(--radius-md);color:var(--accent-success);margin-bottom:var(--space-4);">
            <i class="bi bi-check-circle"></i> Thank you for your feedback!
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
            
            <!-- Star Rating -->
            <div class="form-group" style="text-align:center;">
                <div class="form-label">How was your experience?</div>
                <div class="flex justify-center gap-2 mt-2" id="star-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" class="btn-tf btn-ghost" style="font-size:2rem;padding:var(--space-1);" data-star="<?= $i ?>" onclick="setRating(<?= $i ?>)">
                        <i class="bi bi-star"></i>
                    </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="rating-input" value="0" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Category</label>
                <select class="form-select" name="category">
                    <option value="overall">Overall Experience</option>
                    <option value="service">Service Quality</option>
                    <option value="wait_time">Wait Time</option>
                    <option value="staff">Staff Behavior</option>
                    <option value="facility">Facility</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Branch</label>
                <select class="form-select" name="branch_id">
                    <?php foreach ($branches as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Comments (optional)</label>
                <textarea class="form-input" name="comment" rows="4" placeholder="Tell us about your experience..."></textarea>
            </div>
            
            <button type="submit" class="btn-tf btn-primary w-full"><i class="bi bi-send"></i> Submit Feedback</button>
        </form>
    </div>

    <!-- Past Feedback -->
    <div class="glass-surface card-tf animate-fade-up delay-2">
        <div class="card-header-tf">
            <div class="card-title-tf"><i class="bi bi-clock-history"></i> Your Feedback</div>
        </div>
        <?php if (empty($myFeedback)): ?>
            <div class="empty-state" style="padding:var(--space-6);">
                <p class="text-secondary text-sm">No feedback submitted yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($myFeedback as $fb): ?>
            <div class="queue-list-item">
                <div>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi <?= $i <= $fb['rating'] ? 'bi-star-fill' : 'bi-star' ?>" style="color:var(--accent-warning);font-size:12px;"></i>
                    <?php endfor; ?>
                </div>
                <div class="flex-1">
                    <div class="text-sm"><?= e($fb['comment'] ?: ucfirst($fb['category'])) ?></div>
                    <div class="text-xs text-muted"><?= date('M d, Y', strtotime($fb['created_at'])) ?> — <?= e($fb['branch_name']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function setRating(n) {
    document.getElementById('rating-input').value = n;
    document.querySelectorAll('#star-rating button').forEach((btn, i) => {
        btn.querySelector('i').className = i < n ? 'bi bi-star-fill' : 'bi bi-star';
        btn.querySelector('i').style.color = i < n ? 'var(--accent-warning)' : '';
    });
}
</script>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
