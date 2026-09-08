/**
 * TokenFlow Pro — Charts Module
 * Theme-aware lazy loader and dynamic theme updater for Chart.js
 */

(function() {
    function getThemeColors() {
        const isLight = document.documentElement.getAttribute('data-theme') === 'light';
        return {
            textColor: isLight ? '#0f172a' : 'rgba(248, 250, 252, 0.9)',
            mutedColor: isLight ? '#334155' : 'rgba(203, 213, 225, 0.8)',
            gridColor: isLight ? 'rgba(15, 23, 42, 0.08)' : 'rgba(255, 255, 255, 0.06)',
            tooltipBg: isLight ? 'rgba(255, 255, 255, 0.95)' : 'rgba(6, 10, 19, 0.95)',
            tooltipTitle: isLight ? '#0f172a' : '#ffffff',
            tooltipBody: isLight ? '#1e293b' : '#cbd5e1',
            tooltipBorder: isLight ? 'rgba(2, 132, 199, 0.4)' : 'rgba(56, 189, 248, 0.4)',
        };
    }

    function applyChartDefaults() {
        if (typeof Chart === 'undefined') return;
        const c = getThemeColors();

        Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
        Chart.defaults.font.weight = '500';
        Chart.defaults.color = c.mutedColor;
        Chart.defaults.borderColor = c.gridColor;

        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
        Chart.defaults.plugins.legend.labels.padding = 16;
        Chart.defaults.plugins.legend.labels.color = c.textColor;

        Chart.defaults.plugins.tooltip.backgroundColor = c.tooltipBg;
        Chart.defaults.plugins.tooltip.titleColor = c.tooltipTitle;
        Chart.defaults.plugins.tooltip.bodyColor = c.tooltipBody;
        Chart.defaults.plugins.tooltip.borderColor = c.tooltipBorder;
        Chart.defaults.plugins.tooltip.borderWidth = 1;
        Chart.defaults.plugins.tooltip.cornerRadius = 10;
        Chart.defaults.plugins.tooltip.padding = 12;
    }

    function updateAllCharts() {
        if (typeof Chart === 'undefined') return;
        applyChartDefaults();
        const c = getThemeColors();

        Object.values(Chart.instances).forEach(chart => {
            if (chart.options.scales) {
                Object.values(chart.options.scales).forEach(scale => {
                    if (scale.ticks) scale.ticks.color = c.mutedColor;
                    if (scale.grid) scale.grid.color = c.gridColor;
                });
            }
            if (chart.options.plugins && chart.options.plugins.legend && chart.options.plugins.legend.labels) {
                chart.options.plugins.legend.labels.color = c.textColor;
            }
            chart.update();
        });
    }

    // Lazy load Chart.js
    const chartCanvases = document.querySelectorAll('canvas[id$="-chart"]');
    if (chartCanvases.length > 0 && typeof Chart === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
        script.onload = () => {
            applyChartDefaults();
            document.dispatchEvent(new CustomEvent('chartsReady'));
        };
        document.head.appendChild(script);
    } else if (typeof Chart !== 'undefined') {
        applyChartDefaults();
    }

    // Listen for theme changes
    document.addEventListener('themeChanged', () => {
        updateAllCharts();
    });
})();
