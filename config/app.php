<?php
/**
 * TokenFlow Pro — Application Configuration
 */

// Application Identity
define('APP_NAME', 'TokenFlow Pro');
define('APP_VERSION', '1.0.0');
define('APP_TAGLINE', 'Intelligent Digital Queue & Service Optimization Platform');

// Paths
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('CONFIG_PATH', ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('ASSETS_PATH', ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR);
define('PAGES_PATH', ROOT_PATH . 'pages' . DIRECTORY_SEPARATOR);
define('COMPONENTS_PATH', ROOT_PATH . 'components' . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR);

// Base URL — dynamically detects root vs subdirectory (Laragon / PHP dev server / virtual host)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

$basePath = '/';
if (stristr($scriptName, '/TokenFlow/')) {
    $basePath = '/TokenFlow/';
} elseif (stristr($scriptName, '/tokenflow/')) {
    $basePath = '/tokenflow/';
}

define('BASE_URL', $protocol . '://' . $host . $basePath);
define('ASSETS_URL', BASE_URL . 'assets/');
define('API_URL', BASE_URL . 'api/');
define('PAGES_URL', BASE_URL . 'pages/');

// Security
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_NAME', 'tf_csrf_token');
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('PASSWORD_MIN_LENGTH', 8);

// Queue Configuration
define('QUEUE_POLL_INTERVAL', 3000); // 3 seconds in milliseconds
define('TOKEN_PREFIX_LENGTH', 3);
define('MAX_DAILY_TOKENS', 999);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// File Upload
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// Timezone
date_default_timezone_set('Asia/Kolkata');
