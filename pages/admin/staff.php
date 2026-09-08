<?php
/**
 * TokenFlow Pro — Admin: Manage Staff
 */
$pageTitle = 'Manage Staff';
$pageBackground = 'admin-dashboard';
$currentPage = 'staff';
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
    
    if ($action === 'create_staff') {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? 'staff123';
        
        if ($name && $email) {
            $exists = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE email = ?", [$email]);
            if ($exists) {
                $message = "error:Email already exists.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->query(
                    "INSERT INTO users (org_id, full_name, email, phone, password_hash, role) VALUES (?,?,?,?,?,'staff')",
                    [$orgId, $name, $email, $phone, $hash]
                );
                $staffId = $db->lastInsertId();
                
                // Assignment
                if (!empty($_POST['branch_id']) && !empty($_POST['department_id'])) {
                    $db->query(
                        "INSERT INTO staff_assignments (user_id, branch_id, department_id, counter_id, shift_start, shift_end) VALUES (?,?,?,?,?,?)",
                        [$staffId, (int)$_POST['branch_id'], (int)$_POST['department_id'], 
                         !empty($_POST['counter_id']) ? (int)$_POST['counter_id'] : null,
                         $_POST['shift_start'] ?? '08:00:00', $_POST['shift_end'] ?? '16:00:00']
                    );
                }
                Audit::log('staff_created', 'users', $staffId);
                $message = "Staff member '$name' created.";
            }
        }
    }
    if ($action === 'toggle') {
        $db->query("UPDATE users SET is_active = NOT is_active WHERE id = ? AND org_id = ?", [(int)$_POST['id'], $orgId]);
        $message = "Status updated.";
    }
}

$staffMembers = $db->fetchAll(
    "SELECT u.*, sa.branch_id, sa.department_id, sa.counter_id, sa.is_online, sa.shift_start, sa.shift_end,
            b.name as branch_name, d.name as dept_name, c.number as counter_number,
            (SELECT COUNT(*) FROM tokens t WHERE t.served_by = u.id AND t.date = CURDATE() AND t.status = 'completed') as today_completed
     FROM users u
     LEFT JOIN staff_assignments sa ON sa.user_id = u.id
     LEFT JOIN branches b ON sa.branch_id = b.id
     LEFT JOIN departments d ON sa.department_id = d.id
     LEFT JOIN counters c ON sa.counter_id = c.id
     WHERE u.org_id = ? AND u.role = 'staff'
     ORDER BY u.full_name", [$orgId]
);

$branches = $db->fetchAll("SELECT id, name FROM branches WHERE org_id = ? AND is_active = 1", [$orgId]);
$departments = $db->fetchAll("SELECT d.id, d.name, d.branch_id FROM departments d JOIN branches b ON d.branch_id = b.id WHERE b.org_id = ? AND d.is_active = 1", [$orgId]);
$counters = $db->fetchAll("SELECT c.id, c.name, c.number, c.branch_id FROM counters c JOIN branches b ON c.branch_id = b.id WHERE b.org_id = ?", [$orgId]);

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <h1 class="page-header-title">Manage Staff</h1>
        <button class="btn-tf btn-primary" onclick="document.getElementById('create-modal').classList.add('active');document.getElementById('create-backdrop').classList.add('active');">
            <i class="bi bi-plus-circle"></i> Add Staff
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="animate-fade-up mb-4" style="padding:var(--space-3) var(--space-4);border-radius:var(--radius-md);
    <?= str_starts_with($message, 'error:') ? 'background:rgba(248,113,113,0.1);border:1px solid var(--accent-danger);color:var(--accent-danger);' : 'background:rgba(52,211,153,0.1);border:1px solid var(--accent-success);color:var(--accent-success);' ?>">
    <i class="bi <?= str_starts_with($message, 'error:') ? 'bi-exclamation-circle' : 'bi-check-circle' ?>"></i> <?= e(str_starts_with($message, 'error:') ? substr($message, 6) : $message) ?>
</div>
<?php endif; ?>

<div class="glass-surface card-tf animate-fade-up">
    <div class="table-container">
        <table class="table-tf">
            <thead><tr><th>Staff</th><th>Email</th><th>Branch</th><th>Department</th><th>Counter</th><th>Online</th><th>Today</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($staffMembers as $s): ?>
                <tr>
                    <td class="flex items-center gap-2">
                        <div class="avatar avatar-sm"><?= getInitials($s['full_name']) ?></div>
                        <strong><?= e($s['full_name']) ?></strong>
                    </td>
                    <td class="text-sm text-muted"><?= e($s['email']) ?></td>
                    <td><?= e($s['branch_name'] ?? '-') ?></td>
                    <td><?= e($s['dept_name'] ?? '-') ?></td>
                    <td><?= $s['counter_number'] ? 'C'.$s['counter_number'] : '-' ?></td>
                    <td><span class="status-dot <?= $s['is_online'] ? 'online' : 'offline' ?>"></span></td>
                    <td><span class="badge-tf badge-success"><?= $s['today_completed'] ?></span></td>
                    <td><span class="badge-tf <?= $s['is_active'] ? 'badge-success' : 'badge-secondary' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                    <td>
                        <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button class="btn-tf btn-ghost btn-icon btn-sm"><i class="bi bi-toggle-on"></i></button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop-tf" id="create-backdrop" onclick="this.classList.remove('active');document.getElementById('create-modal').classList.remove('active');"></div>
<div class="modal-tf glass-command" id="create-modal" style="width:min(90vw,560px);">
    <div class="modal-title-tf">Add Staff Member</div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>"><input type="hidden" name="action" value="create_staff">
        <div class="grid-2">
            <div class="form-group"><label class="form-label">Full Name *</label><input type="text" class="form-input" name="full_name" required></div>
            <div class="form-group"><label class="form-label">Email *</label><input type="email" class="form-input" name="email" required></div>
        </div>
        <div class="grid-2">
            <div class="form-group"><label class="form-label">Phone</label><input type="tel" class="form-input" name="phone"></div>
            <div class="form-group"><label class="form-label">Password</label><input type="text" class="form-input" name="password" value="staff123"></div>
        </div>
        <div class="grid-2">
            <div class="form-group"><label class="form-label">Branch</label><select class="form-select" name="branch_id">
                <option value="">— Select —</option><?php foreach ($branches as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Department</label><select class="form-select" name="department_id">
                <option value="">— Select —</option><?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="grid-2">
            <div class="form-group"><label class="form-label">Counter</label><select class="form-select" name="counter_id">
                <option value="">— Select —</option><?php foreach ($counters as $c): ?><option value="<?= $c['id'] ?>">C<?= $c['number'] ?> - <?= e($c['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Shift Start</label><input type="time" class="form-input" name="shift_start" value="08:00"></div>
        </div>
        <div class="modal-actions-tf">
            <button type="button" class="btn-tf btn-secondary" onclick="document.getElementById('create-modal').classList.remove('active');document.getElementById('create-backdrop').classList.remove('active');">Cancel</button>
            <button type="submit" class="btn-tf btn-primary"><i class="bi bi-check"></i> Create Staff</button>
        </div>
    </form>
</div>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
