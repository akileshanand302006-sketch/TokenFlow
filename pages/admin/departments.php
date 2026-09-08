<?php
/**
 * TokenFlow Pro — Admin: Manage Departments
 */
$pageTitle = 'Manage Departments';
$pageBackground = 'admin-dashboard';
$currentPage = 'departments';
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
            "INSERT INTO departments (branch_id, name, code, description, icon, color, token_prefix, sort_order) VALUES (?,?,?,?,?,?,?,?)",
            [(int)$_POST['branch_id'], trim($_POST['name']), strtoupper(trim($_POST['code'])),
             trim($_POST['description'] ?? ''), $_POST['icon'] ?? 'bi-folder', $_POST['color'] ?? '#38bdf8',
             strtoupper(trim($_POST['token_prefix'] ?? 'A')), (int)($_POST['sort_order'] ?? 1)]
        );
        Audit::log('department_created', 'departments', $db->lastInsertId());
        $message = "Department created.";
    }
    if ($action === 'toggle') {
        $db->query("UPDATE departments SET is_active = NOT is_active WHERE id = ?", [(int)$_POST['id']]);
        $message = "Status updated.";
    }
    if ($action === 'delete') {
        $db->query("DELETE FROM departments WHERE id = ?", [(int)$_POST['id']]);
        $message = "Department deleted.";
    }
}

$departments = $db->fetchAll(
    "SELECT d.*, b.name as branch_name,
            (SELECT COUNT(*) FROM services s WHERE s.department_id = d.id) as service_count,
            (SELECT COUNT(*) FROM tokens t WHERE t.department_id = d.id AND t.date = CURDATE() AND t.status = 'waiting') as queue_count
     FROM departments d JOIN branches b ON d.branch_id = b.id WHERE b.org_id = ? ORDER BY b.name, d.sort_order", [$orgId]
);

$branches = $db->fetchAll("SELECT id, name FROM branches WHERE org_id = ? AND is_active = 1 ORDER BY name", [$orgId]);

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <h1 class="page-header-title">Manage Departments</h1>
        <button class="btn-tf btn-primary" onclick="document.getElementById('create-modal').classList.add('active');document.getElementById('create-backdrop').classList.add('active');">
            <i class="bi bi-plus-circle"></i> Add Department
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
            <thead><tr><th>Department</th><th>Branch</th><th>Code</th><th>Prefix</th><th>Services</th><th>Queue</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($departments as $dept): ?>
                <tr>
                    <td class="flex items-center gap-2"><i class="bi <?= e($dept['icon']) ?>" style="color:<?= e($dept['color']) ?>;"></i> <strong><?= e($dept['name']) ?></strong></td>
                    <td><?= e($dept['branch_name']) ?></td>
                    <td><span class="badge-tf badge-secondary"><?= e($dept['code']) ?></span></td>
                    <td class="text-mono"><?= e($dept['token_prefix']) ?></td>
                    <td><?= $dept['service_count'] ?></td>
                    <td><span class="badge-tf <?= $dept['queue_count'] > 5 ? 'badge-warning' : 'badge-success' ?>"><?= $dept['queue_count'] ?></span></td>
                    <td><span class="badge-tf <?= $dept['is_active'] ? 'badge-success' : 'badge-secondary' ?>"><?= $dept['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                    <td>
                        <div class="table-actions">
                            <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $dept['id'] ?>">
                                <button class="btn-tf btn-ghost btn-icon btn-sm" data-tooltip="Toggle"><i class="bi bi-toggle-on"></i></button></form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?');"><input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $dept['id'] ?>">
                                <button class="btn-tf btn-ghost btn-icon btn-sm" style="color:var(--accent-danger);"><i class="bi bi-trash"></i></button></form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($departments)): ?><tr><td colspan="8" class="text-center text-secondary p-6">No departments.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop-tf" id="create-backdrop" onclick="this.classList.remove('active');document.getElementById('create-modal').classList.remove('active');"></div>
<div class="modal-tf glass-command" id="create-modal" style="width:min(90vw,520px);">
    <div class="modal-title-tf">Add Department</div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
        <input type="hidden" name="action" value="create">
        <div class="form-group"><label class="form-label">Branch *</label><select class="form-select" name="branch_id" required>
            <?php foreach ($branches as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?></select></div>
        <div class="grid-2">
            <div class="form-group"><label class="form-label">Name *</label><input type="text" class="form-input" name="name" required></div>
            <div class="form-group"><label class="form-label">Code *</label><input type="text" class="form-input" name="code" required maxlength="10"></div>
        </div>
        <div class="form-group"><label class="form-label">Description</label><textarea class="form-input" name="description" rows="2"></textarea></div>
        <div class="grid-2">
            <div class="form-group"><label class="form-label">Token Prefix</label><input type="text" class="form-input" name="token_prefix" maxlength="5" value="A"></div>
            <div class="form-group"><label class="form-label">Color</label><input type="color" class="form-input" name="color" value="#38bdf8" style="height:42px;"></div>
        </div>
        <div class="form-group"><label class="form-label">Icon (Bootstrap Icons)</label><input type="text" class="form-input" name="icon" value="bi-folder" placeholder="bi-icon-name"></div>
        <div class="modal-actions-tf">
            <button type="button" class="btn-tf btn-secondary" onclick="document.getElementById('create-modal').classList.remove('active');document.getElementById('create-backdrop').classList.remove('active');">Cancel</button>
            <button type="submit" class="btn-tf btn-primary"><i class="bi bi-check"></i> Create</button>
        </div>
    </form>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
