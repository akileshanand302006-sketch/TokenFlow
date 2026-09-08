<?php
/**
 * TokenFlow Pro — Token Status API
 * GET: Check token status  |  POST: Update token status (cancel, complete, skip, call-next)
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInitAPI();
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tokenId = (int)($_GET['id'] ?? 0);
    if (!$tokenId) Response::error('Token ID required.');
    
    $db = Database::getInstance();
    $token = $db->fetch(
        "SELECT t.*, s.name as service_name, d.name as department_name, 
                b.name as branch_name, c.number as counter_number,
                u2.full_name as served_by_name
         FROM tokens t
         JOIN services s ON t.service_id = s.id
         JOIN departments d ON t.department_id = d.id
         JOIN branches b ON t.branch_id = b.id
         LEFT JOIN counters c ON t.counter_id = c.id
         LEFT JOIN users u2 ON t.served_by = u2.id
         WHERE t.id = ?",
        [$tokenId]
    );
    
    if (!$token) Response::error('Token not found.', 404);
    
    // Count people ahead
    if ($token['status'] === 'waiting') {
        $token['people_ahead'] = $db->fetchColumn(
            "SELECT COUNT(*) FROM tokens WHERE department_id = ? AND date = ? AND status = 'waiting' AND 
             (priority_score > ? OR (priority_score = ? AND id < ?))",
            [$token['department_id'], $token['date'], $token['priority_score'], $token['priority_score'], $token['id']]
        );
    }
    
    Response::success($token);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::requireValid();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $tokenId = (int)($input['token_id'] ?? 0);
    
    switch ($action) {
        case 'cancel':
            $result = TokenEngine::cancel($tokenId, Auth::getUserId());
            break;
        case 'call-next':
            $counterId = (int)($input['counter_id'] ?? 0);
            $departmentId = (int)($input['department_id'] ?? 0);
            $result = TokenEngine::callNext($departmentId, $counterId, Auth::getUserId());
            break;
        case 'complete':
            $result = TokenEngine::complete($tokenId, Auth::getUserId());
            break;
        case 'skip':
            $reason = $input['reason'] ?? 'no_show';
            $result = TokenEngine::skip($tokenId, Auth::getUserId(), $reason);
            break;
        case 'recall':
            $result = TokenEngine::recall($tokenId, Auth::getUserId());
            break;
        default:
            Response::error('Invalid action.');
    }
    
    if ($result['success']) {
        Response::success($result, $result['message'] ?? 'Success');
    } else {
        Response::error($result['message'] ?? 'Action failed.', 400);
    }
}

Response::error('Method not allowed.', 405);
