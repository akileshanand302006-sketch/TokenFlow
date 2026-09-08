/**
 * TokenFlow Pro — Token Generation Wizard Controller
 */

document.addEventListener('DOMContentLoaded', () => {
    let currentStep = 1;
    const totalSteps = 5;
    
    const state = {
        branchId: null,
        branchName: '',
        departmentId: null,
        departmentName: '',
        serviceId: null,
        serviceName: '',
        type: 'normal',
        notes: ''
    };

    const prevBtn = document.getElementById('prev-btn');
    
    // Branch selection (Step 1)
    document.querySelectorAll('[data-branch-id]').forEach(card => {
        card.addEventListener('click', () => {
            selectCard(card, '[data-branch-id]');
            state.branchId = card.dataset.branchId;
            state.branchName = card.dataset.branchName;
            loadDepartments(state.branchId);
            goToStep(2);
        });
    });

    // Type selection (Step 4)
    document.querySelectorAll('[data-type]').forEach(card => {
        card.addEventListener('click', () => {
            selectCard(card, '[data-type]');
            state.type = card.dataset.type;
        });
    });

    // Previous button
    if (prevBtn) {
        prevBtn.addEventListener('click', () => goToStep(currentStep - 1));
    }

    // Generate button
    const genBtn = document.getElementById('generate-btn');
    if (genBtn) {
        genBtn.addEventListener('click', generateToken);
    }

    function selectCard(card, selector) {
        card.closest('.service-cards').querySelectorAll(selector).forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
    }

    function goToStep(step) {
        if (step < 1 || step > totalSteps) return;
        
        document.getElementById(`step-${currentStep}`).style.display = 'none';
        document.getElementById(`step-${step}`).style.display = 'block';
        document.getElementById(`step-${step}`).classList.add('wizard-step-active');
        
        // Update indicators
        document.querySelectorAll('.wizard-step-indicator').forEach(ind => {
            const s = parseInt(ind.dataset.step);
            ind.classList.toggle('active', s === step);
            ind.classList.toggle('completed', s < step);
        });
        document.querySelectorAll('.wizard-connector').forEach((conn, i) => {
            conn.classList.toggle('completed', i < step - 1);
        });

        currentStep = step;
        prevBtn.style.display = step > 1 ? 'inline-flex' : 'none';
        
        if (step === 5) buildConfirmation();
    }

    async function loadDepartments(branchId) {
        const container = document.getElementById('department-cards');
        container.innerHTML = '<div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div>';
        
        const result = await API.get(`departments/list.php?branch_id=${branchId}`);
        if (!result.success || !result.data?.length) {
            container.innerHTML = '<p class="text-secondary text-center p-6">No departments available.</p>';
            return;
        }
        
        container.innerHTML = result.data.map(d => `
            <div class="service-card glass-elevated hover-lift" data-dept-id="${d.id}" data-dept-name="${escapeHtml(d.name)}">
                <div class="service-card-icon" style="background: ${d.color}20; color: ${d.color};">
                    <i class="bi ${d.icon || 'bi-folder'}"></i>
                </div>
                <div class="service-card-name">${escapeHtml(d.name)}</div>
                <div class="service-card-desc">${escapeHtml(d.description || '')}</div>
                <div class="service-card-meta">
                    <span><i class="bi bi-people"></i> ${d.queue_count ?? 0} in queue</span>
                    <span><i class="bi bi-clock"></i> ~${d.avg_wait ?? 10} min</span>
                </div>
            </div>
        `).join('');
        
        container.querySelectorAll('[data-dept-id]').forEach(card => {
            card.addEventListener('click', () => {
                selectCard(card, '[data-dept-id]');
                state.departmentId = card.dataset.deptId;
                state.departmentName = card.dataset.deptName;
                loadServices(state.departmentId);
                goToStep(3);
            });
        });
    }

    async function loadServices(deptId) {
        const container = document.getElementById('service-cards');
        container.innerHTML = '<div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div>';
        
        const result = await API.get(`services/list.php?department_id=${deptId}`);
        if (!result.success || !result.data?.length) {
            container.innerHTML = '<p class="text-secondary text-center p-6">No services available.</p>';
            return;
        }
        
        container.innerHTML = result.data.map(s => `
            <div class="service-card glass-elevated hover-lift" data-svc-id="${s.id}" data-svc-name="${escapeHtml(s.name)}">
                <div class="service-card-icon" style="background: rgba(var(--accent-info-rgb), 0.1); color: var(--accent-info);">
                    <i class="bi ${s.icon || 'bi-clipboard'}"></i>
                </div>
                <div class="service-card-name">${escapeHtml(s.name)}</div>
                <div class="service-card-desc">${escapeHtml(s.description || '')}</div>
                <div class="service-card-meta">
                    <span><i class="bi bi-clock"></i> ~${s.avg_service_time ?? 10} min</span>
                </div>
            </div>
        `).join('');
        
        container.querySelectorAll('[data-svc-id]').forEach(card => {
            card.addEventListener('click', () => {
                selectCard(card, '[data-svc-id]');
                state.serviceId = card.dataset.svcId;
                state.serviceName = card.dataset.svcName;
                goToStep(4);
            });
        });
    }

    function buildConfirmation() {
        const container = document.getElementById('confirmation-details');
        container.innerHTML = `
            <div class="qi-item">
                <div class="qi-item-icon" style="background:rgba(var(--accent-primary-rgb),0.1);color:var(--accent-primary);"><i class="bi bi-building"></i></div>
                <span class="qi-item-text">Branch</span>
                <span class="qi-item-value">${escapeHtml(state.branchName)}</span>
            </div>
            <div class="qi-item">
                <div class="qi-item-icon" style="background:rgba(var(--accent-secondary-rgb),0.1);color:var(--accent-secondary);"><i class="bi bi-diagram-3"></i></div>
                <span class="qi-item-text">Department</span>
                <span class="qi-item-value">${escapeHtml(state.departmentName)}</span>
            </div>
            <div class="qi-item">
                <div class="qi-item-icon" style="background:rgba(var(--accent-info-rgb),0.1);color:var(--accent-info);"><i class="bi bi-clipboard-check"></i></div>
                <span class="qi-item-text">Service</span>
                <span class="qi-item-value">${escapeHtml(state.serviceName)}</span>
            </div>
            <div class="qi-item">
                <div class="qi-item-icon" style="background:rgba(var(--accent-warning-rgb),0.1);color:var(--accent-warning);"><i class="bi bi-star"></i></div>
                <span class="qi-item-text">Type</span>
                <span class="qi-item-value">${state.type === 'priority' ? 'Priority' : 'Normal'}</span>
            </div>
        `;
    }

    async function generateToken() {
        const btn = document.getElementById('generate-btn');
        btn.classList.add('loading');
        btn.disabled = true;
        
        const result = await API.post('tokens/generate.php', {
            branch_id: parseInt(state.branchId),
            department_id: parseInt(state.departmentId),
            service_id: parseInt(state.serviceId),
            type: state.type,
            notes: state.notes
        });
        
        if (result.success && result.data) {
            const token = result.data;
            document.querySelector('.wizard-container').style.display = 'none';
            document.getElementById('wizard-nav').style.display = 'none';
            
            const successScreen = document.getElementById('token-success-screen');
            successScreen.style.display = 'block';
            
            document.getElementById('success-token-number').textContent = token.display_number;
            document.getElementById('success-people-ahead').textContent = token.people_ahead ?? 0;
            document.getElementById('success-wait-time').textContent = `${token.estimated_wait ?? 0} min`;
            
            // Render QR Code for generated token
            renderQR('success-qr-canvas', token.qr_code || `TF-TOKEN-${token.display_number}`, 180);
            
            announce(`Your token number is ${token.display_number}. Estimated wait time is approximately ${token.estimated_wait ?? 0} minutes.`);
        } else {
            Toast.error('Generation Failed', result.message || 'Could not generate token.');
            btn.classList.remove('loading');
            btn.disabled = false;
        }
    }
});
