<?php
/**
 * TokenFlow Pro — Token Generation API
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInitAPI();
Auth::requireLogin();
CSRF::requireValid();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$branchId = (int)($input['branch_id'] ?? 0);
$departmentId = (int)($input['department_id'] ?? 0);
$serviceId = (int)($input['service_id'] ?? 0);
$type = $input['type'] ?? 'normal';
$notes = trim($input['notes'] ?? '');

if (!$branchId || !$departmentId || !$serviceId) {
    Response::validationError(['service_id' => 'Branch, department, and service are required.']);
}

$result = TokenEngine::generate([
    'user_id' => Auth::getUserId(),
    'branch_id' => $branchId,
    'department_id' => $departmentId,
    'service_id' => $serviceId,
    'type' => $type,
    'notes' => $notes
]);

if ($result['success']) {
    Response::success($result['token'], $result['message']);
} else {
    Response::error($result['message'], 400);
}
