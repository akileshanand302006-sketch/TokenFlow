<?php
/**
 * TokenFlow Pro — Admin: Manage Services
 */
$pageTitle = 'Manage Services';
$pageBackground = 'admin-dashboard';
$currentPage = 'services';
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
            "INSERT INTO services (department_id, name, description, icon, avg_service_time, max_service_time, sort_order) VALUES (?,?,?,?,?,?,?)",
            [(int)$_POST['department_id'], trim($_POST['name']), trim($_POST['description'] ?? ''),
             $_POST['icon'] ?? 'bi-clipboard', (int)$_POST['avg_service_time'], (int)$_POST['max_service_time'], (int)($_POST['sort_order'] ?? 1)]
        );
        Audit::log('service_created', 'services', $db->lastInsertId());
        $message = "Service created.";
    }
    if ($action === 'toggle') {
        $db->query("UPDATE services SET is_active = NOT is_active WHERE id = ?", [(int)$_POST['id']]);
        $message = "Status updated.";
    }
    if ($action === 'delete') {
        $db->query("DELETE FROM services WHERE id = ?", [(int)$_POST['id']]);
        $message = "Service deleted.";
    }
}

$services = $db->fetchAll(
    "SELECT s.*, d.name as dept_name, d.color as dept_color, b.name as branch_name
     FROM services s JOIN departments d ON s.department_id = d.id JOIN branches b ON d.branch_id = b.id
     WHERE b.org_id = ? ORDER BY b.name, d.name, s.sort_order", [$orgId]
);

$departments = $db->fetchAll(
    "SELECT d.id, d.name, b.name as branch_name FROM departments d JOIN branches b ON d.branch_id = b.id
     WHERE b.org_id = ? AND d.is_active = 1 ORDER BY b.name, d.name", [$orgId]
);

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <h1 class="page-header-title">Manage Services</h1>
        <button class="btn-tf btn-primary" onclick="document.getElementById('create-modal').classList.add('active');document.getElementById('create-backdrop').classList.add('active');">
            <i class="bi bi-plus-circle"></i> Add Service
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
            <thead><tr><th>Service</th><th>Department</th><th>Branch</th><th>Avg Time</th><th>Max Time</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($services as $svc): ?>
                <tr>
                    <td class="flex items-center gap-2"><i class="bi <?= e($svc['icon'] ?? 'bi-clipboard') ?>" style="color:<?= e($svc['dept_color'] ?? 'var(--accent-primary)') ?>;"></i> <strong><?= e($svc['name']) ?></strong></td>
                    <td><?= e($svc['dept_name']) ?></td>
                    <td class="text-muted"><?= e($svc['branch_name']) ?></td>
                    <td><?= $svc['avg_service_time'] ?> min</td>
                    <td><?= $svc['max_service_time'] ?> min</td>
                    <td><span class="badge-tf <?= $svc['is_active'] ? 'badge-success' : 'badge-secondary' ?>"><?= $svc['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                    <td>
                        <div class="table-actions">
                            <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $svc['id'] ?>">
                                <button class="btn-tf btn-ghost btn-icon btn-sm"><i class="bi bi-toggle-on"></i></button></form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?');"><input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $svc['id'] ?>">
                                <button class="btn-tf btn-ghost btn-icon btn-sm" style="color:var(--accent-danger);"><i class="bi bi-trash"></i></button></form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop-tf" id="create-backdrop" onclick="this.classList.remove('active');document.getElementById('create-modal').classList.remove('active');"></div>
<div class="modal-tf glass-command" id="create-modal" style="width:min(90vw,520px);">
    <div class="modal-title-tf">Add Service</div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>"><input type="hidden" name="action" value="create">
        <div class="form-group"><label class="form-label">Department *</label><select class="form-select" name="department_id" required>
            <?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['branch_name']) ?> — <?= e($d['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Service Name *</label><input type="text" class="form-input" name="name" required></div>
        <div class="form-group"><label class="form-label">Description</label><textarea class="form-input" name="description" rows="2"></textarea></div>
        <div class="grid-2">
            <div class="form-group"><label class="form-label">Avg Service Time (min)</label><input type="number" class="form-input" name="avg_service_time" value="10" min="1"></div>
            <div class="form-group"><label class="form-label">Max Service Time (min)</label><input type="number" class="form-input" name="max_service_time" value="30" min="1"></div>
        </div>
        <div class="form-group"><label class="form-label">Icon</label><input type="text" class="form-input" name="icon" value="bi-clipboard" placeholder="bi-icon-name"></div>
        <div class="modal-actions-tf">
            <button type="button" class="btn-tf btn-secondary" onclick="document.getElementById('create-modal').classList.remove('active');document.getElementById('create-backdrop').classList.remove('active');">Cancel</button>
            <button type="submit" class="btn-tf btn-primary"><i class="bi bi-check"></i> Create</button>
        </div>
    </form>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
