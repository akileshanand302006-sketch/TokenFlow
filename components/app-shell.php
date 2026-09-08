<?php
/**
 * TokenFlow Pro — Application Shell
 * Common wrapper for all authenticated pages
 * 
 * Usage in page files:
 *   $pageTitle = 'Dashboard';
 *   $pageBackground = 'customer-dashboard';
 *   $currentPage = 'dashboard';
 *   $pageRole = 'customer'; // customer, staff, admin
 *   include COMPONENTS_PATH . 'app-shell.php';
 *   // ... page content ...
 *   include COMPONENTS_PATH . 'app-shell-end.php';
 */

if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/../includes/helpers.php';
    tfInit();
}

Auth::requireLogin();

$user = Auth::getCurrentUser();
$pageTitle = $pageTitle ?? 'TokenFlow Pro';
$pageBackground = $pageBackground ?? 'customer-dashboard';
$currentPage = $currentPage ?? getCurrentPage();
$pageRole = $pageRole ?? $user['role'];
$pageDescription = $pageDescription ?? APP_TAGLINE;
$bodyClass = $bodyClass ?? '';

// Get unread notification count
$unreadNotifications = 0;
try {
    $unreadNotifications = NotificationEngine::getUnreadCount($user['id']);
} catch (\Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= e($_SESSION['preferred_language'] ?? 'en') ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#060a13">
    <?= CSRF::metaTag() ?>
    
    <title><?= e($pageTitle) ?> — <?= APP_NAME ?></title>
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- TokenFlow Design System -->
    <link href="<?= ASSETS_URL ?>css/design-system.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/components.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/animations.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/layouts.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/pages.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/responsive.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/light-theme.css" rel="stylesheet">
    
    <!-- PWA -->
    <link rel="manifest" href="<?= BASE_URL ?>manifest.json">
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>images/logo/favicon.png">
    
    <script>
        // Theme initialization (before render to prevent flash)
        const savedTheme = localStorage.getItem('tf_theme') || 'dark';
        if (savedTheme === 'light' || (savedTheme === 'system' && window.matchMedia('(prefers-color-scheme: light)').matches)) {
            document.documentElement.setAttribute('data-theme', 'light');
        }
        
        // Global config
        window.TF = {
            baseUrl: '<?= BASE_URL ?>',
            apiUrl: '<?= API_URL ?>',
            assetsUrl: '<?= ASSETS_URL ?>',
            csrfToken: '<?= CSRF::getToken() ?>',
            userId: <?= $user['id'] ?>,
            userRole: '<?= e($user['role']) ?>',
            userName: '<?= e($user['name']) ?>',
            pollInterval: <?= QUEUE_POLL_INTERVAL ?>
        };
    </script>
</head>
<body class="<?= e($bodyClass) ?>">
    <!-- Skip Link -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Page Background -->
    <div class="page-background" aria-hidden="true">
        <img src="<?= ASSETS_URL ?>images/backgrounds/<?= e($pageBackground) ?>.webp" 
             alt="" loading="eager"
             onerror="this.style.display='none'">
    </div>
    <div class="page-background-overlay" aria-hidden="true"></div>
    <div class="atmosphere-glow" aria-hidden="true"></div>
    
    <!-- Application Shell -->
    <div class="app-shell">
        <!-- Sidebar -->
        <aside class="app-sidebar-wrapper glass-floating" id="sidebar-wrapper">
            <?php include COMPONENTS_PATH . 'sidebar.php'; ?>
            <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">
                <i class="bi bi-chevron-left"></i>
            </button>
        </aside>
        
        <!-- Mobile Overlay -->
        <div class="mobile-overlay" id="mobile-overlay"></div>
        
        <!-- Main Content -->
        <main class="app-main" id="app-main">
            <!-- Top Bar -->
            <?php include COMPONENTS_PATH . 'header.php'; ?>
            
            <!-- Page Content -->
            <div class="content-wrapper page-content" id="main-content">
