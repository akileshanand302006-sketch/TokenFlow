/**
 * TokenFlow Pro — Staff Counter Controller
 * Token lifecycle actions + service timer
 */

document.addEventListener('DOMContentLoaded', () => {
    // Service timer
    const timerDisplay = document.getElementById('timer-display');
    if (timerDisplay) {
        const startTime = new Date();
        setInterval(() => {
            const elapsed = Math.floor((new Date() - startTime) / 1000);
            const mins = String(Math.floor(elapsed / 60)).padStart(2, '0');
            const secs = String(elapsed % 60).padStart(2, '0');
            timerDisplay.textContent = `${mins}:${secs}`;
            
            // Color warning at 15 min
            if (elapsed > 900) {
                timerDisplay.parentElement.style.background = 'rgba(248,113,113,0.15)';
                timerDisplay.parentElement.style.color = 'var(--accent-danger)';
            }
        }, 1000);
    }

    // Auto-refresh queue count
    setInterval(async () => {
        if (document.hidden) return;
        try {
            const countEl = document.getElementById('queue-count');
            const callBtn = document.getElementById('call-next-btn');
            if (!countEl) return;
            
            const deptId = callBtn?.getAttribute('onclick')?.match(/callNextToken\((\d+)/)?.[1];
            if (!deptId) return;
            
            const result = await API.get(`queue/live.php?branch_id=1`);
            if (result.success && result.data?.departments) {
                const dept = result.data.departments.find(d => d.id == deptId);
                if (dept) {
                    countEl.textContent = dept.waiting;
                }
            }
        } catch(e) {}
    }, 8000);
});

// Token actions
async function callNextToken(departmentId, counterId) {
    const btn = document.getElementById('call-next-btn');
    if (btn) { btn.classList.add('loading'); btn.disabled = true; }
    
    const result = await API.post('tokens/status.php', {
        action: 'call-next',
        department_id: departmentId,
        counter_id: counterId
    });
    
    if (result.success) {
        const token = result.data?.token;
        Toast.success('Token Called', `Now serving ${token?.display_number || 'next token'}`);
        announce(`Token number ${token?.display_number}, please proceed to Counter ${counterId}`);
        setTimeout(() => location.reload(), 1000);
    } else {
        Toast.error('No Token', result.message || 'No more tokens in queue.');
        if (btn) { btn.classList.remove('loading'); btn.disabled = false; }
    }
}

async function completeToken(tokenId) {
    const btn = document.getElementById('complete-btn');
    if (btn) btn.classList.add('loading');
    
    const result = await API.post('tokens/status.php', { action: 'complete', token_id: tokenId });
    
    if (result.success) {
        Toast.success('Completed', 'Token service completed.');
        setTimeout(() => location.reload(), 800);
    } else {
        Toast.error('Error', result.message || 'Failed to complete.');
        if (btn) btn.classList.remove('loading');
    }
}

async function skipToken(tokenId) {
    Modal.confirm('Skip Token', 'Customer did not respond. Skip this token?', async () => {
        const result = await API.post('tokens/status.php', { action: 'skip', token_id: tokenId, reason: 'no_show' });
        if (result.success) {
            Toast.warning('Skipped', 'Token has been skipped.');
            setTimeout(() => location.reload(), 800);
        } else {
            Toast.error('Error', result.message || 'Failed to skip.');
        }
    });
}

async function recallToken(tokenId) {
    const result = await API.post('tokens/status.php', { action: 'recall', token_id: tokenId });
    if (result.success) {
        Toast.info('Recalled', 'Token has been recalled. Waiting for customer...');
        announce(`Token number recalled. Please proceed to your counter.`);
    } else {
        Toast.error('Error', result.message || 'Failed to recall.');
    }
}
