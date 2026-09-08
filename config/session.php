<?php
/**
 * TokenFlow Pro — Session Configuration
 */

if (session_status() === PHP_SESSION_NONE) {
    // Secure session settings
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME ?? 3600);
    
    session_name('tokenflow_session');
    session_start();
    
    // Regenerate session ID periodically to prevent fixation
    if (!isset($_SESSION['_tf_created'])) {
        $_SESSION['_tf_created'] = time();
    } elseif (time() - $_SESSION['_tf_created'] > 1800) {
        // Regenerate every 30 minutes
        session_regenerate_id(true);
        $_SESSION['_tf_created'] = time();
    }
}
