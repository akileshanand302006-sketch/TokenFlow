/**
 * TokenFlow Pro — Sidebar Controller
 */

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar-wrapper');
    const toggle = document.getElementById('sidebar-toggle');
    const mobileToggle = document.getElementById('mobile-menu-toggle');
    const overlay = document.getElementById('mobile-overlay');
    
    if (!sidebar) return;

    // Desktop collapse
    const savedState = localStorage.getItem('tf_sidebar_collapsed');
    if (savedState === 'true') {
        sidebar.classList.add('collapsed');
    }

    if (toggle) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('tf_sidebar_collapsed', sidebar.classList.contains('collapsed'));
        });
    }

    // Mobile drawer
    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            sidebar.classList.add('mobile-open');
            if (overlay) overlay.classList.add('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        });
    }

    // Close mobile on resize to desktop
    const mediaQuery = window.matchMedia('(min-width: 992px)');
    mediaQuery.addEventListener('change', (e) => {
        if (e.matches) {
            sidebar.classList.remove('mobile-open');
            if (overlay) overlay.classList.remove('active');
        }
    });

    // Keyboard navigation
    const links = sidebar.querySelectorAll('.sidebar-link');
    links.forEach((link, i) => {
        link.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' && i < links.length - 1) {
                e.preventDefault();
                links[i + 1].focus();
            }
            if (e.key === 'ArrowUp' && i > 0) {
                e.preventDefault();
                links[i - 1].focus();
            }
        });
    });
});
