<?php
/**
 * TokenFlow Pro — Departments List API
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInitAPI();
Auth::requireLogin();

$branchId = (int)($_GET['branch_id'] ?? 0);
if (!$branchId) Response::error('Branch ID required.');

$db = Database::getInstance();
$departments = $db->fetchAll(
    "SELECT d.*, 
            (SELECT COUNT(*) FROM tokens t WHERE t.department_id = d.id AND t.date = CURDATE() AND t.status = 'waiting') as queue_count,
            (SELECT AVG(estimated_wait_minutes) FROM tokens t WHERE t.department_id = d.id AND t.date = CURDATE() AND t.status = 'waiting') as avg_wait
     FROM departments d 
     WHERE d.branch_id = ? AND d.is_active = 1 
     ORDER BY d.sort_order, d.name",
    [$branchId]
);

Response::success($departments);
