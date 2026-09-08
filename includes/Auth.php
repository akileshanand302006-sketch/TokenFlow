<?php
/**
 * TokenFlow Pro — Authentication System
 */

require_once __DIR__ . '/Database.php';

class Auth {
    
    /**
     * Attempt to log in a user
     */
    public static function login(string $email, string $password): array {
        $db = Database::getInstance();
        
        // Check login throttling
        $user = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
        
        // Check if account is locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
            return ['success' => false, 'message' => "Account locked. Try again in {$remaining} minutes."];
        }
        
        // Check if account is active
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Account is deactivated. Contact support.'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            // Increment login attempts
            $attempts = $user['login_attempts'] + 1;
            $lockUntil = null;
            
            if ($attempts >= LOGIN_MAX_ATTEMPTS) {
                $lockUntil = date('Y-m-d H:i:s', time() + (LOGIN_LOCKOUT_MINUTES * 60));
                $attempts = 0;
            }
            
            $db->execute(
                "UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?",
                [$attempts, $lockUntil, $user['id']]
            );
            
            if ($lockUntil) {
                return ['success' => false, 'message' => 'Too many failed attempts. Account locked for ' . LOGIN_LOCKOUT_MINUTES . ' minutes.'];
            }
            
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
        
        // Successful login
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_avatar'] = $user['avatar_path'];
        $_SESSION['org_id'] = $user['org_id'];
        $_SESSION['login_time'] = time();
        
        // Reset login attempts and update last login
        $db->execute(
            "UPDATE users SET login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?",
            [$user['id']]
        );
        
        // Audit log
        Audit::log('LOGIN', 'user', $user['id'], 'User logged in');
        
        return [
            'success' => true,
            'message' => 'Login successful.',
            'user' => [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'avatar' => $user['avatar_path']
            ]
        ];
    }
    
    /**
     * Register a new user
     */
    public static function register(array $data): array {
        $db = Database::getInstance();
        
        // Validate required fields
        $required = ['full_name', 'email', 'password', 'confirm_password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field '{$field}' is required."];
            }
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }
        
        // Check duplicate email
        $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$data['email']]);
        if ($existing) {
            return ['success' => false, 'message' => 'Email already registered.'];
        }
        
        // Validate password
        if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.'];
        }
        
        if ($data['password'] !== $data['confirm_password']) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }
        
        // Create user
        $userId = $db->insert('users', [
            'full_name' => htmlspecialchars(trim($data['full_name']), ENT_QUOTES, 'UTF-8'),
            'email' => strtolower(trim($data['email'])),
            'phone' => htmlspecialchars(trim($data['phone'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'role' => 'customer',
            'org_id' => $data['org_id'] ?? 1,
            'preferred_language' => $data['language'] ?? 'en'
        ]);
        
        // Create welcome notification
        $db->insert('notifications', [
            'user_id' => $userId,
            'type' => 'system',
            'title' => 'Welcome to TokenFlow Pro!',
            'message' => 'Your account has been created successfully. Start by generating your first token.',
            'icon' => 'bi-stars'
        ]);
        
        Audit::log('USER_REGISTERED', 'user', $userId, 'New customer registered');
        
        return [
            'success' => true,
            'message' => 'Registration successful! You can now log in.',
            'user_id' => $userId
        ];
    }
    
    /**
     * Log out the current user
     */
    public static function logout(): void {
        if (isset($_SESSION['user_id'])) {
            Audit::log('LOGOUT', 'user', $_SESSION['user_id'], 'User logged out');
        }
        
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user data (fresh from database)
     */
    public static function getCurrentUser(): ?array {
        if (!self::isLoggedIn()) return null;
        
        $db = Database::getInstance();
        $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
        if (!$user) return null;
        
        return [
            'id' => (int)$user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? '',
            'role' => $user['role'],
            'avatar' => $user['avatar_path'] ?? null,
            'org_id' => $user['org_id'] ?? null,
            'created_at' => $user['created_at'] ?? null
        ];
    }
    
    /**
     * Get current user ID
     */
    public static function getUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user role
     */
    public static function getRole(): ?string {
        return $_SESSION['user_role'] ?? null;
    }
    
    /**
     * Check if current user has a specific role
     */
    public static function hasRole(string ...$roles): bool {
        $currentRole = self::getRole();
        return $currentRole && in_array($currentRole, $roles);
    }
    
    /**
     * Require authentication — redirect or return 401 if not logged in
     */
    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            if (defined('API_REQUEST') && API_REQUEST) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Authentication required.']);
                exit;
            }
            header('Location: ' . BASE_URL . 'pages/public/login.php');
            exit;
        }
    }
    
    /**
     * Require specific role(s)
     */
    public static function requireRole(string ...$roles): void {
        self::requireLogin();
        
        if (!self::hasRole(...$roles)) {
            if (defined('API_REQUEST') && API_REQUEST) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied.']);
                exit;
            }
            http_response_code(403);
            include PAGES_PATH . 'errors/403.php';
            exit;
        }
    }
    
    /**
     * Get dashboard URL based on role
     */
    public static function getDashboardUrl(): string {
        switch (self::getRole()) {
            case 'admin':
            case 'super_admin':
                return PAGES_URL . 'admin/dashboard.php';
            case 'staff':
                return PAGES_URL . 'staff/dashboard.php';
            case 'customer':
            default:
                return PAGES_URL . 'customer/dashboard.php';
        }
    }
    
    /**
     * Generate password reset token
     */
    public static function generateResetToken(string $email): array {
        $db = Database::getInstance();
        
        $user = $db->fetchOne("SELECT id, full_name FROM users WHERE email = ? AND is_active = 1", [$email]);
        
        if (!$user) {
            // Don't reveal if email exists
            return ['success' => true, 'message' => 'If an account exists with that email, a reset link will be sent.'];
        }
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        $db->execute(
            "UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?",
            [password_hash($token, PASSWORD_BCRYPT), $expires, $user['id']]
        );
        
        Audit::log('PASSWORD_RESET_REQUESTED', 'user', $user['id'], 'Password reset token generated');
        
        return [
            'success' => true,
            'message' => 'If an account exists with that email, a reset link will be sent.',
            'token' => $token // In production, this would be emailed
        ];
    }
}
