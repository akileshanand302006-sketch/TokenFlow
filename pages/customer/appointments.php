<?php
/**
 * TokenFlow Pro — Customer Appointments
 */
$pageTitle = 'Appointments';
$pageBackground = 'customer-dashboard';
$currentPage = 'appointments';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireLogin();

$user = Auth::getCurrentUser();
$pageRole = $user['role'];
$userId = $user['id'];
$db = Database::getInstance();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::requireValid();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'book') {
        $db->query(
            "INSERT INTO appointments (user_id, branch_id, department_id, service_id, appointment_date, appointment_time, notes, status) VALUES (?,?,?,?,?,?,?,'scheduled')",
            [$userId, (int)$_POST['branch_id'], (int)$_POST['department_id'], (int)$_POST['service_id'], 
             $_POST['appointment_date'], $_POST['appointment_time'], trim($_POST['notes'] ?? '')]
        );
        $message = "Appointment booked successfully!";
    }
    if ($action === 'cancel') {
        $db->query("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND user_id = ?", [(int)$_POST['id'], $userId]);
        $message = "Appointment cancelled.";
    }
}

$upcoming = $db->fetchAll(
    "SELECT a.*, s.name as service_name, d.name as dept_name, b.name as branch_name
     FROM appointments a JOIN services s ON a.service_id = s.id JOIN departments d ON a.department_id = d.id JOIN branches b ON a.branch_id = b.id
     WHERE a.user_id = ? AND a.appointment_date >= CURDATE() AND a.status NOT IN ('cancelled','completed')
     ORDER BY a.appointment_date, a.appointment_time", [$userId]
);

$past = $db->fetchAll(
    "SELECT a.*, s.name as service_name, d.name as dept_name, b.name as branch_name
     FROM appointments a JOIN services s ON a.service_id = s.id JOIN departments d ON a.department_id = d.id JOIN branches b ON a.branch_id = b.id
     WHERE a.user_id = ? AND (a.appointment_date < CURDATE() OR a.status IN ('cancelled','completed'))
     ORDER BY a.appointment_date DESC LIMIT 10", [$userId]
);

$branches = $db->fetchAll("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name");
$services = $db->fetchAll(
    "SELECT s.id, s.name, s.department_id, d.name as dept_name, d.branch_id FROM services s 
     JOIN departments d ON s.department_id = d.id WHERE s.is_active = 1 ORDER BY d.name, s.name"
);

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <div class="page-header-top">
        <h1 class="page-header-title"><i class="bi bi-calendar-check" style="color:var(--accent-info);"></i> Appointments</h1>
        <button class="btn-tf btn-primary" onclick="document.getElementById('book-modal').classList.add('active');document.getElementById('book-backdrop').classList.add('active');">
            <i class="bi bi-plus-circle"></i> Book Appointment
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="animate-fade-up mb-4" style="padding:var(--space-3) var(--space-4);border-radius:var(--radius-md);background:rgba(52,211,153,0.1);border:1px solid var(--accent-success);color:var(--accent-success);">
    <i class="bi bi-check-circle"></i> <?= e($message) ?>
</div>
<?php endif; ?>

