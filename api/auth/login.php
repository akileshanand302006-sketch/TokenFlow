<?php
/**
 * TokenFlow Pro — Login API
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInitAPI();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    Response::validationError(['email' => 'Email and password are required.']);
}

$result = Auth::login($email, $password);

if ($result['success']) {
    $result['redirect'] = Auth::getDashboardUrl();
}

if ($result['success']) {
    Response::success($result, $result['message']);
} else {
    Response::error($result['message'], 401);
}
