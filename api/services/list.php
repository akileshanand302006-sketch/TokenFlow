<?php
/**
 * TokenFlow Pro — Services List API
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInitAPI();
Auth::requireLogin();

$departmentId = (int)($_GET['department_id'] ?? 0);
if (!$departmentId) Response::error('Department ID required.');

$db = Database::getInstance();
$services = $db->fetchAll(
    "SELECT s.* FROM services s WHERE s.department_id = ? AND s.is_active = 1 ORDER BY s.sort_order, s.name",
    [$departmentId]
);

Response::success($services);
