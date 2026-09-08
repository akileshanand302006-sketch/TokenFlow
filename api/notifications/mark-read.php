<?php
/**
 * TokenFlow Pro — Mark Notifications Read API
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInitAPI();

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

CSRF::requireValid();

$userId = Auth::getUserId();
$input = json_decode(file_get_contents('php://input'), true);

require_once INCLUDES_PATH . 'NotificationEngine.php';

if (!empty($input['all'])) {
    $count = NotificationEngine::markAllRead($userId);
    Response::success(['marked' => $count], 'All notifications marked as read.');
}

if (!empty($input['id'])) {
    $result = NotificationEngine::markRead((int)$input['id'], $userId);
    Response::success(null, 'Notification marked as read.');
}

Response::error('Invalid request.');
