<?php
/**
 * TokenFlow Pro — Live Queue API
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInitAPI();
Auth::requireLogin();

$branchId = (int)($_GET['branch_id'] ?? 1);

$db = Database::getInstance();

// Currently serving tokens
$serving = $db->fetchAll(
    "SELECT t.display_number, t.token_number, s.name as service_name, d.name as department_name, 
            c.number as counter_number, c.name as counter_name, t.service_start_time
     FROM tokens t
     JOIN services s ON t.service_id = s.id
     JOIN departments d ON t.department_id = d.id
     LEFT JOIN counters c ON t.counter_id = c.id
     WHERE t.branch_id = ? AND t.date = CURDATE() AND t.status = 'serving'
     ORDER BY t.service_start_time DESC",
    [$branchId]
);

// Department queues
$departments = $db->fetchAll(
    "SELECT d.id, d.name, d.code, d.icon, d.color FROM departments d 
     WHERE d.branch_id = ? AND d.is_active = 1 ORDER BY d.sort_order, d.name",
    [$branchId]
);

foreach ($departments as &$dept) {
    $dept['waiting'] = (int)$db->fetchColumn(
        "SELECT COUNT(*) FROM tokens WHERE department_id = ? AND date = CURDATE() AND status = 'waiting'",
        [$dept['id']]
    );
    $dept['completed'] = (int)$db->fetchColumn(
        "SELECT COUNT(*) FROM tokens WHERE department_id = ? AND date = CURDATE() AND status = 'completed'",
        [$dept['id']]
    );
    $dept['avg_wait'] = round((float)$db->fetchColumn(
        "SELECT AVG(actual_wait_minutes) FROM tokens WHERE department_id = ? AND date = CURDATE() AND status = 'completed' AND actual_wait_minutes IS NOT NULL",
        [$dept['id']]
    ));
    $dept['active_counters'] = (int)$db->fetchColumn(
        "SELECT COUNT(DISTINCT c.id) FROM counters c JOIN staff_assignments sa ON sa.counter_id = c.id WHERE sa.department_id = ? AND c.status = 'open' AND sa.is_online = 1",
        [$dept['id']]
    );
    $dept['queue'] = $db->fetchAll(
        "SELECT t.display_number, t.type, t.estimated_wait_minutes, t.priority_score, s.name as service_name
         FROM tokens t JOIN services s ON t.service_id = s.id
         WHERE t.department_id = ? AND t.date = CURDATE() AND t.status = 'waiting'
         ORDER BY t.priority_score DESC, t.id ASC LIMIT 10",
        [$dept['id']]
    );
}

Response::success([
    'serving' => $serving,
    'departments' => $departments,
    'timestamp' => date('Y-m-d H:i:s')
]);
