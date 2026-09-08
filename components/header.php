<?php
/**
 * TokenFlow Pro — Top Navigation Bar
 */

$user = Auth::getCurrentUser();
$breadcrumb = getBreadcrumb();
$pageTitleDisplay = $pageTitle ?? 'Dashboard';
?>
<header class="topbar" id="topbar" role="banner">
    <div class="topbar-left">
        <!-- Mobile Toggle -->
        <button class="topbar-mobile-toggle" id="mobile-menu-toggle" aria-label="Open menu">
            <i class="bi bi-list"></i>
        </button>
        
        <!-- Breadcrumb & Page Title -->
        <div>
            <nav class="topbar-breadcrumb" aria-label="Breadcrumb">
                <a href="<?= Auth::getDashboardUrl() ?>">Home</a>
                <?php foreach (array_slice($breadcrumb, 0, -1) as $crumb): ?>
                    <span class="separator">/</span>
                    <a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a>
                <?php endforeach; ?>
            </nav>
            <h1 class="topbar-page-title"><?= e($pageTitleDisplay) ?></h1>
        </div>
    </div>
    
    <div class="topbar-right">
        <!-- Search Trigger -->
        <button class="topbar-search-trigger" id="search-trigger" aria-label="Search">
            <i class="bi bi-search"></i>
            <span>Search...</span>
            <kbd>⌘K</kbd>
        </button>
        
        <!-- Theme Toggle -->
        <button class="topbar-btn" id="theme-toggle" data-tooltip="Toggle theme" aria-label="Toggle theme">
            <i class="bi bi-moon-stars" id="theme-icon"></i>
        </button>
        
        <!-- Notifications -->
        <button class="topbar-btn" id="notification-toggle" data-tooltip="Notifications" aria-label="Notifications">
            <i class="bi bi-bell"></i>
            <?php if ($unreadNotifications > 0): ?>
                <span class="notification-count animate-notification-badge" id="notif-count">
                    <?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?>
                </span>
            <?php endif; ?>
        </button>
        
        <!-- Profile -->
        <div class="topbar-profile dropdown-tf" id="profile-dropdown">
            <div class="avatar" data-tooltip="<?= e($user['name']) ?>">
                <?= getInitials($user['name']) ?>
            </div>
            <div class="topbar-profile-info">
                <div class="topbar-profile-name"><?= e($user['name']) ?></div>
                <div class="topbar-profile-role"><?= e(ucfirst($user['role'])) ?></div>
            </div>
            
            <!-- Dropdown Menu -->
            <div class="dropdown-menu-tf glass-command" id="profile-menu">
                <a href="<?= PAGES_URL ?>customer/profile.php" class="dropdown-item-tf">
                    <i class="bi bi-person"></i> Profile
                </a>
                <a href="<?= PAGES_URL ?>customer/settings.php" class="dropdown-item-tf">
                    <i class="bi bi-gear"></i> Settings
                </a>
                <div class="dropdown-divider-tf"></div>
                <a href="<?= API_URL ?>auth/logout.php" class="dropdown-item-tf">
                    <i class="bi bi-box-arrow-right"></i> Sign Out
                </a>
            </div>
        </div>
    </div>
</header>
