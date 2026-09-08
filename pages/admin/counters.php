<?php
/**
 * TokenFlow Pro — Admin: Manage Counters
 */
$pageTitle = 'Manage Counters';
$pageBackground = 'admin-dashboard';
$currentPage = 'counters';
$pageRole = 'admin';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('admin');

$db = Database::getInstance();
$orgId = $_SESSION['org_id'] ?? 1;

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::requireValid();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $db->query(
            "INSERT INTO counters (branch_id, name, number, status) VALUES (?,?,?,?)",
            [(int)$_POST['branch_id'], trim($_POST['name']), (int)$_POST['number'], $_POST['status'] ?? 'closed']
        );
        $message = "Counter created.";
    }
    if ($action === 'status') {
        $db->query("UPDATE counters SET status = ? WHERE id = ?", [$_POST['status'], (int)$_POST['id']]);
        $message = "Counter status updated.";
    }
    if ($action === 'delete') {
        $db->query("DELETE FROM counters WHERE id = ?", [(int)$_POST['id']]);
        $message = "Counter deleted.";
    }
}

$counters = $db->fetchAll(
    "SELECT c.*, b.name as branch_name,
            sa.user_id as staff_id, u.full_name as staff_name,
            t.display_number as current_token
     FROM counters c
     JOIN branches b ON c.branch_id = b.id
     LEFT JOIN staff_assignments sa ON sa.counter_id = c.id
     LEFT JOIN users u ON sa.user_id = u.id
     LEFT JOIN tokens t ON c.current_token_id = t.id
     WHERE b.org_id = ? ORDER BY b.name, c.number", [$orgId]
);

$branches = $db->fetchAll("SELECT id, name FROM branches WHERE org_id = ? AND is_active = 1 ORDER BY name", [$orgId]);

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <h1 class="page-header-title">Manage Counters</h1>
        <button class="btn-tf btn-primary" onclick="document.getElementById('create-modal').classList.add('active');document.getElementById('create-backdrop').classList.add('active');">
            <i class="bi bi-plus-circle"></i> Add Counter
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="animate-fade-up mb-4" style="padding:var(--space-3) var(--space-4);border-radius:var(--radius-md);background:rgba(52,211,153,0.1);border:1px solid var(--accent-success);color:var(--accent-success);">
    <i class="bi bi-check-circle"></i> <?= e($message) ?>
</div>
<?php endif; ?>

<div class="glass-surface card-tf animate-fade-up">
    <div class="table-container">
        <table class="table-tf">
            <thead><tr><th>#</th><th>Name</th><th>Branch</th><th>Assigned Staff</th><th>Current Token</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($counters as $c): ?>
                <tr>
                    <td><strong><?= $c['number'] ?></strong></td>
                    <td><?= e($c['name']) ?></td>
                    <td><?= e($c['branch_name']) ?></td>
                    <td><?= $c['staff_name'] ? e($c['staff_name']) : '<span class="text-muted">Unassigned</span>' ?></td>
                    <td><?= $c['current_token'] ? '<span class="token-number" style="font-size:var(--text-sm);">' . e($c['current_token']) . '</span>' : '-' ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
                            <input type="hidden" name="action" value="status">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <select class="form-select" name="status" onchange="this.form.submit()" style="width:120px;padding:var(--space-1) var(--space-2);font-size:var(--text-xs);">
                                <option value="open" <?= $c['status'] === 'open' ? 'selected' : '' ?>>🟢 Open</option>
                                <option value="closed" <?= $c['status'] === 'closed' ? 'selected' : '' ?>>⚫ Closed</option>
                                <option value="break" <?= $c['status'] === 'break' ? 'selected' : '' ?>>🟡 Break</option>
                                <option value="maintenance" <?= $c['status'] === 'maintenance' ? 'selected' : '' ?>>🔴 Maint.</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                            <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button class="btn-tf btn-ghost btn-icon btn-sm" style="color:var(--accent-danger);"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop-tf" id="create-backdrop" onclick="this.classList.remove('active');document.getElementById('create-modal').classList.remove('active');"></div>
<div class="modal-tf glass-command" id="create-modal">
    <div class="modal-title-tf">Add Counter</div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>"><input type="hidden" name="action" value="create">
        <div class="form-group"><label class="form-label">Branch *</label><select class="form-select" name="branch_id" required>
            <?php foreach ($branches as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?></select></div>
        <div class="grid-2">
            <div class="form-group"><label class="form-label">Counter Name *</label><input type="text" class="form-input" name="name" required placeholder="Counter 1"></div>
            <div class="form-group"><label class="form-label">Number *</label><input type="number" class="form-input" name="number" required min="1"></div>
        </div>
        <div class="form-group"><label class="form-label">Initial Status</label><select class="form-select" name="status">
            <option value="closed">Closed</option><option value="open">Open</option></select></div>
        <div class="modal-actions-tf">
            <button type="button" class="btn-tf btn-secondary" onclick="document.getElementById('create-modal').classList.remove('active');document.getElementById('create-backdrop').classList.remove('active');">Cancel</button>
            <button type="submit" class="btn-tf btn-primary"><i class="bi bi-check"></i> Create</button>
        </div>
    </form>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
