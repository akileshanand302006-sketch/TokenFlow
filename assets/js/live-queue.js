/**
 * TokenFlow Pro — Live Queue Controller
 */

document.addEventListener('DOMContentLoaded', () => {
    const branchFilter = document.getElementById('branch-filter');
    const servingCards = document.getElementById('serving-cards');
    const deptQueues = document.getElementById('dept-queues');
    
    if (!servingCards && !deptQueues) return;

    let pollTimer = null;

    function loadQueue() {
        const branchId = branchFilter?.value || 1;
        fetchQueueData(branchId);
    }

    async function fetchQueueData(branchId) {
        const result = await API.get(`queue/live.php?branch_id=${branchId}`);
        if (!result.success) return;
        
        const data = result.data;
        
        // Render now serving
        if (servingCards && data.serving) {
            if (data.serving.length === 0) {
                servingCards.innerHTML = `
                    <div class="glass-surface p-6 text-center" style="grid-column: 1/-1;">
                        <i class="bi bi-pause-circle text-muted" style="font-size:2rem;"></i>
                        <p class="text-secondary mt-2">No tokens currently being served.</p>
                    </div>`;
            } else {
                servingCards.innerHTML = data.serving.map(t => `
                    <div class="metric-card glass-elevated card-accent-success animate-fade-up">
                        <div class="flex items-center justify-between mb-3">
                            <span class="badge-tf badge-success"><span class="status-dot online pulse"></span> Serving</span>
                            <span class="text-caption">Counter ${t.counter_number}</span>
                        </div>
                        <div class="token-number token-number-md text-gradient mb-2">${t.display_number}</div>
                        <div class="text-sm text-secondary">${escapeHtml(t.service_name)}</div>
                        <div class="text-xs text-muted mt-1">${escapeHtml(t.department_name)}</div>
                    </div>
                `).join('');
            }
        }
        
        // Render department queues
        if (deptQueues && data.departments) {
            if (data.departments.length === 0) {
                deptQueues.innerHTML = `
                    <div class="glass-surface p-6 text-center" style="grid-column: 1/-1;">
                        <p class="text-secondary">No departments found.</p>
                    </div>`;
            } else {
                deptQueues.innerHTML = data.departments.map(dept => {
                    const healthClass = dept.waiting <= 3 ? 'low' : dept.waiting <= 8 ? 'moderate' : dept.waiting <= 15 ? 'high' : 'critical';
                    return `
                    <div class="glass-surface card-tf">
                        <div class="card-header-tf">
                            <div class="card-title-tf">
                                <i class="bi ${dept.icon || 'bi-folder'}" style="color:${dept.color || 'var(--accent-primary)'}"></i>
                                ${escapeHtml(dept.name)}
                            </div>
                            <span class="badge-tf badge-${healthClass === 'low' ? 'success' : healthClass === 'moderate' ? 'warning' : 'danger'}">
                                ${dept.waiting} waiting
                            </span>
                        </div>
                        <div class="dept-queue-bar mb-4">
                            <div class="progress-tf progress-lg">
                                <div class="progress-tf-bar ${healthClass === 'low' ? 'success' : healthClass === 'moderate' ? 'warning' : 'danger'}" 
                                     style="width:${Math.min(100, (dept.waiting / 20) * 100)}%"></div>
                            </div>
                        </div>
                        <div class="flex justify-between text-sm">
                            <div><span class="text-muted">Served today:</span> <strong>${dept.completed}</strong></div>
                            <div><span class="text-muted">Avg wait:</span> <strong>${dept.avg_wait ?? '-'} min</strong></div>
                            <div><span class="text-muted">Active counters:</span> <strong>${dept.active_counters}</strong></div>
                        </div>
                        ${dept.queue?.length > 0 ? `
                        <div class="queue-list mt-4" style="max-height:200px;overflow-y:auto;">
                            ${dept.queue.map((t, i) => `
                                <div class="queue-list-item">
                                    <div class="queue-list-position">${i + 1}</div>
                                    <span class="token-number" style="font-size:var(--text-sm);">${t.display_number}</span>
                                    <span class="text-xs text-muted flex-1">${escapeHtml(t.service_name)}</span>
                                    ${t.type === 'priority' ? '<span class="badge-tf badge-warning" style="font-size:9px;">Priority</span>' : ''}
                                    <span class="text-xs text-muted">~${t.estimated_wait_minutes ?? '-'} min</span>
                                </div>
                            `).join('')}
                        </div>` : ''}
                    </div>`;
                }).join('');
            }
        }
    }

    // Init
    loadQueue();

    // Poll
    pollTimer = setInterval(loadQueue, window.TF?.pollInterval || 5000);

    // Branch filter
    if (branchFilter) {
        branchFilter.addEventListener('change', loadQueue);
    }

    // Stop polling when tab hidden
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearInterval(pollTimer);
        } else {
            loadQueue();
            pollTimer = setInterval(loadQueue, window.TF?.pollInterval || 5000);
        }
    });
});