<!-- Upcoming -->
<div class="section-tf animate-fade-up">
    <h2 class="section-title-tf mb-4"><i class="bi bi-calendar-event"></i> Upcoming</h2>
    <?php if (empty($upcoming)): ?>
        <div class="glass-surface card-tf text-center p-8">
            <div class="empty-state-icon" style="margin:0 auto var(--space-4);"><i class="bi bi-calendar-x"></i></div>
            <p class="text-secondary">No upcoming appointments.</p>
        </div>
    <?php else: ?>
    <div class="grid-3">
        <?php foreach ($upcoming as $appt): ?>
        <div class="glass-elevated card-tf">
            <div class="flex items-center justify-between mb-3">
                <span class="badge-tf <?= $appt['status'] === 'confirmed' ? 'badge-success' : 'badge-info' ?>"><?= ucfirst($appt['status']) ?></span>
                <span class="text-xs text-muted">#<?= $appt['id'] ?></span>
            </div>
            <div class="text-heading mb-1"><?= e($appt['service_name']) ?></div>
            <div class="text-sm text-secondary mb-3"><?= e($appt['dept_name']) ?> — <?= e($appt['branch_name']) ?></div>
            <div class="flex items-center gap-2 mb-1"><i class="bi bi-calendar3 text-muted"></i> <span class="text-sm"><?= date('D, M d, Y', strtotime($appt['appointment_date'])) ?></span></div>
            <div class="flex items-center gap-2 mb-4"><i class="bi bi-clock text-muted"></i> <span class="text-sm"><?= date('g:i A', strtotime($appt['appointment_time'])) ?></span></div>
            <?php if ($appt['notes']): ?><div class="text-xs text-muted mb-3"><?= e($appt['notes']) ?></div><?php endif; ?>
            <form method="POST"><input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>"><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= $appt['id'] ?>">
                <button type="submit" class="btn-tf btn-ghost btn-sm" style="color:var(--accent-danger);" onclick="return confirm('Cancel this appointment?');"><i class="bi bi-x-circle"></i> Cancel</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Past -->
<?php if (!empty($past)): ?>
<div class="section-tf animate-fade-up delay-2 mt-6">
    <h2 class="section-title-tf mb-4"><i class="bi bi-clock-history"></i> Past Appointments</h2>
    <div class="glass-surface card-tf">
        <div class="table-container">
            <table class="table-tf">
                <thead><tr><th>Service</th><th>Date</th><th>Time</th><th>Branch</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($past as $p): ?>
                    <tr>
                        <td><?= e($p['service_name']) ?></td>
                        <td><?= date('M d, Y', strtotime($p['appointment_date'])) ?></td>
                        <td><?= date('g:i A', strtotime($p['appointment_time'])) ?></td>
                        <td class="text-muted"><?= e($p['branch_name']) ?></td>
                        <td><span class="badge-tf <?= getStatusBadgeClass($p['status']) ?>"><?= ucfirst($p['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Book Modal -->
<div class="modal-backdrop-tf" id="book-backdrop" onclick="this.classList.remove('active');document.getElementById('book-modal').classList.remove('active');"></div>
<div class="modal-tf glass-command" id="book-modal" style="width:min(90vw,520px);">
    <div class="modal-title-tf">Book Appointment</div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>"><input type="hidden" name="action" value="book">
        <div class="form-group"><label class="form-label">Branch *</label><select class="form-select" name="branch_id" required>
            <?php foreach ($branches as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Service *</label><select class="form-select" name="service_id" required>
            <?php foreach ($services as $s): ?><option value="<?= $s['id'] ?>" data-dept="<?= $s['department_id'] ?>"><?= e($s['dept_name']) ?> — <?= e($s['name']) ?></option><?php endforeach; ?></select>
            <input type="hidden" name="department_id" id="dept-hidden" value="<?= $services[0]['department_id'] ?? '' ?>"></div>
        <div class="grid-2">
            <div class="form-group"><label class="form-label">Date *</label><input type="date" class="form-input" name="appointment_date" required min="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label class="form-label">Time *</label><input type="time" class="form-input" name="appointment_time" required></div>
        </div>
        <div class="form-group"><label class="form-label">Notes</label><textarea class="form-input" name="notes" rows="2" placeholder="Any special requirements..."></textarea></div>
        <div class="modal-actions-tf">
            <button type="button" class="btn-tf btn-secondary" onclick="document.getElementById('book-modal').classList.remove('active');document.getElementById('book-backdrop').classList.remove('active');">Cancel</button>
            <button type="submit" class="btn-tf btn-primary"><i class="bi bi-calendar-check"></i> Book</button>
        </div>
    </form>
</div>

<script>
document.querySelector('[name="service_id"]')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    document.getElementById('dept-hidden').value = opt.dataset.dept || '';
});
</script>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
