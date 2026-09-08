/**
 * TokenFlow Pro — Application Core
 * Initialization, global utilities, and event management
 */

'use strict';

// ============================================================
// APP INITIALIZATION
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

const App = {
    init() {
        this.initDropdowns();
        this.initTooltips();
        this.initCountUp();
        this.initStaggeredAnimations();
        this.initLogoutHandler();
        console.log(`%c✦ TokenFlow Pro v${window.TF?.version || '1.0'}`, 
            'color: #38bdf8; font-weight: bold; font-size: 14px;');
    },

    // ---- Dropdowns ----
    initDropdowns() {
        document.querySelectorAll('.dropdown-tf').forEach(dropdown => {
            const trigger = dropdown.querySelector('.avatar, .topbar-profile, [data-dropdown-trigger]');
            const menu = dropdown.querySelector('.dropdown-menu-tf');
            
            if (!trigger || !menu) return;
            
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                // Close other dropdowns
                document.querySelectorAll('.dropdown-menu-tf.active').forEach(m => {
                    if (m !== menu) m.classList.remove('active');
                });
                menu.classList.toggle('active');
            });
        });
        
        // Close dropdowns on outside click
        document.addEventListener('click', () => {
            document.querySelectorAll('.dropdown-menu-tf.active').forEach(m => {
                m.classList.remove('active');
            });
        });
    },

    // ---- Tooltips ----
    initTooltips() {
        // CSS tooltips are handled via [data-tooltip] attribute in CSS
    },

    // ---- Count Up Animation ----
    initCountUp() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const target = parseFloat(el.dataset.countTo || el.textContent);
                    const duration = parseInt(el.dataset.countDuration || 1000);
                    const decimals = (el.dataset.countDecimals || 0);
                    const suffix = el.dataset.countSuffix || '';
                    const prefix = el.dataset.countPrefix || '';
                    
                    this.animateValue(el, 0, target, duration, decimals, prefix, suffix);
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.3 });

        document.querySelectorAll('[data-count-to]').forEach(el => {
            observer.observe(el);
        });
    },

    animateValue(el, start, end, duration, decimals = 0, prefix = '', suffix = '') {
        const startTime = performance.now();
        
        const step = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Ease out cubic
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const current = start + (end - start) * easeOut;
            
            el.textContent = prefix + current.toFixed(decimals) + suffix;
            
            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };
        
        requestAnimationFrame(step);
    },

    // ---- Staggered Animations ----
    initStaggeredAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-up');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('[data-animate]').forEach((el, i) => {
            el.style.opacity = '0';
            el.style.animationDelay = `${i * 0.05}s`;
            observer.observe(el);
        });
    },

    // ---- Logout Handler ----
    initLogoutHandler() {
        document.querySelectorAll('#logout-link, [data-action="logout"]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm('Are you sure you want to sign out?')) {
                    window.location.href = link.href || `${TF.apiUrl}auth/logout.php`;
                }
            });
        });
    }
};

// ============================================================
// TOAST SYSTEM
// ============================================================
const Toast = {
    container: null,

    getContainer() {
        if (!this.container) {
            this.container = document.getElementById('toast-container');
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.className = 'toast-container';
                this.container.id = 'toast-container';
                document.body.appendChild(this.container);
            }
        }
        return this.container;
    },

    show(type, title, message, duration = 4000) {
        const container = this.getContainer();
        
        const icons = {
            success: 'bi-check-circle-fill',
            error: 'bi-exclamation-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill'
        };

        const toast = document.createElement('div');
        toast.className = `toast-tf glass-floating ${type}`;
        toast.innerHTML = `
            <div class="toast-icon"><i class="bi ${icons[type] || icons.info}"></i></div>
            <div class="toast-content">
                <div class="toast-title">${this.escapeHtml(title)}</div>
                ${message ? `<div class="toast-message">${this.escapeHtml(message)}</div>` : ''}
            </div>
            <button class="toast-close" aria-label="Close">&times;</button>
        `;

        // Close button
        toast.querySelector('.toast-close').addEventListener('click', () => {
            this.dismiss(toast);
        });

        container.appendChild(toast);

        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => this.dismiss(toast), duration);
        }

        return toast;
    },

    dismiss(toast) {
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 300);
    },

    success(title, message) { return this.show('success', title, message); },
    error(title, message) { return this.show('error', title, message); },
    warning(title, message) { return this.show('warning', title, message); },
    info(title, message) { return this.show('info', title, message); },

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
};

// ============================================================
// MODAL SYSTEM
// ============================================================
const Modal = {
    show(options) {
        const { title, message, icon, iconType = 'primary', confirmText = 'Confirm', cancelText = 'Cancel', onConfirm, danger = false } = options;

        // Create backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop-tf active';

        // Create modal
        const modal = document.createElement('div');
        modal.className = 'modal-tf glass-command active';
        modal.innerHTML = `
            ${icon ? `<div class="modal-icon ${iconType}"><i class="bi ${icon}"></i></div>` : ''}
            <div class="modal-title-tf">${title}</div>
            <div class="modal-body-tf">${message}</div>
            <div class="modal-actions-tf">
                <button class="btn-tf btn-secondary modal-cancel">${cancelText}</button>
                <button class="btn-tf ${danger ? 'btn-danger' : 'btn-primary'} modal-confirm">${confirmText}</button>
            </div>
        `;

        document.body.appendChild(backdrop);
        document.body.appendChild(modal);

        // Event handlers
        const close = () => {
            backdrop.classList.remove('active');
            modal.classList.remove('active');
            setTimeout(() => {
                backdrop.remove();
                modal.remove();
            }, 300);
        };

        backdrop.addEventListener('click', close);
        modal.querySelector('.modal-cancel').addEventListener('click', close);
        modal.querySelector('.modal-confirm').addEventListener('click', () => {
            close();
            if (onConfirm) onConfirm();
        });

        // ESC key
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                close();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    },

    confirm(title, message, onConfirm) {
        this.show({ title, message, icon: 'bi-question-circle', onConfirm });
    },

    danger(title, message, onConfirm) {
        this.show({ title, message, icon: 'bi-exclamation-triangle', iconType: 'danger', confirmText: 'Delete', danger: true, onConfirm });
    }
};
