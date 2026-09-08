<?php
/**
 * TokenFlow Pro — Sidebar Component
 * Glass floating sidebar with role-based navigation
 */

$user = Auth::getCurrentUser();
$role = $user['role'];
?>
<nav class="sidebar" id="sidebar" aria-label="Main navigation">
    <!-- Logo -->
    <a href="<?= Auth::getDashboardUrl() ?>" class="sidebar-logo">
        <div class="sidebar-logo-icon">
            <i class="bi bi-layers"></i>
        </div>
        <span class="sidebar-logo-text">TokenFlow</span>
    </a>
    
    <?php if ($role === 'customer'): ?>
    <!-- CUSTOMER NAVIGATION -->
    <div class="sidebar-section">
        <div class="sidebar-section-title">Overview</div>
        <div class="sidebar-nav">
            <a href="<?= PAGES_URL ?>customer/dashboard.php" class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= PAGES_URL ?>customer/live-queue.php" class="sidebar-link <?= $currentPage === 'live-queue' ? 'active' : '' ?>">
                <i class="bi bi-broadcast"></i>
                <span>Live Queue</span>
            </a>
        </div>
    </div>
    
    <div class="sidebar-section">
        <div class="sidebar-section-title">Services</div>
        <div class="sidebar-nav">
            <a href="<?= PAGES_URL ?>customer/generate-token.php" class="sidebar-link <?= $currentPage === 'generate-token' ? 'active' : '' ?>">
                <i class="bi bi-plus-circle"></i>
                <span>Generate Token</span>
            </a>
            <a href="<?= PAGES_URL ?>customer/active-token.php" class="sidebar-link <?= $currentPage === 'active-token' ? 'active' : '' ?>">
                <i class="bi bi-ticket-perforated"></i>
                <span>Active Token</span>
            </a>
            <a href="<?= PAGES_URL ?>customer/appointments.php" class="sidebar-link <?= $currentPage === 'appointments' ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i>
                <span>Appointments</span>
            </a>
        </div>
    </div>
    
    <div class="sidebar-section">
        <div class="sidebar-section-title">History</div>
        <div class="sidebar-nav">
            <a href="<?= PAGES_URL ?>customer/history.php" class="sidebar-link <?= $currentPage === 'history' ? 'active' : '' ?>">
                <i class="bi bi-clock-history"></i>
                <span>Token History</span>
            </a>
            <a href="<?= PAGES_URL ?>customer/feedback.php" class="sidebar-link <?= $currentPage === 'feedback' ? 'active' : '' ?>">
                <i class="bi bi-star"></i>
                <span>Feedback</span>
            </a>
        </div>
    </div>
    
    <?php elseif ($role === 'staff'): ?>
    <!-- STAFF NAVIGATION -->
    <div class="sidebar-section">
        <div class="sidebar-section-title">Operations</div>
        <div class="sidebar-nav">
            <a href="<?= PAGES_URL ?>staff/dashboard.php" class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= PAGES_URL ?>staff/counter.php" class="sidebar-link <?= $currentPage === 'counter' ? 'active' : '' ?>">
                <i class="bi bi-display"></i>
                <span>Counter</span>
            </a>
            <a href="<?= PAGES_URL ?>staff/live-queue.php" class="sidebar-link <?= $currentPage === 'live-queue' ? 'active' : '' ?>">
                <i class="bi bi-broadcast"></i>
                <span>Live Queue</span>
            </a>
        </div>
    </div>
    
    <div class="sidebar-section">
        <div class="sidebar-section-title">Tools</div>
        <div class="sidebar-nav">
            <a href="<?= PAGES_URL ?>staff/scan-qr.php" class="sidebar-link <?= $currentPage === 'scan-qr' ? 'active' : '' ?>">
                <i class="bi bi-qr-code-scan"></i>
                <span>Scan QR</span>
            </a>
            <a href="<?= PAGES_URL ?>staff/transfer.php" class="sidebar-link <?= $currentPage === 'transfer' ? 'active' : '' ?>">
                <i class="bi bi-arrow-left-right"></i>
                <span>Transfer</span>
            </a>
        </div>
    </div>
    
    <div class="sidebar-section">
        <div class="sidebar-section-title">Insights</div>
        <div class="sidebar-nav">
            <a href="<?= PAGES_URL ?>staff/performance.php" class="sidebar-link <?= $currentPage === 'performance' ? 'active' : '' ?>">
                <i class="bi bi-graph-up"></i>
                <span>Performance</span>
            </a>
            <a href="<?= PAGES_URL ?>staff/history.php" class="sidebar-link <?= $currentPage === 'history' ? 'active' : '' ?>">
                <i class="bi bi-clock-history"></i>
                <span>History</span>
            </a>
        </div>
    </div>
    
    <?php elseif (in_array($role, ['admin', 'super_admin'])): ?>
    <!-- ADMIN NAVIGATION -->
    <div class="sidebar-section">
        <div class="sidebar-section-title">Overview</div>
        <div class="sidebar-nav">
            <a href="<?= PAGES_URL ?>admin/dashboard.php" class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-command"></i>
                <span>Command Center</span>
            </a>
            <a href="<?= PAGES_URL ?>admin/live-operations.php" class="sidebar-link <?= $currentPage === 'live-operations' ? 'active' : '' ?>">
                <i class="bi bi-broadcast"></i>
                <span>Live Operations</span>
            </a>
        </div>
    </div>
    
    <div class="sidebar-section">
        <div class="sidebar-section-title">Management</div>
        <div class="sidebar-nav">
            <a href="<?= PAGES_URL ?>admin/branches.php" class="sidebar-link <?= $currentPage === 'branches' ? 'active' : '' ?>">
                <i class="bi bi-building"></i>
                <span>Branches</span>
            </a>
            <a href="<?= PAGES_URL ?>admin/departments.php" class="sidebar-link <?= $currentPage === 'departments' ? 'active' : '' ?>">
                <i class="bi bi-diagram-3"></i>
                <span>Departments</span>
            </a>
            <a href="<?= PAGES_URL ?>admin/services.php" class="sidebar-link <?= $currentPage === 'services' ? 'active' : '' ?>">
                <i class="bi bi-clipboard-check"></i>
                <span>Services</span>
            </a>
            <a href="<?= PAGES_URL ?>admin/counters.php" class="sidebar-link <?= $currentPage === 'counters' ? 'active' : '' ?>">
                <i class="bi bi-display"></i>
                <span>Counters</span>
            </a>
            <a href="<?= PAGES_URL ?>admin/staff.php" class="sidebar-link <?= $currentPage === 'staff' ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                <span>Staff</span>
            </a>
            <a href="<?= PAGES_URL ?>admin/customers.php" class="sidebar-link <?= $currentPage === 'customers' ? 'active' : '' ?>">
                <i class="bi bi-person-lines-fill"></i>
                <span>Customers</span>
            </a>
        </div>
    </div>
    
    <div class="sidebar-section">
        <div class="sidebar-section-title">Insights</div>
        <div class="sidebar-nav">
            <a href="<?= PAGES_URL ?>admin/analytics.php" class="sidebar-link <?= $currentPage === 'analytics' ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i>
                <span>Analytics</span>
            </a>
            <a href="<?= PAGES_URL ?>admin/reports.php" class="sidebar-link <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i>
                <span>Reports</span>
            </a>
            <a href="<?= PAGES_URL ?>admin/performance.php" class="sidebar-link <?= $currentPage === 'performance' ? 'active' : '' ?>">
                <i class="bi bi-trophy"></i>
                <span>Performance</span>
            </a>
            <a href="<?= PAGES_URL ?>admin/sla.php" class="sidebar-link <?= $currentPage === 'sla' ? 'active' : '' ?>">
                <i class="bi bi-speedometer"></i>
                <span>SLA</span>
            </a>
        </div>
    </div>
    
    <div class="sidebar-section">
        <div class="sidebar-section-title">System</div>
        <div class="sidebar-nav">
            <a href="<?= PAGES_URL ?>admin/feedback.php" class="sidebar-link <?= $currentPage === 'feedback' ? 'active' : '' ?>">
                <i class="bi bi-chat-square-text"></i>
                <span>Feedback</span>
            </a>
            <a href="<?= PAGES_URL ?>admin/audit-logs.php" class="sidebar-link <?= $currentPage === 'audit-logs' ? 'active' : '' ?>">
                <i class="bi bi-shield-check"></i>
                <span>Audit Logs</span>
            </a>
            <a href="<?= PAGES_URL ?>admin/settings.php" class="sidebar-link <?= $currentPage === 'settings' ? 'active' : '' ?>">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-nav">
            <a href="<?= PAGES_URL ?><?= e($role === 'customer' ? 'customer' : ($role === 'staff' ? 'staff' : 'admin')) ?>/notifications.php" 
               class="sidebar-link <?= $currentPage === 'notifications' ? 'active' : '' ?>">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
                <?php if ($unreadNotifications > 0): ?>
                    <span class="sidebar-badge"><?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= PAGES_URL ?>customer/profile.php" class="sidebar-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
                <i class="bi bi-person-circle"></i>
                <span>Profile</span>
            </a>
            <a href="<?= API_URL ?>auth/logout.php" class="sidebar-link" id="logout-link">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
            </a>
        </div>
    </div>
</nav>
