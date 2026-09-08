<?php
/**
 * TokenFlow Pro — Logout
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInit();

Auth::logout();

// Check if AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logged out.']);
    exit;
}

header('Location: ' . BASE_URL . 'pages/public/login.php');
exit;
