<?php
/**
 * TokenFlow Pro — Database Configuration
 * PDO connection with error handling
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'tokenflow_pro');
define('DB_USER', 'root');
define('DB_PASS', '1234');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection (singleton)
 */
function getDBConnection(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            if (defined('API_REQUEST') && API_REQUEST) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
            die('Database connection failed. Please check configuration.');
        }
    }
    
    return $pdo;
}
