/**
 * TokenFlow Pro — Notification Center
 */

document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('notification-toggle');
    const panel = document.getElementById('notification-panel');
    const closeBtn = document.getElementById('close-notifications');
    const markAllBtn = document.getElementById('mark-all-read');
    const notifList = document.getElementById('notification-list');
    const countBadge = document.getElementById('notif-count');
    const tabs = panel?.querySelectorAll('.tab-tf');

    if (!toggleBtn || !panel) return;

    let currentTab = 'all';

    // Toggle panel
    toggleBtn.addEventListener('click', () => {
        panel.classList.toggle('active');
        if (panel.classList.contains('active')) {
            loadNotifications();
        }
    });

    // Close
    if (closeBtn) {
        closeBtn.addEventListener('click', () => panel.classList.remove('active'));
    }

    // Close on ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') panel.classList.remove('active');
    });

    // Tab switching
    if (tabs) {
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                currentTab = tab.dataset.tab;
                loadNotifications();
            });
        });
    }

    // Mark all read
    if (markAllBtn) {
        markAllBtn.addEventListener('click', async () => {
            const result = await API.post('notifications/mark-read.php', { all: true });
            if (result.success) {
                updateBadge(0);
                loadNotifications();
                Toast.success('Done', 'All notifications marked as read.');
            }
        });
    }

    // Load notifications
    async function loadNotifications() {
        if (!notifList) return;

        const typeParam = currentTab !== 'all' ? `&type=${currentTab}` : '';
        const result = await API.get(`notifications/list.php?limit=20${typeParam}`);

        if (!result.success || !result.data?.notifications?.length) {
            notifList.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="bi bi-bell-slash"></i></div>
                    <div class="empty-state-title">No notifications</div>
                    <div class="empty-state-text">You're all caught up!</div>
                </div>`;
            return;
        }

        notifList.innerHTML = result.data.notifications.map(n => `
            <div class="notification-item ${n.is_read == 0 ? 'unread' : ''}" 
                 data-id="${n.id}" ${n.link ? `data-link="${n.link}"` : ''}>
                <div class="notif-icon glass-surface" style="background: rgba(var(--accent-primary-rgb), 0.1); color: var(--accent-primary);">
                    <i class="bi ${n.icon || 'bi-bell'}"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-title">${escapeHtml(n.title)}</div>
                    <div class="notif-message">${escapeHtml(n.message)}</div>
                    <div class="notif-time">${timeAgo(n.created_at)}</div>
                </div>
            </div>
        `).join('');

        updateBadge(result.data.unread_count);

        // Click handlers
        notifList.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', async () => {
                const id = item.dataset.id;
                const link = item.dataset.link;
                
                // Mark as read
                if (item.classList.contains('unread')) {
                    await API.post('notifications/mark-read.php', { id: parseInt(id) });
                    item.classList.remove('unread');
                }
                
                if (link) {
                    window.location.href = `${TF.baseUrl}${link}`;
                }
            });
        });
    }

    function updateBadge(count) {
        if (countBadge) {
            if (count > 0) {
                countBadge.textContent = count > 99 ? '99+' : count;
                countBadge.style.display = 'flex';
            } else {
                countBadge.style.display = 'none';
            }
        }
    }

    // Poll for new notifications every 30 seconds
    setInterval(async () => {
        if (document.hidden) return;
        try {
            const result = await API.get('notifications/list.php?limit=1');
            if (result.success && result.data) {
                updateBadge(result.data.unread_count);
            }
        } catch (e) {}
    }, 30000);
});

// Utility functions (available globally)
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

function timeAgo(datetime) {
    const now = new Date();
    const past = new Date(datetime);
    const diff = Math.floor((now - past) / 1000);
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
    return past.toLocaleDateString();
}
