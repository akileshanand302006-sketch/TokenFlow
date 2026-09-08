/**
 * TokenFlow Pro — Utility Functions
 */

/**
 * Format number with abbreviation
 */
function formatNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
}

/**
 * Format duration in minutes
 */
function formatDuration(minutes) {
    if (minutes < 1) return '< 1 min';
    if (minutes < 60) return Math.round(minutes) + ' min';
    const hrs = Math.floor(minutes / 60);
    const mins = Math.round(minutes % 60);
    if (mins === 0) return hrs + ' hr';
    return `${hrs} hr ${mins} min`;
}

/**
 * Format date
 */
function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

/**
 * Format time
 */
function formatTime(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

/**
 * Debounce function
 */
function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function
 */
function throttle(func, limit = 100) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Get status color
 */
function getStatusColor(status) {
    const map = {
        waiting: 'var(--accent-warning)',
        serving: 'var(--accent-primary)',
        completed: 'var(--accent-success)',
        skipped: 'var(--accent-danger)',
        cancelled: 'var(--text-muted)',
        low: 'var(--accent-success)',
        moderate: 'var(--accent-warning)',
        high: 'var(--accent-danger)',
        critical: 'var(--accent-danger)',
    };
    return map[status] || 'var(--text-secondary)';
}

/**
 * Get status badge HTML
 */
function getStatusBadge(status) {
    const classes = {
        waiting: 'badge-warning',
        serving: 'badge-primary',
        completed: 'badge-success',
        skipped: 'badge-danger',
        cancelled: 'badge-secondary',
        open: 'badge-success',
        closed: 'badge-secondary',
    };
    const cls = classes[status] || 'badge-secondary';
    const label = status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    return `<span class="badge-tf ${cls}">${label}</span>`;
}

/**
 * Create skeleton loading
 */
function createSkeleton(type = 'card', count = 1) {
    let html = '';
    for (let i = 0; i < count; i++) {
        if (type === 'card') {
            html += '<div class="skeleton skeleton-card"></div>';
        } else if (type === 'text') {
            html += `
                <div class="skeleton skeleton-text" style="width:${60 + Math.random() * 40}%"></div>
                <div class="skeleton skeleton-text short"></div>
            `;
        } else if (type === 'chart') {
            html += '<div class="skeleton skeleton-chart"></div>';
        }
    }
    return html;
}

/**
 * Copy to clipboard
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        Toast.success('Copied', 'Copied to clipboard!');
    } catch (err) {
        // Fallback
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        Toast.success('Copied', 'Copied to clipboard!');
    }
}

/**
 * Download data as CSV
 */
function downloadCSV(data, filename = 'export.csv') {
    const csv = data.map(row => 
        row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')
    ).join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
    URL.revokeObjectURL(link.href);
}

/**
 * Voice announcement using Web Speech API
 */
function announce(text, lang = 'en-US') {
    if (!('speechSynthesis' in window)) return;
    
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = lang;
    utterance.rate = 0.9;
    utterance.pitch = 1;
    utterance.volume = 0.8;
    
    speechSynthesis.cancel();
    speechSynthesis.speak(utterance);
}

/**
 * Universal Bulletproof QR Code Renderer
 */
function renderQR(target, text, size = 180) {
    const canvas = typeof target === 'string' ? document.getElementById(target) : target;
    if (!canvas) return;

    canvas.width = size;
    canvas.height = size;
    canvas.style.display = 'block';

    const payload = String(text || 'TF-GENERIC-PASS');

    if (typeof QRCode !== 'undefined' && QRCode.toCanvas) {
        try {
            QRCode.toCanvas(canvas, payload, {
                width: size,
                margin: 1,
                color: { dark: '#000000', light: '#ffffff' }
            }, function(err) {
                if (err) renderQRFallback(canvas, payload, size);
            });
        } catch(e) {
            renderQRFallback(canvas, payload, size);
        }
    } else {
        renderQRFallback(canvas, payload, size);
    }
}

function renderQRFallback(canvas, text, size) {
    const parent = canvas.parentElement;
    if (!parent) return;
    let img = parent.querySelector('img.qr-fallback-img');
    if (!img) {
        img = document.createElement('img');
        img.className = 'qr-fallback-img';
        img.style.borderRadius = '8px';
        parent.appendChild(img);
    }
    img.src = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(text)}`;
    img.width = size;
    img.height = size;
    canvas.style.display = 'none';
}
