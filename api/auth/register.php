<?php
/**
 * TokenFlow Pro — Register API
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInitAPI();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$result = Auth::register($input);

if ($result['success']) {
    Response::success($result, $result['message']);
} else {
    Response::error($result['message'], 400);
}
