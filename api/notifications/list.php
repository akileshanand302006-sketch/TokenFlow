<?php
/**
 * TokenFlow Pro — Notifications API
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInitAPI();

Auth::requireLogin();

$userId = Auth::getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? null;
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    
    require_once INCLUDES_PATH . 'NotificationEngine.php';
    $result = NotificationEngine::getForUser($userId, $type, $limit, $offset);
    
    Response::success($result);
}

Response::error('Method not allowed.', 405);
