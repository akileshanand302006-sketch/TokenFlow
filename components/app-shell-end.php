            </div><!-- /.content-wrapper -->
        </main><!-- /.app-main -->
    </div><!-- /.app-shell -->
    
    <!-- Toast Container -->
    <div class="toast-container" id="toast-container" aria-live="polite"></div>
    
    <!-- Command Palette & Shortcuts Modal -->
    <div class="command-palette-backdrop" id="command-palette-backdrop">
        <div class="command-palette glass-command" id="command-palette" style="max-width: 580px; width: 90%;">
            <div class="command-palette-header" style="padding: var(--space-4); border-bottom: 1px solid var(--border-glass); display: flex; align-items: center; gap: var(--space-3);">
                <i class="bi bi-search" style="font-size: 18px; color: var(--accent-primary);"></i>
                <input type="text" class="command-palette-input" id="command-input" 
                       placeholder="Search actions, pages, shortcuts... (Press Esc to close)" autocomplete="off" 
                       style="border: none; background: transparent; outline: none; flex: 1; color: var(--text-primary); font-size: var(--text-base);">
                <kbd style="padding: 2px 8px; border-radius: 4px; background: rgba(255,255,255,0.1); font-size: 11px; color: var(--text-secondary);">Esc</kbd>
            </div>
            
            <div class="command-palette-results" id="command-results" style="max-height: 380px; overflow-y: auto; padding: var(--space-2);">
                <div class="command-section-title">Quick Actions</div>
                <div class="command-item" data-action="navigate" data-url="<?= PAGES_URL ?>customer/generate-token.php">
                    <i class="bi bi-plus-circle" style="color: var(--accent-primary);"></i>
                    <span>Generate New Token</span>
                    <div class="command-shortcut"><kbd>Alt</kbd><kbd>T</kbd></div>
                </div>
                <div class="command-item" data-action="toggle-theme">
                    <i class="bi bi-sun-fill" style="color: var(--accent-warning);"></i>
                    <span>Toggle Theme (Dark / Light)</span>
                    <div class="command-shortcut"><kbd>Alt</kbd><kbd>L</kbd></div>
                </div>
                <div class="command-item" data-action="toggle-sidebar">
                    <i class="bi bi-layout-sidebar-inset" style="color: var(--accent-info);"></i>
                    <span>Toggle Sidebar Collapse</span>
                    <div class="command-shortcut"><kbd>Alt</kbd><kbd>B</kbd></div>
                </div>
                <div class="command-item" data-action="navigate" data-url="<?= PAGES_URL ?>customer/live-queue.php">
                    <i class="bi bi-broadcast" style="color: var(--accent-success);"></i>
                    <span>Open Live Queue Tracker</span>
                    <div class="command-shortcut"><kbd>Alt</kbd><kbd>Q</kbd></div>
                </div>

                <div class="command-section-title">Navigation & Pages</div>
                <div class="command-item" data-action="navigate" data-url="<?= PAGES_URL ?><?= e($pageRole) ?>/dashboard.php">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Dashboard</span>
                    <div class="command-shortcut"><kbd>Alt</kbd><kbd>D</kbd></div>
                </div>
                <div class="command-item" data-action="navigate" data-url="<?= PAGES_URL ?>customer/active-token.php">
                    <i class="bi bi-ticket-perforated"></i>
                    <span>Active Token</span>
                    <div class="command-shortcut"><kbd>Alt</kbd><kbd>K</kbd></div>
                </div>
                <div class="command-item" data-action="navigate" data-url="<?= PAGES_URL ?>customer/appointments.php">
                    <i class="bi bi-calendar-check"></i>
                    <span>Appointments</span>
                    <div class="command-shortcut"><kbd>Alt</kbd><kbd>A</kbd></div>
                </div>
                <div class="command-item" data-action="navigate" data-url="<?= PAGES_URL ?>customer/history.php">
                    <i class="bi bi-clock-history"></i>
                    <span>Token History</span>
                    <div class="command-shortcut"><kbd>Alt</kbd><kbd>H</kbd></div>
                </div>
                <div class="command-item" data-action="navigate" data-url="<?= PAGES_URL ?>customer/notifications.php">
                    <i class="bi bi-bell"></i>
                    <span>Notifications</span>
                    <div class="command-shortcut"><kbd>Alt</kbd><kbd>N</kbd></div>
                </div>
                <div class="command-item" data-action="navigate" data-url="<?= PAGES_URL ?>customer/profile.php">
                    <i class="bi bi-person"></i>
                    <span>Profile & Settings</span>
                    <div class="command-shortcut"><kbd>Alt</kbd><kbd>P</kbd></div>
                </div>
                <div class="command-item" data-action="logout" data-url="<?= API_URL ?>auth/logout.php">
                    <i class="bi bi-box-arrow-right" style="color: var(--accent-danger);"></i>
                    <span>Sign Out</span>
                    <div class="command-shortcut"><kbd>Alt</kbd><kbd>X</kbd></div>
                </div>
            </div>

            <div class="command-palette-footer" style="padding: var(--space-3) var(--space-4); border-top: 1px solid var(--border-glass); display: flex; align-items: center; justify-content: space-between; font-size: 11px; color: var(--text-muted);">
                <span><kbd style="padding: 1px 4px;">↑</kbd> <kbd style="padding: 1px 4px;">↓</kbd> Navigate</span>
                <span><kbd style="padding: 1px 4px;">↵</kbd> Select</span>
                <span><kbd style="padding: 1px 4px;">Ctrl</kbd> + <kbd style="padding: 1px 4px;">K</kbd> Open Search</span>
            </div>
        </div>
    </div>
    
    <!-- Notification Panel -->
    <div class="notification-panel glass-floating" id="notification-panel">
        <div class="notification-panel-header">
            <h3 class="text-heading">Notifications</h3>
            <div class="flex items-center gap-2">
                <button class="btn-tf btn-ghost btn-sm" id="mark-all-read" data-tooltip="Mark all read">
                    <i class="bi bi-check-all"></i>
                </button>
                <button class="btn-tf btn-ghost btn-sm" id="close-notifications" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
        <div class="tabs-tf mb-4">
            <button class="tab-tf active" data-tab="all">All</button>
            <button class="tab-tf" data-tab="token">Tokens</button>
            <button class="tab-tf" data-tab="appointment">Appointments</button>
            <button class="tab-tf" data-tab="system">System</button>
        </div>
        <div class="notification-list" id="notification-list">
            <!-- Populated by JS -->
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-bell-slash"></i></div>
                <div class="empty-state-title">No notifications</div>
                <div class="empty-state-text">You're all caught up!</div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    
    <script src="<?= ASSETS_URL ?>js/app.js"></script>
    <script src="<?= ASSETS_URL ?>js/api.js"></script>
    <script src="<?= ASSETS_URL ?>js/sidebar.js"></script>
    <script src="<?= ASSETS_URL ?>js/command-palette.js"></script>
    <script src="<?= ASSETS_URL ?>js/theme.js"></script>
    <script src="<?= ASSETS_URL ?>js/notifications.js"></script>
    <script src="<?= ASSETS_URL ?>js/utils.js"></script>
    
    <?php if (isset($pageScripts)): ?>
        <?php foreach ((array)$pageScripts as $script): ?>
            <script src="<?= ASSETS_URL ?>js/<?= e($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
