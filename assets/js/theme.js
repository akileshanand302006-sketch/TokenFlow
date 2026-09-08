/**
 * TokenFlow Pro — Theme Controller (1-Click Instant Toggle)
 */

document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('theme-toggle');
    const icon = document.getElementById('theme-icon');
    
    if (!toggle || !icon) return;

    function getSavedTheme() {
        const saved = localStorage.getItem('tf_theme');
        if (saved && (saved === 'dark' || saved === 'light')) {
            return saved;
        }
        return 'dark';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('tf_theme', theme);
        
        // Update icon
        if (icon) {
            icon.className = theme === 'light' ? 'bi bi-sun' : 'bi bi-moon-stars';
        }

        // Notify app components (e.g. Chart.js)
        document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    }

    // 1-Click Instant Toggle between Dark and Light
    toggle.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-theme') || getSavedTheme();
        const nextTheme = current === 'dark' ? 'light' : 'dark';
        applyTheme(nextTheme);
        
        if (typeof Toast !== 'undefined' && Toast.info) {
            Toast.info('Theme Changed', nextTheme === 'light' ? 'Light Mode' : 'Dark Mode');
        }
    });

    // Alt+L keyboard shortcut to toggle theme
    document.addEventListener('keydown', (e) => {
        if (e.altKey && (e.key === 'l' || e.key === 'L')) {
            e.preventDefault();
            toggle.click();
        }
    });

    // Initial load
    applyTheme(getSavedTheme());
});
