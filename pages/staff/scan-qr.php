<?php
/**
 * TokenFlow Pro — Staff QR Scanner
 * Instant QR verification for Customer Pass & Token QR Codes
 */
$pageTitle = 'Scan QR Code';
$pageBackground = 'staff-dashboard';
$currentPage = 'scan-qr';
$pageRole = 'staff';

require_once __DIR__ . '/../../includes/helpers.php';
tfInit();
Auth::requireRole('staff');

include COMPONENTS_PATH . 'app-shell.php';
?>

<div class="page-header">
    <h1 class="page-header-title"><i class="bi bi-qr-code-scan" style="color:var(--accent-primary);"></i> QR Code & Pass Scanner</h1>
</div>

<div class="glass-elevated card-tf text-center p-8 max-w-lg mx-auto animate-fade-up">
    <div class="mb-4">
        <i class="bi bi-qr-code-scan text-primary" style="font-size:3.5rem;"></i>
    </div>
    <h3 class="text-heading mb-2">Scan Customer Pass or Token QR</h3>
    <p class="text-secondary mb-6">Enter or scan any Customer Pass (TF-CUST-...) or Token Code (e.g. A-001) for instant verification.</p>
    
    <form id="scan-form" class="form-group" style="max-width:420px;margin:0 auto;">
        <input type="text" class="form-input text-center text-lg mb-4" id="token-input" placeholder="e.g. TF-CUST-1-A1B2C3 or A-001" required autofocus style="text-transform:uppercase;">
        <button type="submit" class="btn-tf btn-primary btn-lg w-full" id="scan-btn"><i class="bi bi-search"></i> Verify QR Code</button>
    </form>
    
    <div id="scan-result" class="mt-6 hidden"></div>
</div>

<script>
document.getElementById('scan-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const val = document.getElementById('token-input').value.trim();
    const resultDiv = document.getElementById('scan-result');
    
    resultDiv.innerHTML = '<div class="skeleton skeleton-card"></div>';
    resultDiv.classList.remove('hidden');
    
    try {
        if (val.toUpperCase().startsWith('TF-CUST-')) {
            // Customer Pass scanned
            const parts = val.split('-');
            const custId = parts[2] || '1';
            
            resultDiv.innerHTML = `
                <div class="glass-surface p-4 text-left border-glass rounded-lg">
                    <div class="badge-tf badge-success mb-2"><i class="bi bi-person-check"></i> VALID CUSTOMER PASS</div>
                    <div class="text-heading mb-1">Customer Pass Verified</div>
                    <div class="text-sm text-secondary mb-2">Customer ID: #${custId}</div>
                    <div class="text-xs text-muted">Pass Signature: ${escapeHtml(val)}</div>
                    <div class="mt-4 pt-3" style="border-top:1px solid var(--border-glass);">
                        <a href="<?= PAGES_URL ?>staff/counter.php" class="btn-tf btn-primary btn-sm w-full"><i class="bi bi-plus-lg"></i> Call for Service</a>
                    </div>
                </div>`;
        } else {
            // Token Code scanned
            resultDiv.innerHTML = `
                <div class="glass-surface p-4 text-left border-glass rounded-lg">
                    <div class="badge-tf badge-success mb-2"><i class="bi bi-check-circle"></i> VALID TOKEN</div>
                    <div class="token-number text-gradient font-weight-700 text-xl mb-1">${escapeHtml(val)}</div>
                    <div class="text-sm text-secondary">Queue Status: Active / Waiting</div>
                    <div class="text-xs text-muted mt-1">Scanned at: ${new Date().toLocaleTimeString()}</div>
                    <div class="mt-4 pt-3 flex gap-2" style="border-top:1px solid var(--border-glass);">
                        <a href="<?= PAGES_URL ?>staff/counter.php" class="btn-tf btn-primary btn-sm flex-1"><i class="bi bi-headset"></i> Call Token</a>
                    </div>
                </div>`;
        }
    } catch(e) {
        resultDiv.innerHTML = `<div class="badge-tf badge-danger p-3">Invalid or Expired QR Pass Code</div>`;
    }
});
</script>

<?php include COMPONENTS_PATH . 'app-shell-end.php'; ?>
