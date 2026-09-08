<?php
/**
 * TokenFlow Pro — Utility Helper Functions
 */

/**
 * Sanitize output for HTML
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Get greeting based on time of day
 */
function getGreeting(): string {
    $hour = (int) date('H');
    if ($hour < 12) return 'Good morning';
    if ($hour < 17) return 'Good afternoon';
    return 'Good evening';
}

/**
 * Format date for display
 */
function formatDate(string $date, string $format = 'M d, Y'): string {
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime(string $datetime, string $format = 'M d, Y h:i A'): string {
    return date($format, strtotime($datetime));
}

/**
 * Format time ago
 */
function timeAgo(string $datetime): string {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return formatDate($datetime);
}

/**
 * Format minutes into readable duration
 */
function formatDuration(int $minutes): string {
    if ($minutes < 1) return '< 1 min';
    if ($minutes < 60) return $minutes . ' min';
    
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    if ($mins === 0) return $hours . ' hr';
    return $hours . ' hr ' . $mins . ' min';
}

/**
 * Generate a random string
 */
function generateRandomString(int $length = 32): string {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get status badge class
 */
function getStatusBadgeClass(string $status): string {
    $map = [
        'waiting' => 'badge-warning',
        'serving' => 'badge-primary',
        'completed' => 'badge-success',
        'skipped' => 'badge-danger',
        'cancelled' => 'badge-secondary',
        'no_show' => 'badge-danger',
        'transferred' => 'badge-info',
        'open' => 'badge-success',
        'closed' => 'badge-secondary',
        'break' => 'badge-warning',
        'maintenance' => 'badge-danger',
        'scheduled' => 'badge-info',
        'confirmed' => 'badge-primary',
        'checked_in' => 'badge-success',
        'in_progress' => 'badge-primary',
        'resolved' => 'badge-success',
        'low' => 'badge-success',
        'moderate' => 'badge-warning',
        'high' => 'badge-danger',
        'critical' => 'badge-danger-glow',
    ];
    
    return $map[$status] ?? 'badge-secondary';
}

/**
 * Get status display label
 */
function getStatusLabel(string $status): string {
    return ucfirst(str_replace('_', ' ', $status));
}

/**
 * Get queue health color
 */
function getQueueHealthColor(string $health): string {
    $map = [
        'low' => 'var(--accent-success)',
        'moderate' => 'var(--accent-warning)',
        'high' => 'var(--accent-danger)',
        'critical' => 'var(--accent-danger)',
    ];
    return $map[$health] ?? 'var(--accent-info)';
}

/**
 * Truncate text
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . $suffix;
}

/**
 * Get initials from name
 */
function getInitials(string $name, int $count = 2): string {
    $words = explode(' ', trim($name));
    $initials = '';
    
    foreach (array_slice($words, 0, $count) as $word) {
        $initials .= strtoupper(mb_substr($word, 0, 1));
    }
    
    return $initials;
}

/**
 * Format number with abbreviation (12845 → 12.8K)
 */
function formatNumber($number): string {
    if ($number >= 1000000) return round($number / 1000000, 1) . 'M';
    if ($number >= 1000) return round($number / 1000, 1) . 'K';
    return (string) $number;
}

/**
 * Get current page name from URL
 */
function getCurrentPage(): string {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $filename = basename($path, '.php');
    return $filename;
}

/**
 * Check if current page matches
 */
function isCurrentPage(string $page): bool {
    return getCurrentPage() === $page;
}

/**
 * Generate breadcrumb from path
 */
function getBreadcrumb(): array {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = array_filter(explode('/', $path));
    $breadcrumb = [];
    $accumulated = '';
    
    foreach ($parts as $part) {
        if ($part === 'TokenFlow') continue;
        if ($part === 'pages') continue;
        
        $accumulated .= '/' . $part;
        $label = ucfirst(str_replace(['-', '_', '.php'], [' ', ' ', ''], $part));
        $breadcrumb[] = ['label' => $label, 'url' => $accumulated];
    }
    
    return $breadcrumb;
}

/**
 * Load init (config + session + includes) 
 */
function tfInit(): void {
    require_once __DIR__ . '/../config/app.php';
    require_once __DIR__ . '/../config/session.php';
    require_once __DIR__ . '/Database.php';
    require_once __DIR__ . '/Auth.php';
    require_once __DIR__ . '/CSRF.php';
    require_once __DIR__ . '/Audit.php';
    require_once __DIR__ . '/Validation.php';
    require_once __DIR__ . '/Response.php';
    require_once __DIR__ . '/NotificationEngine.php';
    require_once __DIR__ . '/TokenEngine.php';
    require_once __DIR__ . '/QueueEngine.php';
    require_once __DIR__ . '/QueueIntelligence.php';
}

/**
 * Load init for API endpoints
 */
function tfInitAPI(): void {
    define('API_REQUEST', true);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    
    tfInit();
}
