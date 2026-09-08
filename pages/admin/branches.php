<?php
/**
 * TokenFlow Pro — Admin: Manage Branches
 */
$pageTitle = 'Manage Branches';
$pageBackground = 'admin-dashboard';
$currentPage = 'branches';
$pageRole = 'admin';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('admin');

$db = Database::getInstance();
$orgId = $_SESSION['org_id'] ?? 1;

// Handle actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::requireValid();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $open = $_POST['opening_time'] ?? '08:00:00';
        $close = $_POST['closing_time'] ?? '18:00:00';
        
        if ($name && $code) {
            $db->query(
                "INSERT INTO branches (org_id, name, code, address, city, state, phone, opening_time, closing_time) VALUES (?,?,?,?,?,?,?,?,?)",
                [$orgId, $name, $code, $address, $city, $state, $phone, $open, $close]
            );
            Audit::log('branch_created', 'branches', $db->lastInsertId(), "Created branch: $name");
            $message = "Branch '$name' created successfully.";
        }
    }
    
    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $db->query("UPDATE branches SET is_active = NOT is_active WHERE id = ? AND org_id = ?", [$id, $orgId]);
        $message = "Branch status updated.";
    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM branches WHERE id = ? AND org_id = ?", [$id, $orgId]);
        Audit::log('branch_deleted', 'branches', $id, "Deleted branch");
        $message = "Branch deleted.";
    }
}

$branches = $db->fetchAll(
    "SELECT b.*, 
            (SELECT COUNT(*) FROM departments d WHERE d.branch_id = b.id) as dept_count,
            (SELECT COUNT(*) FROM counters c WHERE c.branch_id = b.id) as counter_count
     FROM branches b WHERE b.org_id = ? ORDER BY b.name", [$orgId]
);

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <h1 class="page-header-title">Manage Branches</h1>
        <button class="btn-tf btn-primary" onclick="document.getElementById('create-modal').classList.add('active');document.getElementById('create-backdrop').classList.add('active');">
            <i class="bi bi-plus-circle"></i> Add Branch
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
            <thead>
                <tr>
                    <th>Branch</th>
                    <th>Code</th>
                    <th>City</th>
                    <th>Phone</th>
                    <th>Hours</th>
                    <th>Departments</th>
                    <th>Counters</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($branches as $branch): ?>
                <tr>
                    <td class="font-weight-600"><?= e($branch['name']) ?></td>
                    <td><span class="badge-tf badge-secondary"><?= e($branch['code']) ?></span></td>
                    <td><?= e($branch['city'] ?? '-') ?></td>
                    <td class="text-muted"><?= e($branch['phone'] ?? '-') ?></td>
                    <td class="text-sm"><?= date('g:i A', strtotime($branch['opening_time'])) ?> - <?= date('g:i A', strtotime($branch['closing_time'])) ?></td>
                    <td><?= $branch['dept_count'] ?></td>
                    <td><?= $branch['counter_count'] ?></td>
                    <td>
                        <span class="badge-tf <?= $branch['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
                            <?= $branch['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= $branch['id'] ?>">
                                <button class="btn-tf btn-ghost btn-icon btn-sm" data-tooltip="Toggle Status"><i class="bi bi-toggle-on"></i></button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this branch?');">
                                <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $branch['id'] ?>">
                                <button class="btn-tf btn-ghost btn-icon btn-sm" style="color:var(--accent-danger);" data-tooltip="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($branches)): ?>
                <tr><td colspan="9" class="text-center text-secondary p-6">No branches found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div class="modal-backdrop-tf" id="create-backdrop" onclick="this.classList.remove('active');document.getElementById('create-modal').classList.remove('active');"></div>
<div class="modal-tf glass-command" id="create-modal" style="width:min(90vw,560px);">
    <div class="modal-title-tf">Add Branch</div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
        <input type="hidden" name="action" value="create">
        <div class="form-group"><label class="form-label">Branch Name *</label><input type="text" class="form-input" name="name" required></div>
        <div class="grid-2">
            <div class="form-group"><label class="form-label">Code *</label><input type="text" class="form-input" name="code" required maxlength="10" placeholder="e.g. MN"></div>
            <div class="form-group"><label class="form-label">Phone</label><input type="tel" class="form-input" name="phone"></div>
        </div>
        <div class="form-group"><label class="form-label">Address</label><input type="text" class="form-input" name="address"></div>
        <div class="grid-2">
            <div class="form-group"><label class="form-label">City</label><input type="text" class="form-input" name="city"></div>
            <div class="form-group"><label class="form-label">State</label><input type="text" class="form-input" name="state"></div>
        </div>
        <div class="grid-2">
            <div class="form-group"><label class="form-label">Opens At</label><input type="time" class="form-input" name="opening_time" value="08:00"></div>
            <div class="form-group"><label class="form-label">Closes At</label><input type="time" class="form-input" name="closing_time" value="18:00"></div>
        </div>
        <div class="modal-actions-tf">
            <button type="button" class="btn-tf btn-secondary" onclick="document.getElementById('create-modal').classList.remove('active');document.getElementById('create-backdrop').classList.remove('active');">Cancel</button>
            <button type="submit" class="btn-tf btn-primary"><i class="bi bi-check"></i> Create Branch</button>
        </div>
    </form>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
