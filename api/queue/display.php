<?php
/**
 * TokenFlow Pro — Display API (Public, no auth)
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInitAPI();

$branchId = (int)($_GET['branch_id'] ?? 1);
$db = Database::getInstance();

$serving = $db->fetchAll(
    "SELECT t.display_number, s.name as service_name, c.number as counter_number
     FROM tokens t JOIN services s ON t.service_id = s.id LEFT JOIN counters c ON t.counter_id = c.id
     WHERE t.branch_id = ? AND t.date = CURDATE() AND t.status = 'serving'
     ORDER BY t.service_start_time DESC", [$branchId]
);

$upcoming = $db->fetchAll(
    "SELECT t.display_number, t.type, t.priority_score, s.name as service_name
     FROM tokens t JOIN services s ON t.service_id = s.id
     WHERE t.branch_id = ? AND t.date = CURDATE() AND t.status = 'waiting'
     ORDER BY t.priority_score DESC, t.id ASC LIMIT 20", [$branchId]
);

Response::success(['serving' => $serving, 'upcoming' => $upcoming]);
