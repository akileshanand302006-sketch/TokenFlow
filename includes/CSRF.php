<?php
/**
 * TokenFlow Pro — CSRF Protection
 */

class CSRF {
    
    /**
     * Generate or retrieve CSRF token
     */
    public static function getToken(): string {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Output hidden form field
     */
    public static function field(): string {
        $token = self::getToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Get meta tag for AJAX requests
     */
    public static function metaTag(): string {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Validate CSRF token from request
     */
    public static function validate(?string $token = null): bool {
        if ($token === null) {
            // Check POST (csrf_token, tf_csrf_token), then headers
            $token = $_POST['csrf_token'] ?? $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_XSRF_TOKEN'] ?? null;
        }
        
        if (empty($token) || empty($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Validate and abort if invalid
     */
    public static function requireValid(): void {
        if (!self::validate()) {
            if (defined('API_REQUEST') && API_REQUEST) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
                exit;
            }
            http_response_code(403);
            die('Invalid security token. Please refresh the page.');
        }
    }
    
    /**
     * Regenerate token
     */
    public static function regenerate(): string {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        return $_SESSION[CSRF_TOKEN_NAME];
    }
}
