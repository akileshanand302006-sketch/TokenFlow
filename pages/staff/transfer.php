<?php
/**
 * TokenFlow Pro — Staff Token Transfer
 */
$pageTitle = 'Transfer Token';
$pageBackground = 'staff-dashboard';
$currentPage = 'transfer';
$pageRole = 'staff';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('staff');

$db = Database::getInstance();
$departments = $db->fetchAll("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name");

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::requireValid();
    $tokenNum = trim($_POST['token_number'] ?? '');
    $targetDept = (int)($_POST['department_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    if ($tokenNum && $targetDept) {
        $token = $db->fetch("SELECT id FROM tokens WHERE display_number = ? AND date = CURDATE()", [$tokenNum]);
        if ($token) {
            $db->query("UPDATE tokens SET department_id = ?, priority_score = priority_score + 10 WHERE id = ?", [$targetDept, $token['id']]);
            Audit::log('token_transferred', 'tokens', $token['id'], "Transferred token $tokenNum to dept $targetDept");
            $message = "Token $tokenNum transferred successfully.";
        } else {
            $message = "error:Token number not found today.";
        }
    }
}

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <h1 class="page-header-title"><i class="bi bi-arrow-left-right" style="color:var(--accent-info);"></i> Transfer Token</h1>
</div>

<div class="glass-elevated card-tf max-w-lg mx-auto animate-fade-up">
    <div class="card-header-tf">
        <div class="card-title-tf"><i class="bi bi-arrow-right-circle"></i> Inter-Department Transfer</div>
    </div>
    
    <?php if ($message): ?>
    <div class="animate-fade-up mb-4" style="padding:var(--space-3) var(--space-4);border-radius:var(--radius-md);
        <?= str_starts_with($message, 'error:') ? 'background:rgba(248,113,113,0.1);border:1px solid var(--accent-danger);color:var(--accent-danger);' : 'background:rgba(52,211,153,0.1);border:1px solid var(--accent-success);color:var(--accent-success);' ?>">
        <i class="bi <?= str_starts_with($message, 'error:') ? 'bi-exclamation-circle' : 'bi-check-circle' ?>"></i> <?= e(str_starts_with($message, 'error:') ? substr($message, 6) : $message) ?>
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
        <div class="form-group">
            <label class="form-label">Token Number *</label>
            <input type="text" class="form-input" name="token_number" placeholder="e.g. A-004" required autofocus style="text-transform:uppercase;">
        </div>
        <div class="form-group">
            <label class="form-label">Target Department *</label>
            <select class="form-select" name="department_id" required>
                <option value="">— Select Target Department —</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Transfer Reason</label>
            <textarea class="form-input" name="reason" rows="2" placeholder="Reason for transfer..."></textarea>
        </div>
        <button type="submit" class="btn-tf btn-primary w-full"><i class="bi bi-send"></i> Transfer Token</button>
    </form>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
