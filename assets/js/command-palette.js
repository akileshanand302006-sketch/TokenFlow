/**
 * TokenFlow Pro — Command Palette & Global Shortcut Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    const backdrop = document.getElementById('command-palette-backdrop');
    const palette = document.getElementById('command-palette');
    const input = document.getElementById('command-input');
    const results = document.getElementById('command-results');
    const searchTrigger = document.getElementById('search-trigger');

    if (!backdrop || !palette || !input) return;

    let selectedIndex = -1;
    let allItems = [];

    // Open palette
    function openPalette() {
        backdrop.classList.add('active');
        input.value = '';
        input.focus();
        filterResults('');
        selectedIndex = 0;
        updateSelection();
    }

    // Close palette
    function closePalette() {
        backdrop.classList.remove('active');
        selectedIndex = -1;
    }

    // Global Keyboard Shortcuts listener
    document.addEventListener('keydown', (e) => {
        // Ignore shortcuts if typing in input/textarea (except Ctrl+K / Cmd+K and Esc)
        const isInput = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName);

        // Ctrl+K / Cmd+K — Open Command Palette
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (backdrop.classList.contains('active')) {
                closePalette();
            } else {
                openPalette();
            }
            return;
        }

        // Esc — Close Palette
        if (e.key === 'Escape' && backdrop.classList.contains('active')) {
            closePalette();
            return;
        }

        if (isInput) return;

        // Alt Shortcuts
        if (e.altKey) {
            const key = e.key.toLowerCase();
            const shortcuts = {
                't': () => window.location.href = TF.pagesUrl + 'customer/generate-token.php',
                'd': () => window.location.href = TF.pagesUrl + (TF.userRole || 'customer') + '/dashboard.php',
                'q': () => window.location.href = TF.pagesUrl + 'customer/live-queue.php',
                'k': () => window.location.href = TF.pagesUrl + 'customer/active-token.php',
                'a': () => window.location.href = TF.pagesUrl + 'customer/appointments.php',
                'h': () => window.location.href = TF.pagesUrl + 'customer/history.php',
                'n': () => window.location.href = TF.pagesUrl + 'customer/notifications.php',
                'p': () => window.location.href = TF.pagesUrl + 'customer/profile.php',
                'b': () => {
                    const toggleBtn = document.getElementById('sidebar-toggle');
                    if (toggleBtn) toggleBtn.click();
                },
                'l': () => {
                    const themeBtn = document.getElementById('theme-toggle');
                    if (themeBtn) themeBtn.click();
                },
                'x': () => {
                    if (confirm('Are you sure you want to sign out?')) {
                        window.location.href = TF.apiUrl + 'auth/logout.php';
                    }
                }
            };

            if (shortcuts[key]) {
                e.preventDefault();
                shortcuts[key]();
            }
        }
    });

    // Search trigger button click
    if (searchTrigger) {
        searchTrigger.addEventListener('click', openPalette);
    }

    // Backdrop click
    backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) closePalette();
    });

    // Filter results
    function filterResults(query) {
        const items = results.querySelectorAll('.command-item');
        const sections = results.querySelectorAll('.command-section-title');
        
        query = query.toLowerCase().trim();
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = !query || text.includes(query) ? 'flex' : 'none';
        });

        // Hide empty sections
        sections.forEach(section => {
            let nextEl = section.nextElementSibling;
            let hasVisible = false;
            while (nextEl && !nextEl.classList.contains('command-section-title')) {
                if (nextEl.style.display !== 'none' && nextEl.classList.contains('command-item')) {
                    hasVisible = true;
                }
                nextEl = nextEl.nextElementSibling;
            }
            section.style.display = hasVisible ? 'block' : 'none';
        });

        allItems = Array.from(results.querySelectorAll('.command-item')).filter(i => i.style.display !== 'none');
        selectedIndex = allItems.length > 0 ? 0 : -1;
        updateSelection();
    }

    // Input events
    input.addEventListener('input', (e) => filterResults(e.target.value));

    // Keyboard navigation inside Palette
    input.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, allItems.length - 1);
            updateSelection();
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, 0);
            updateSelection();
        }
        if (e.key === 'Enter' && selectedIndex >= 0 && allItems[selectedIndex]) {
            e.preventDefault();
            executeItem(allItems[selectedIndex]);
        }
    });

    function updateSelection() {
        allItems.forEach((item, i) => {
            item.classList.toggle('selected', i === selectedIndex);
            if (i === selectedIndex) {
                item.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    function executeItem(item) {
        const action = item.dataset.action;
        const url = item.dataset.url;
        
        closePalette();

        if (action === 'navigate' && url) {
            window.location.href = url;
        } else if (action === 'toggle-theme') {
            const themeBtn = document.getElementById('theme-toggle');
            if (themeBtn) themeBtn.click();
        } else if (action === 'toggle-sidebar') {
            const sidebarBtn = document.getElementById('sidebar-toggle');
            if (sidebarBtn) sidebarBtn.click();
        } else if (action === 'logout' && url) {
            if (confirm('Are you sure you want to sign out?')) {
                window.location.href = url;
            }
        }
    }

    // Click on items
    results.addEventListener('click', (e) => {
        const item = e.target.closest('.command-item');
        if (item) executeItem(item);
    });
});
